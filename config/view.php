<?php

declare(strict_types=1);

return [
    'paths' => [
        base_path('resources/views'),
        base_path('app'),
        base_path('components'),
    ],
    'cache' => base_path('storage/framework/views'),
    'layout' => 'layout.latte',
    'assets' => [
        'dev_server' => env('VITE_DEV_SERVER', 'http://127.0.0.1:5173'),
        'manifest' => base_path('public/assets/manifest.json'),
        'build_path' => 'assets',
        'entries' => [
            'app.ts' => 'resources/ts/app.ts',
            'app.scss' => 'resources/scss/app.scss',
        ],
    ],
];
