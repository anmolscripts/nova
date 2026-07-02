<?php

declare(strict_types=1);

namespace Nova\Middleware;

use Nova\Http\Request;

/**
 * Middleware that restores remember-me authentication.
 */
final class RestoreRememberedUser
{
    public function handle(Request $request, callable $next): mixed
    {
        app()->auth()->restoreFromRememberCookie();

        return $next($request);
    }
}
