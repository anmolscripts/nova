<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'Nova'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost:8000'),
    'timezone' => 'UTC',
    'providers' => [],
    'optimize' => [
        'compilers' => [
            \Nova\Console\Optimization\ConfigCompiler::class,
            \Nova\Console\Optimization\RouteCompiler::class,
            \Nova\Console\Optimization\PageCompiler::class,
            \Nova\Console\Optimization\LayoutCompiler::class,
            \Nova\Console\Optimization\ComponentCompiler::class,
            \Nova\Console\Optimization\ViewCompiler::class,
            \Nova\Console\Optimization\AssetCompiler::class,
        ],
    ],
];
