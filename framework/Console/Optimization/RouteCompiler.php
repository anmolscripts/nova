<?php

declare(strict_types=1);

namespace Nova\Console\Optimization;

use Nova\Routing\RouteMatcher;

final class RouteCompiler implements CompilerInterface
{
    public function __construct(private readonly \Nova\Application\Application $app)
    {
    }

    public function name(): string
    {
        return 'route';
    }

    public function compile(): array
    {
        $path = $this->app->storagePath('framework/routes.php');
        $created = !is_file($path) ? 1 : 0;
        $modified = is_file($path) ? 1 : 0;
        (new RouteMatcher($this->app))->writeCache();

        return ['created' => $created, 'modified' => $modified, 'warnings' => []];
    }

    public function clear(): void
    {
        $this->delete($this->app->storagePath('framework/routes.php'));
    }

    public function status(): string
    {
        $file = $this->app->storagePath('framework/routes.php');
        return is_file($file) ? 'Generated' : 'Missing';
    }

    private function delete(string $file): void
    {
        if (is_file($file)) {
            unlink($file);
        }
    }
}
