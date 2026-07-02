<?php

declare(strict_types=1);

namespace Nova\Component;

use Nova\Application\Application;

/**
 * Discovers component directories and metadata.
 */
final class ComponentDiscovery
{
    /** @var array<string, Component>|null */
    private ?array $components = null;

    public function __construct(private readonly Application $app)
    {
    }

    public function resolve(string $name): Component
    {
        $components = $this->components();
        $page = $this->app->currentPage();

        if ($page !== null) {
            foreach ($this->localScopes($page->directory) as $scope) {
                $key = $scope . ':' . $name;
                if (isset($components[$key])) {
                    return $components[$key];
                }
            }
        }

        $key = 'global:' . $name;
        if (isset($components[$key])) {
            return $components[$key];
        }

        throw new ComponentException("Component [{$name}] was not found.");
    }

    /** @return array<string, Component> */
    public function components(): array
    {
        $manifest = $this->manifestPath();
        $this->profile('component.discovery.start');

        if (is_file($manifest)) {
            $this->profile('component.manifest.read');
            return $this->components = $this->hydrate(require $manifest);
        }

        $components = $this->discover();
        $this->profile('component.manifest.write');
        $this->writeManifest($components);

        return $this->components = $components;
    }

    /** @return array<string, Component> */
    public function discover(): array
    {
        return array_merge($this->discoverGlobal(), $this->discoverLocal());
    }

    /** @param array<string, Component> $components */
    public function writeManifest(array $components): void
    {
        $path = $this->manifestPath();
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($path, "<?php\n\nreturn " . var_export($this->manifestRows($components), true) . ";\n");
        $this->components = $components;
    }

    /** @return array<string, Component> */
    private function discoverGlobal(): array
    {
        return $this->discoverIn($this->app->basePath('components'), 'global');
    }

    /** @return array<string, Component> */
    private function discoverLocal(): array
    {
        $components = [];
        $root = $this->app->basePath('app');
        if (!is_dir($root)) {
            return [];
        }

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root)) as $file) {
            if (!$file->isFile() || $file->getFilename() !== 'component.latte') {
                continue;
            }

            $componentDirectory = dirname($file->getPathname());
            $componentsDirectory = dirname($componentDirectory);
            if (basename($componentsDirectory) !== 'components') {
                continue;
            }

            $scope = $this->relativePath(dirname($componentsDirectory));
            $component = $this->componentFromDirectory($componentDirectory, $scope);
            $components[$scope . ':' . $component->name] = $component;
        }

        return $components;
    }

    /** @return array<string, Component> */
    private function discoverIn(string $root, string $scope): array
    {
        if (!is_dir($root)) {
            return [];
        }

        $components = [];
        foreach (glob($root . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [] as $directory) {
            $component = $this->componentFromDirectory($directory, $scope);
            $components[$scope . ':' . $component->name] = $component;
        }

        return $components;
    }

    private function componentFromDirectory(string $directory, ?string $scope): Component
    {
        $template = $this->file($directory, 'component.latte');
        if ($template === null) {
            throw new ComponentException('Component template missing in [' . $directory . '].');
        }

        return new Component(
            name: basename($directory),
            directory: $directory,
            templateFile: $template,
            serverFile: $this->file($directory, 'component.php'),
            typescriptFile: $this->file($directory, 'component.ts'),
            scssFile: $this->file($directory, 'component.scss'),
            scope: $scope
        );
    }

    /** @return string[] */
    private function localScopes(string $pageDirectory): array
    {
        $root = rtrim(str_replace('\\', '/', $this->app->basePath('app')), '/');
        $cursor = rtrim(str_replace('\\', '/', $pageDirectory), '/');
        $scopes = [];

        while (str_starts_with($cursor, $root)) {
            $scopes[] = $this->relativePath($cursor);
            if ($cursor === $root) {
                break;
            }
            $cursor = dirname($cursor);
        }

        return $scopes;
    }

    /** @return array<string, Component> */
    private function hydrate(array $manifest): array
    {
        $components = [];
        foreach ($manifest as $key => $row) {
            $directory = $this->app->basePath($row['path']);
            $components[$key] = new Component(
                name: $row['name'],
                directory: $directory,
                templateFile: $directory . DIRECTORY_SEPARATOR . 'component.latte',
                serverFile: $row['server'] ? $directory . DIRECTORY_SEPARATOR . $row['server'] : null,
                typescriptFile: in_array('component.ts', $row['assets'] ?? [], true) ? $directory . DIRECTORY_SEPARATOR . 'component.ts' : null,
                scssFile: in_array('component.scss', $row['assets'] ?? [], true) ? $directory . DIRECTORY_SEPARATOR . 'component.scss' : null,
                scope: $row['scope']
            );
        }

        return $components;
    }

    /** @param array<string, Component> $components */
    private function manifestRows(array $components): array
    {
        $rows = [];
        foreach ($components as $key => $component) {
            $assets = [];
            if ($component->typescriptFile !== null) {
                $assets[] = 'component.ts';
            }
            if ($component->scssFile !== null) {
                $assets[] = 'component.scss';
            }

            $rows[$key] = [
                'name' => $component->name,
                'path' => $this->relativePath($component->directory),
                'template' => 'component.latte',
                'server' => $component->serverFile ? 'component.php' : null,
                'assets' => $assets,
                'scope' => $component->scope,
            ];
        }

        return $rows;
    }

    private function manifestPath(): string
    {
        return $this->app->storagePath('framework/components.php');
    }

    private function profile(string $name): void
    {
        if (method_exists($this->app, 'profiler')) {
            $this->app->profiler()->record($name);
        }
    }

    private function file(string $directory, string $name): ?string
    {
        $file = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . $name;
        return is_file($file) ? $file : null;
    }

    private function relativePath(string $path): string
    {
        $base = rtrim(str_replace('\\', '/', $this->app->basePath()), '/') . '/';
        $normalized = str_replace('\\', '/', $path);

        return str_starts_with($normalized, $base) ? substr($normalized, strlen($base)) : $normalized;
    }
}
