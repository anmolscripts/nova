<?php

declare(strict_types=1);

namespace Nova\Middleware;

use Nova\Http\Request;

final class StartSession
{
    public function handle(Request $request, callable $next): mixed
    {
        app()->session()->start();
        $response = $next($request);
        app()->session()->ageFlashData();
        return $response;
    }
}
