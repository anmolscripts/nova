<?php

declare(strict_types=1);

namespace Nova\Application;

use Nova\Config\Repository as ConfigRepository;
use Nova\Container\Container;
use Nova\Database\DatabaseManager;
use Nova\Http\Request;
use Nova\Routing\Router;
use Nova\Security\AuthManager;
use Nova\Security\Csrf;
use Nova\Session\SessionManager;
use Nova\Support\Cache;
use Nova\Support\Logger;
use Nova\View\ViewFactory;

final class Application extends Container
{
    private ConfigRepository $config;
    private ?Request $request = null;

    public function __construct(private readonly string $basePath)
    {
        $this->instance(self::class, $this);
        $this->instance(Container::class, $this);
    }

    public function bootstrap(): void
    {
        $this->loadEnvironment();
        date_default_timezone_set((string) env('APP_TIMEZONE', 'UTC'));

        $this->config = new ConfigRepository($this->loadConfig());
        date_default_timezone_set((string) $this->config->get('app.timezone', 'UTC'));

        $this->singleton(ConfigRepository::class, fn () => $this->config);
        $this->singleton(Router::class, fn () => new Router($this));
        $this->singleton(ViewFactory::class, fn () => new ViewFactory($this));
        $this->singleton(SessionManager::class, fn () => new SessionManager($this->config->get('session', []), $this->storagePath('sessions')));
        $this->singleton(Csrf::class, fn () => new Csrf($this->session()));
        $this->singleton(DatabaseManager::class, fn () => new DatabaseManager($this->config->get('database', [])));
        $this->singleton(AuthManager::class, fn () => new AuthManager($this));
        $this->singleton(Cache::class, fn () => new Cache($this->config->get('cache.path', $this->storagePath('cache'))));
    }

    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }

    public function storagePath(string $path = ''): string
    {
        return $this->basePath('storage' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : ''));
    }

    public function config(): ConfigRepository
    {
        return $this->config;
    }

    public function setRequest(Request $request): void
    {
        $this->request = $request;
        $this->instance(Request::class, $request);
    }

    public function request(): Request
    {
        return $this->request ?? Request::capture();
    }

    public function view(): ViewFactory
    {
        return $this->make(ViewFactory::class);
    }

    public function session(): SessionManager
    {
        return $this->make(SessionManager::class);
    }

    public function csrf(): Csrf
    {
        return $this->make(Csrf::class);
    }

    public function auth(): AuthManager
    {
        return $this->make(AuthManager::class);
    }

    public function cache(): Cache
    {
        return $this->make(Cache::class);
    }

    public function logger(?string $channel = null): Logger
    {
        return new Logger($this->storagePath('logs/' . ($channel ?: 'app') . '.log'));
    }

    private function loadEnvironment(): void
    {
        $file = $this->basePath('.env');
        if (!is_file($file)) {
            return;
        }

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv($key . '=' . $value);
        }
    }

    private function loadConfig(): array
    {
        $cached = $this->storagePath('framework/config/config.php');
        if (is_file($cached)) {
            return require $cached;
        }

        $items = [];
        foreach (glob($this->basePath('config/*.php')) ?: [] as $file) {
            $items[basename($file, '.php')] = require $file;
        }

        return $items;
    }
}
