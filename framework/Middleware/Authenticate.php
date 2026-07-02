<?php

declare(strict_types=1);

namespace Nova\Middleware;

use Nova\Http\Request;

/**
 * Middleware that requires an authenticated user.
 */
final class Authenticate
{
    public function handle(Request $request, callable $next): mixed
    {
        if (check()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, 'Unauthenticated');
        }

        return redirect(config('auth.login', '/login'));
    }
}
