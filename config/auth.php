<?php

declare(strict_types=1);

return [
    'driver' => 'session',
    'login' => '/login',
    'redirect' => '/',
    'provider' => [
        'driver' => 'database',
        'connection' => null,
        'table' => 'users',
        'identifier' => 'email',
        'id' => 'id',
        'password' => 'password',
        'remember_token' => 'remember_token',
        'class' => null,
    ],
    'remember' => [
        'enabled' => true,
        'cookie' => 'nova_remember',
        'lifetime' => 60 * 60 * 24 * 30,
        'secure' => false,
        'http_only' => true,
        'same_site' => 'Lax',
    ],
];
