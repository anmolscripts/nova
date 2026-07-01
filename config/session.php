<?php

declare(strict_types=1);

return [
    'name' => env('SESSION_NAME', 'nova_session'),
    'lifetime' => 120,
    'path' => '/',
    'secure' => false,
    'http_only' => true,
    'same_site' => 'Lax',
];
