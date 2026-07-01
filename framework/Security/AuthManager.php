<?php

declare(strict_types=1);

namespace Nova\Security;

use Nova\Application\Application;

final class AuthManager
{
    public function __construct(private readonly Application $app)
    {
    }

    public function check(): bool
    {
        return $this->id() !== null;
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function id(): mixed
    {
        return $this->app->session()->get('_auth_id');
    }

    public function user(): mixed
    {
        return $this->app->session()->get('_auth_user');
    }

    public function login(mixed $user, mixed $id = null): void
    {
        $this->app->session()->regenerate();
        $this->app->session()->put('_auth_user', $user);
        $this->app->session()->put('_auth_id', $id ?? (is_array($user) ? ($user['id'] ?? null) : ($user->id ?? null)));
    }

    public function logout(): void
    {
        $this->app->session()->forget('_auth_user');
        $this->app->session()->forget('_auth_id');
        $this->app->session()->regenerate();
    }
}
