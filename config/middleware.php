<?php

declare(strict_types=1);

return [
    'global' => [
        \Nova\Middleware\StartSession::class,
        \Nova\Middleware\VerifyCsrfToken::class,
    ],
];
