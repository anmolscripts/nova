<?php

declare(strict_types=1);

namespace Nova\Routing;

use Nova\App\PageRenderer;
use Nova\Application\Application;
use Nova\Http\Request;
use Nova\Http\Response;
use Nova\Middleware\Pipeline;

/**
 * Dispatches matched routes to handlers.
 */
final class Router
{
    /** @var Route[] */
    private array $routes;

    public function __construct(private readonly Application $app)
    {
        $this->routes = (new RouteLoader($app))->load();
    }

    public function dispatch(Request $request): Response
    {
        if (!str_starts_with($request->path(), '/api/')) {
            return $this->dispatchPage($request);
        }

        $route = $this->match($request);
        if (!$route) {
            abort(404);
        }

        $params = $this->params($route, $request->path());
        $request->setRouteParams($params);

        $middleware = array_map(fn (string $file) => require $file, $route->middleware);

        return (new Pipeline($this->app))
            ->send($request)
            ->through($middleware)
            ->then(fn () => $this->runRoute($route, $params, $request));
    }

    private function dispatchPage(Request $request): Response
    {
        $match = (new RouteMatcher($this->app))->match($request->path());

        if (!$match) {
            abort(404);
        }

        $request->setRouteParams($match->parameters);

        return $this->app->make(PageRenderer::class)->render($match->page, $match->parameters);
    }

    public function all(): array
    {
        return $this->routes;
    }

    public function url(string $name, array $params = []): string
    {
        foreach ($this->routes as $route) {
            if ($route->name !== $name) {
                continue;
            }
            $path = $route->path;
            foreach ($params as $key => $value) {
                $path = preg_replace('#\(\?P<' . preg_quote((string) $key, '#') . '>[^)]+\)#', (string) $value, $path);
            }
            return url($path);
        }

        throw new RoutingException("Route [{$name}] not found.");
    }

    private function match(Request $request): ?Route
    {
        foreach ($this->routes as $route) {
            if (preg_match($route->regex, $request->path())) {
                return $route;
            }
        }
        return null;
    }

    private function params(Route $route, string $path): array
    {
        preg_match($route->regex, $path, $matches);
        $params = [];
        foreach ($route->params as $param) {
            $params[$param] = $matches[$param] ?? null;
        }
        return $params;
    }

    private function runRoute(Route $route, array $params, Request $request): Response
    {
        $result = require $route->file;

        if ($route->api && is_array($result) && array_key_exists($request->method(), $result)) {
            $result = $result[$request->method()];
        }

        if ($result instanceof \Closure) {
            $result = $result(...array_values($params));
        }

        $response = $this->normalize($result, $route->api);

        if (!$route->api && $route->layouts) {
            $slot = $response->content();
            foreach (array_reverse($route->layouts) as $layout) {
                $slot = $this->renderPhpFile($layout, ['slot' => $slot]);
            }
            return response($slot, $response->status());
        }

        return $response;
    }

    private function normalize(mixed $result, bool $api): Response
    {
        if ($result instanceof Response) {
            return $result;
        }
        if (is_array($result)) {
            return $api ? json($result) : response($this->app->view()->renderString(print_r($result, true)));
        }
        return response((string) $result);
    }

    private function renderPhpFile(string $file, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require $file;
            return (string) ob_get_clean();
        } catch (\Throwable $throwable) {
            ob_end_clean();
            throw $throwable;
        }
    }
}
