<?php

declare(strict_types=1);

return [
    'global' => [
        \Nova\Middleware\StartSession::class,
        \Nova\Middleware\RestoreRememberedUser::class,
        \Nova\Middleware\VerifyCsrfToken::class,
        \Nova\Middleware\PageMiddleware::class,
        \Nova\Middleware\DispatchServerAction::class,
    ],
];
