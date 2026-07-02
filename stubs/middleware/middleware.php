<?php

declare(strict_types=1);

namespace App\Middleware;

use Nova\Http\Request;

final class {{ class }}
{
    public function handle(Request $request, callable $next): mixed
    {
        return $next($request);
    }
}
