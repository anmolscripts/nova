<?php

declare(strict_types=1);

return [
    'default' => env('STORAGE_DISK', 'local'),
    'temporary_lifetime' => 3600,
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => base_path('storage/app'),
        ],
        'public' => [
            'driver' => 'public',
            'root' => base_path('storage/public'),
            'url' => rtrim((string) env('APP_URL', 'http://localhost:8000'), '/') . '/storage',
        ],
        'temporary' => [
            'driver' => 'local',
            'root' => base_path('storage/temporary'),
        ],
        'uploads' => [
            'driver' => 'local',
            'root' => base_path('storage/uploads'),
        ],
        'memory' => [
            'driver' => 'memory',
        ],
    ],
];
