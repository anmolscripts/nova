<?php

declare(strict_types=1);

namespace Nova\Middleware;

use Nova\Action\ActionDiscovery;
use Nova\Action\ActionEngine;
use Nova\Http\Request;

/**
 * Middleware that dispatches Nova server actions.
 */
final class DispatchServerAction
{
    public function handle(Request $request, callable $next): mixed
    {
        $action = app()->make(ActionDiscovery::class)->resolve($request->path());
        if ($action === null) {
            return $next($request);
        }

        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            abort(405, 'Action method not allowed.');
        }

        return app()->make(ActionEngine::class)->handle($action, $request);
    }
}
