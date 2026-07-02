<?php

declare(strict_types=1);

namespace Nova\Security;

use Nova\Application\Application;

/**
 * Manages authentication state for the current request.
 */
final class AuthManager
{
    private ?array $user = null;
    private ?UserProvider $provider = null;

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
        $this->restoreFromRememberCookie();

        return $this->app->session()->get('_auth_id');
    }

    public function user(): ?array
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $id = $this->id();
        if ($id === null) {
            return null;
        }

        return $this->user = $this->provider()->retrieveById($id);
    }

    public function attempt(array $credentials, bool $remember = false): bool
    {
        $user = $this->provider()->retrieveByCredentials($credentials);
        if (!$user || !$this->provider()->validateCredentials($user, $credentials)) {
            return false;
        }

        if (isset($credentials['password']) && is_string($credentials['password'])) {
            $this->provider()->rehashPasswordIfNeeded($user, $credentials['password']);
        }

        $this->login($user[$this->idField()] ?? null, $remember);

        return true;
    }

    public function login(mixed $id, bool $remember = false): void
    {
        if ($id === null) {
            throw new AuthenticationException('Authenticated user id cannot be null.');
        }

        $this->app->session()->regenerate();
        $this->app->session()->put('_auth_id', $id);
        $this->app->session()->put('_auth_at', time());
        $this->user = null;

        if ($remember) {
            $this->remember($id);
        }
    }

    public function logout(): void
    {
        $this->app->session()->forget('_auth_id');
        $this->app->session()->forget('_auth_at');
        $this->user = null;
        $this->forgetRememberCookie();
        $this->app->session()->regenerate();
    }

    public function provider(): UserProvider
    {
        if ($this->provider !== null) {
            return $this->provider;
        }

        $config = $this->app->config()->get('auth.provider', []);
        $class = $config['class'] ?? null;

        if (is_string($class) && class_exists($class)) {
            $provider = new $class($this->app, $config);
            if (!$provider instanceof UserProvider) {
                throw new AuthenticationException("Auth provider [{$class}] must implement " . UserProvider::class);
            }

            return $this->provider = $provider;
        }

        return $this->provider = new DatabaseUserProvider($this->app, $config);
    }

    private function remember(mixed $id): void
    {
        if (!$this->app->config()->get('auth.remember.enabled', true)) {
            return;
        }

        $token = bin2hex(random_bytes(32));
        $this->provider()->updateRememberToken($id, $token);
        $cookie = base64_encode((string) $id . '|' . $token);

        setcookie($this->rememberCookieName(), $cookie, [
            'expires' => time() + (int) $this->app->config()->get('auth.remember.lifetime', 2592000),
            'path' => '/',
            'secure' => (bool) $this->app->config()->get('auth.remember.secure', false),
            'httponly' => (bool) $this->app->config()->get('auth.remember.http_only', true),
            'samesite' => (string) $this->app->config()->get('auth.remember.same_site', 'Lax'),
        ]);
    }

    public function restoreFromRememberCookie(): void
    {
        if ($this->app->session()->get('_auth_id') !== null) {
            return;
        }

        $cookie = $_COOKIE[$this->rememberCookieName()] ?? null;
        if (!is_string($cookie)) {
            return;
        }

        $decoded = base64_decode($cookie, true);
        if (!is_string($decoded) || !str_contains($decoded, '|')) {
            return;
        }

        [$id, $token] = explode('|', $decoded, 2);
        if (!$this->provider()->retrieveByRememberToken($id, $token)) {
            return;
        }

        $this->app->session()->put('_auth_id', $id);
        $this->app->session()->put('_auth_at', time());
    }

    private function forgetRememberCookie(): void
    {
        setcookie($this->rememberCookieName(), '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => (bool) $this->app->config()->get('auth.remember.secure', false),
            'httponly' => (bool) $this->app->config()->get('auth.remember.http_only', true),
            'samesite' => (string) $this->app->config()->get('auth.remember.same_site', 'Lax'),
        ]);
    }

    private function rememberCookieName(): string
    {
        return (string) $this->app->config()->get('auth.remember.cookie', 'nova_remember');
    }

    private function idField(): string
    {
        return (string) $this->app->config()->get('auth.provider.id', 'id');
    }
}
