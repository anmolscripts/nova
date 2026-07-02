<?php

declare(strict_types=1);

namespace Nova\Middleware;

use Nova\Http\Request;

/**
 * Middleware that redirects authenticated visitors away from guest pages.
 */
final class RedirectIfAuthenticated
{
    public function handle(Request $request, callable $next): mixed
    {
        if (guest()) {
            return $next($request);
        }

        return $request->expectsJson()
            ? abort(403, 'Already authenticated')
            : redirect(config('auth.redirect', '/'));
    }
}
