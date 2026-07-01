<?php

declare(strict_types=1);

namespace Nova\Session;

final class SessionManager
{
    public function __construct(private readonly array $config, private readonly string $path)
    {
    }

    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        if (!is_dir($this->path)) {
            mkdir($this->path, 0777, true);
        }

        session_save_path($this->path);
        session_name((string) ($this->config['name'] ?? 'nova_session'));
        session_set_cookie_params([
            'lifetime' => ((int) ($this->config['lifetime'] ?? 120)) * 60,
            'path' => (string) ($this->config['path'] ?? '/'),
            'secure' => (bool) ($this->config['secure'] ?? false),
            'httponly' => (bool) ($this->config['http_only'] ?? true),
            'samesite' => (string) ($this->config['same_site'] ?? 'Lax'),
        ]);
        session_start();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public function flash(string $key, mixed $value = null): mixed
    {
        if (func_num_args() === 1) {
            return $_SESSION['_flash'][$key] ?? null;
        }

        $_SESSION['_flash_new'][$key] = $value;
        $_SESSION['_flash'][$key] = $value;
        return null;
    }

    public function ageFlashData(): void
    {
        $_SESSION['_flash'] = $_SESSION['_flash_new'] ?? [];
        $_SESSION['_flash_new'] = [];
    }
}
