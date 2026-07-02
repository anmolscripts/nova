<?php

declare(strict_types=1);

namespace Nova\Routing;

use Nova\Application\Application;

/**
 * Loads application routes from discovered pages.
 */
final class RouteLoader
{
    /** @var Route[]|null */
    private ?array $routes = null;

    public function __construct(private readonly Application $app)
    {
    }

    /** @return Route[] */
    public function load(): array
    {
        if ($this->routes !== null) {
            return $this->routes;
        }

        $cached = $this->app->storagePath('framework/routes/routes.php');
        if (is_file($cached)) {
            return $this->routes = require $cached;
        }

        return $this->routes = $this->discover();
    }

    /** @return Route[] */
    public function discover(): array
    {
        return array_merge($this->discoverPages(), $this->discoverApi());
    }

    /** @return Route[] */
    private function discoverPages(): array
    {
        $routes = [];
        $root = $this->app->basePath('app');
        foreach ($this->files($root, 'page.php') as $file) {
            $directory = dirname($file);
            $relative = trim(str_replace('\\', '/', substr($directory, strlen($root))), '/');
            [$path, $params, $regex, $name] = $this->compilePath($relative);
            $routes[] = new Route($name ?: 'home', $path, $regex, $params, $file, $this->layoutsFor($directory, $root), $this->middlewareFor($directory, $root));
        }

        usort($routes, fn (Route $a, Route $b) => substr_count($a->path, '[') <=> substr_count($b->path, '['));
        return $routes;
    }

    /** @return Route[] */
    private function discoverApi(): array
    {
        $routes = [];
        $root = $this->app->basePath('api');
        if (!is_dir($root)) {
            return [];
        }

        foreach ($this->phpFiles($root) as $file) {
            $relative = trim(str_replace('\\', '/', substr($file, strlen($root), -4)), '/');
            if (str_ends_with($relative, '/index')) {
                $relative = substr($relative, 0, -6);
            }
            [$path, $params, $regex, $name] = $this->compilePath('api/' . $relative);
            $routes[] = new Route($name ?: 'api.index', $path, $regex, $params, $file, [], [], true);
        }

        return $routes;
    }

    private function compilePath(string $relative): array
    {
        $segments = $relative === '' ? [] : explode('/', $relative);
        $url = [];
        $params = [];
        $name = [];

        foreach ($segments as $segment) {
            if ($segment === '' || str_starts_with($segment, '(') && str_ends_with($segment, ')')) {
                continue;
            }
            if (str_starts_with($segment, '_')) {
                continue;
            }

            if (preg_match('/^\[\.\.\.(.+)]$/', $segment, $match)) {
                $params[] = $match[1];
                $url[] = '(?P<' . $match[1] . '>.*)';
                $name[] = $match[1];
                continue;
            }

            if (preg_match('/^\[(.+)]$/', $segment, $match)) {
                $params[] = $match[1];
                $url[] = '(?P<' . $match[1] . '>[^/]+)';
                $name[] = $match[1];
                continue;
            }

            $url[] = preg_quote($segment, '#');
            $name[] = $segment;
        }

        $path = '/' . implode('/', array_map(fn ($part) => str_replace('\\', '', $part), $url));
        $path = $path === '/' ? '/' : '/' . trim($path, '/');
        $regex = '#^' . ($path === '/' ? '/' : $path) . '$#';

        return [$path, $params, $regex, implode('.', $name)];
    }

    private function layoutsFor(string $directory, string $root): array
    {
        $layouts = [];
        $cursor = $root;
        if (is_file($cursor . DIRECTORY_SEPARATOR . 'layout.php')) {
            $layouts[] = $cursor . DIRECTORY_SEPARATOR . 'layout.php';
        }

        $relative = trim(substr($directory, strlen($root)), DIRECTORY_SEPARATOR);
        foreach ($relative === '' ? [] : explode(DIRECTORY_SEPARATOR, $relative) as $segment) {
            $cursor .= DIRECTORY_SEPARATOR . $segment;
            $layout = $cursor . DIRECTORY_SEPARATOR . 'layout.php';
            if (is_file($layout)) {
                $layouts[] = $layout;
            }
        }

        return $layouts;
    }

    private function middlewareFor(string $directory, string $root): array
    {
        $middleware = [];
        $cursor = $root;
        if (is_file($cursor . DIRECTORY_SEPARATOR . 'middleware.php')) {
            $middleware[] = $cursor . DIRECTORY_SEPARATOR . 'middleware.php';
        }

        $relative = trim(substr($directory, strlen($root)), DIRECTORY_SEPARATOR);
        foreach ($relative === '' ? [] : explode(DIRECTORY_SEPARATOR, $relative) as $segment) {
            $cursor .= DIRECTORY_SEPARATOR . $segment;
            $file = $cursor . DIRECTORY_SEPARATOR . 'middleware.php';
            if (is_file($file)) {
                $middleware[] = $file;
            }
        }

        return $middleware;
    }

    private function files(string $root, string $name): array
    {
        if (!is_dir($root)) {
            return [];
        }

        $files = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root)) as $file) {
            if ($file->isFile() && $file->getFilename() === $name) {
                $files[] = $file->getPathname();
            }
        }
        return $files;
    }

    private function phpFiles(string $root): array
    {
        $files = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root)) as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        return $files;
    }
}
