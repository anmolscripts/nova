<?php

declare(strict_types=1);

namespace Nova\App;

use Nova\Application\Application;

/**
 * Discovers page files from the application tree.
 */
final class PageDiscovery
{
    /** @var Page[]|null */
    private ?array $pages = null;

    public function __construct(private readonly Application $app)
    {
    }

    /** @return Page[] */
    public function pages(): array
    {
        $manifest = $this->manifestPath();
        $this->profile('page.discovery.start');

        if (is_file($manifest)) {
            $this->profile('page.manifest.read');
            return $this->pages = $this->hydrate(require $manifest);
        }

        $pages = $this->discover();
        $this->profile('page.manifest.write');
        $this->writeManifest($pages);

        return $this->pages = $pages;
    }

    public function match(string $path): ?Page
    {
        foreach ($this->pages() as $page) {
            if (preg_match($page->regex, $path)) {
                return $page;
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    public function parameters(Page $page, string $path): array
    {
        preg_match($page->regex, $path, $matches);
        $parameters = [];

        foreach ($page->routeParameters as $name => $type) {
            $value = $matches[$name] ?? null;
            if ($type === 'catchall') {
                $parameters[$name] = $value === null || $value === '' ? [] : explode('/', trim($value, '/'));
                continue;
            }
            $parameters[$name] = $value;
        }

        return $parameters;
    }

    /** @return Page[] */
    public function discover(): array
    {
        $root = $this->app->basePath('app');
        if (!is_dir($root)) {
            return [];
        }

        $pages = [];
        foreach ($this->pageDirectories($root) as $directory) {
            $relative = trim(str_replace('\\', '/', substr($directory, strlen($root))), '/');
            [$uri, $regex, $parameters, $score] = $this->compileUri($relative);

            $pages[] = new Page(
                uri: $uri,
                directory: $directory,
                serverFile: $this->file($directory, 'page.php'),
                templateFile: $this->file($directory, 'page.latte'),
                layoutFiles: $this->layoutFiles($directory, $root),
                typescriptFile: $this->file($directory, 'page.ts'),
                scssFile: $this->file($directory, 'page.scss'),
                loadingFile: $this->file($directory, 'loading.latte'),
                errorFile: $this->file($directory, 'error.latte'),
                routeParameters: $parameters,
                regex: $regex
            );
            $scores[] = $score;
        }

        if (isset($scores)) {
            array_multisort($scores, SORT_DESC, $pages);
        }

        return $pages;
    }

    /** @param Page[] $pages */
    public function writeManifest(array $pages): void
    {
        $path = $this->manifestPath();
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($path, "<?php\n\nreturn " . var_export($this->manifestRows($pages), true) . ";\n");
        $this->pages = $pages;
    }

    private function manifestPath(): string
    {
        return $this->app->storagePath('framework/pages.php');
    }

    private function profile(string $name): void
    {
        if (method_exists($this->app, 'profiler')) {
            $this->app->profiler()->record($name);
        }
    }

    /** @return Page[] */
    private function hydrate(array $manifest): array
    {
        $first = reset($manifest);
        if ($first instanceof Page) {
            return $manifest;
        }

        $pages = [];
        foreach ($manifest as $uri => $row) {
            $directory = $this->app->basePath($row['path']);
            $pages[] = new Page(
                uri: (string) $uri,
                directory: $directory,
                serverFile: $this->optionalPath($directory, $row['server'] ?? null),
                templateFile: $this->optionalPath($directory, $row['template'] ?? null),
                layoutFiles: array_map(fn (string $layout): string => $this->app->basePath($layout), $row['layouts'] ?? []),
                typescriptFile: in_array('page.ts', $row['assets'] ?? [], true) ? $directory . DIRECTORY_SEPARATOR . 'page.ts' : null,
                scssFile: in_array('page.scss', $row['assets'] ?? [], true) ? $directory . DIRECTORY_SEPARATOR . 'page.scss' : null,
                loadingFile: $this->optionalPath($directory, $row['loading'] ?? null),
                errorFile: $this->optionalPath($directory, $row['error'] ?? null),
                routeParameters: $row['routeParameters'] ?? [],
                regex: $row['regex']
            );
        }

        return $pages;
    }

    /** @param Page[] $pages */
    private function manifestRows(array $pages): array
    {
        $rows = [];
        foreach ($pages as $page) {
            $assets = [];
            if ($page->typescriptFile !== null) {
                $assets[] = 'page.ts';
            }
            if ($page->scssFile !== null) {
                $assets[] = 'page.scss';
            }

            $rows[$page->uri] = [
                'path' => $this->relativePath($page->directory),
                'template' => $page->templateFile ? basename($page->templateFile) : null,
                'server' => $page->serverFile ? basename($page->serverFile) : null,
                'layouts' => array_map(fn (string $layout): string => $this->relativePath($layout), $page->layoutFiles),
                'assets' => $assets,
                'loading' => $page->loadingFile ? basename($page->loadingFile) : null,
                'error' => $page->errorFile ? basename($page->errorFile) : null,
                'routeParameters' => $page->routeParameters,
                'regex' => $page->regex,
            ];
        }

        return $rows;
    }

    private function optionalPath(string $directory, ?string $file): ?string
    {
        return $file ? $directory . DIRECTORY_SEPARATOR . $file : null;
    }

    private function relativePath(string $path): string
    {
        $base = rtrim(str_replace('\\', '/', $this->app->basePath()), '/') . '/';
        $normalized = str_replace('\\', '/', $path);

        return str_starts_with($normalized, $base) ? substr($normalized, strlen($base)) : $normalized;
    }

    /** @return string[] */
    private function pageDirectories(string $root): array
    {
        $directories = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root)) as $file) {
            if (!$file->isFile() || !in_array($file->getFilename(), ['page.php', 'page.latte'], true)) {
                continue;
            }
            $directory = $file->getPathname();
            $directories[dirname($directory)] = dirname($directory);
        }

        return array_values($directories);
    }

    private function compileUri(string $relative): array
    {
        $segments = $relative === '' ? [] : explode('/', $relative);
        $uriParts = [];
        $regexParts = [];
        $parameters = [];
        $score = 0;

        foreach ($segments as $segment) {
            if ($segment === '' || str_starts_with($segment, '_')) {
                continue;
            }

            if (preg_match('/^\[\[\.\.\.(.+)]]$/', $segment, $match)) {
                $name = $match[1];
                $uriParts[] = $segment;
                $regexParts[] = '(?:/(?P<' . $name . '>.*))?';
                $parameters[$name] = 'catchall';
                $score -= 20;
                continue;
            }

            if (preg_match('/^\[\.\.\.(.+)]$/', $segment, $match)) {
                $name = $match[1];
                $uriParts[] = $segment;
                $regexParts[] = '/(?P<' . $name . '>.+)';
                $parameters[$name] = 'catchall';
                $score -= 10;
                continue;
            }

            if (preg_match('/^\[(.+)]$/', $segment, $match)) {
                $name = $match[1];
                $uriParts[] = $segment;
                $regexParts[] = '/(?P<' . $name . '>[^/]+)';
                $parameters[$name] = 'single';
                $score += 1;
                continue;
            }

            $uriParts[] = $segment;
            $regexParts[] = '/' . preg_quote($segment, '#');
            $score += 10;
        }

        $uri = '/' . implode('/', $uriParts);
        $uri = $uri === '/' ? '/' : '/' . trim($uri, '/');
        $regex = '#^' . ($regexParts ? implode('', $regexParts) : '/') . '$#';

        return [$uri, $regex, $parameters, $score];
    }

    /** @return string[] */
    private function layoutFiles(string $directory, string $root): array
    {
        $layouts = [];
        $cursor = $root;
        $rootLayout = $this->file($cursor, 'layout.latte');
        if ($rootLayout !== null) {
            $layouts[] = $rootLayout;
        }

        $relative = trim(substr($directory, strlen($root)), DIRECTORY_SEPARATOR);
        foreach ($relative === '' ? [] : explode(DIRECTORY_SEPARATOR, $relative) as $segment) {
            $cursor .= DIRECTORY_SEPARATOR . $segment;
            $layout = $this->file($cursor, 'layout.latte');
            if ($layout !== null) {
                $layouts[] = $layout;
            }
        }

        return $layouts;
    }

    private function file(string $directory, string $name): ?string
    {
        $file = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . $name;
        return is_file($file) ? $file : null;
    }
}
