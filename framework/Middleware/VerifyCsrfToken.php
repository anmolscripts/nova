<?php

declare(strict_types=1);

namespace Nova\Middleware;

use Nova\Http\Request;

final class VerifyCsrfToken
{
    public function handle(Request $request, callable $next): mixed
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true) || str_starts_with($request->path(), '/api/')) {
            return $next($request);
        }

        if (!app()->csrf()->verify((string) $request->input('_token', ''))) {
            abort(419, 'Invalid CSRF token.');
        }

        return $next($request);
    }
}
