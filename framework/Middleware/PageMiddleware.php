<?php

declare(strict_types=1);

namespace Nova\Middleware;

use Nova\App\PageDiscovery;
use Nova\Http\Request;

/**
 * Middleware that runs page-specific middleware files.
 */
final class PageMiddleware
{
    private const ALIASES = [
        'auth' => Authenticate::class,
        'guest' => RedirectIfAuthenticated::class,
    ];

    public function handle(Request $request, callable $next): mixed
    {
        $page = app()->make(PageDiscovery::class)->match($this->pagePath($request->path()));
        if ($page === null) {
            return $next($request);
        }

        return (new Pipeline(app()))
            ->send($request)
            ->through($this->middlewareFor($page->directory))
            ->then($next);
    }

    private function pagePath(string $path): string
    {
        return preg_replace('#/actions/[A-Za-z0-9_-]+$#', '', $path) ?: '/';
    }

    private function middlewareFor(string $pageDirectory): array
    {
        $root = app()->basePath('app');
        $cursor = $root;
        $files = [];
        $relative = trim(substr($pageDirectory, strlen($root)), DIRECTORY_SEPARATOR);

        if (is_file($cursor . DIRECTORY_SEPARATOR . 'middleware.php')) {
            $files[] = $cursor . DIRECTORY_SEPARATOR . 'middleware.php';
        }

        foreach ($relative === '' ? [] : explode(DIRECTORY_SEPARATOR, $relative) as $segment) {
            $cursor .= DIRECTORY_SEPARATOR . $segment;
            $file = $cursor . DIRECTORY_SEPARATOR . 'middleware.php';
            if (is_file($file)) {
                $files[] = $file;
            }
        }

        $middleware = [];
        foreach ($files as $file) {
            foreach ((array) require $file as $entry) {
                $middleware[] = self::ALIASES[$entry] ?? $entry;
            }
        }

        return $middleware;
    }
}
