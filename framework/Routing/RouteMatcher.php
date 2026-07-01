<?php

declare(strict_types=1);

namespace Nova\Routing;

use Nova\App\Page;
use Nova\App\PageDiscovery;
use Nova\Application\Application;

final class RouteMatcher
{
    public function __construct(private readonly Application $app)
    {
    }

    public function match(string $path): ?RouteMatch
    {
        $path = $path === '/' ? '/' : '/' . trim($path, '/');

        foreach ($this->routes() as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            $page = $this->pageFor($route['uri']);
            if ($page === null) {
                continue;
            }

            return new RouteMatch($page, $this->parameters($route['parameters'], $matches));
        }

        return null;
    }

    public function writeCache(): void
    {
        $path = $this->cachePath();
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($path, "<?php\n\nreturn " . var_export($this->buildRoutes(), true) . ";\n");
    }

    private function routes(): array
    {
        $cache = $this->cachePath();
        if (is_file($cache)) {
            return require $cache;
        }

        $routes = $this->buildRoutes();
        $this->writeCache();

        return $routes;
    }

    private function buildRoutes(): array
    {
        $routes = [];
        foreach ($this->app->make(PageDiscovery::class)->pages() as $page) {
            $routes[] = [
                'uri' => $page->uri,
                'regex' => $page->regex,
                'parameters' => $page->routeParameters,
                'priority' => $this->priority($page),
            ];
        }

        usort($routes, fn (array $a, array $b): int => $b['priority'] <=> $a['priority']);

        return $routes;
    }

    private function priority(Page $page): int
    {
        $segments = $page->uri === '/' ? [] : explode('/', trim($page->uri, '/'));
        $score = count($segments);

        foreach ($segments as $segment) {
            if (preg_match('/^\[\[\.\.\..+]]$/', $segment)) {
                $score += 0;
                continue;
            }
            if (preg_match('/^\[\.\.\..+]$/', $segment)) {
                $score += 100;
                continue;
            }
            if (preg_match('/^\[.+]$/', $segment)) {
                $score += 1_000;
                continue;
            }
            $score += 10_000;
        }

        return $score;
    }

    private function pageFor(string $uri): ?Page
    {
        foreach ($this->app->make(PageDiscovery::class)->pages() as $page) {
            if ($page->uri === $uri) {
                return $page;
            }
        }

        return null;
    }

    private function parameters(array $routeParameters, array $matches): array
    {
        $parameters = [];

        foreach ($routeParameters as $name => $type) {
            $value = $matches[$name] ?? '';
            if ($type === 'catchall') {
                $parameters[$name] = $value === '' ? [] : array_values(array_filter(explode('/', trim((string) $value, '/')), fn (string $segment): bool => $segment !== ''));
                continue;
            }

            $parameters[$name] = (string) $value;
        }

        return $parameters;
    }

    private function cachePath(): string
    {
        return $this->app->storagePath('framework/routes.php');
    }
}
