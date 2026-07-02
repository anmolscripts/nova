<?php

declare(strict_types=1);

namespace Nova\Middleware;

use Nova\Http\Request;

/**
 * Middleware that verifies CSRF tokens for unsafe requests.
 */
final class VerifyCsrfToken
{
    public function handle(Request $request, callable $next): mixed
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true) || str_starts_with($request->path(), '/api/')) {
            return $next($request);
        }

        $token = (string) ($request->input('_token') ?? $request->header('X-CSRF-Token', ''));

        if (!app()->csrf()->verify($token)) {
            abort(419, 'Invalid CSRF token.');
        }

        return $next($request);
    }
}
