<?php

declare(strict_types=1);

namespace Nova\Application;

use Nova\App\Page;
use Nova\App\PageDiscovery;
use Nova\App\PageRenderer;
use Nova\App\Layout;
use Nova\App\LayoutEngine;
use Nova\Component\Component;
use Nova\Component\ComponentDiscovery;
use Nova\Component\ComponentEngine;
use Nova\Config\ConfigLoader;
use Nova\Config\Repository as ConfigRepository;
use Nova\Container\Container;
use Nova\Database\DatabaseManager;
use Nova\Http\Request;
use Nova\Routing\Router;
use Nova\Security\AuthManager;
use Nova\Security\Csrf;
use Nova\Session\SessionManager;
use Nova\Storage\StorageManager;
use Nova\Support\Cache;
use Nova\Support\Logger;
use Nova\Support\Profiler;
use Nova\View\ViewFactory;
use Nova\View\Asset;
use Nova\View\LatteEngine;

/**
 * Bootstraps and exposes the Nova application container.
 */
final class Application extends Container
{
    private ConfigRepository $config;
    private ?Request $request = null;
    private ?Page $currentPage = null;
    private ?Layout $currentLayout = null;
    private ?Component $currentComponent = null;

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
        $this->singleton(Profiler::class, fn () => new Profiler($this));
        $this->singleton(PageDiscovery::class, fn () => new PageDiscovery($this));
        $this->singleton(LayoutEngine::class, fn () => new LayoutEngine($this));
        $this->singleton(PageRenderer::class, fn () => new PageRenderer($this));
        $this->singleton(ComponentDiscovery::class, fn () => new ComponentDiscovery($this));
        $this->singleton(ComponentEngine::class, fn () => new ComponentEngine($this));
        $this->singleton(Router::class, fn () => new Router($this));
        $this->singleton(Asset::class, fn () => new Asset($this));
        $this->singleton(LatteEngine::class, fn () => new LatteEngine($this, $this->make(Asset::class)));
        $this->singleton(ViewFactory::class, fn () => new ViewFactory($this));
        $this->singleton(SessionManager::class, fn () => new SessionManager($this->config->get('session', []), $this->storagePath('sessions')));
        $this->singleton(Csrf::class, fn () => new Csrf($this->session()));
        $this->singleton(DatabaseManager::class, fn () => new DatabaseManager($this->config->get('database', [])));
        $this->singleton(AuthManager::class, fn () => new AuthManager($this));
        $this->singleton(Cache::class, fn () => new Cache($this->config->get('cache.path', $this->storagePath('cache'))));
        $this->singleton(StorageManager::class, fn () => new StorageManager($this));
        $this->ensureStorageDirectories();
        $this->cleanupTemporaryStorage();
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

    public function setCurrentPage(Page $page): void
    {
        $this->currentPage = $page;
    }

    public function currentPage(): ?Page
    {
        return $this->currentPage;
    }

    public function setCurrentLayout(Layout $layout): void
    {
        $this->currentLayout = $layout;
    }

    public function currentLayout(): ?Layout
    {
        return $this->currentLayout;
    }

    public function setCurrentComponent(?Component $component): void
    {
        $this->currentComponent = $component;
    }

    public function currentComponent(): ?Component
    {
        return $this->currentComponent;
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

    public function storage(): StorageManager
    {
        return $this->make(StorageManager::class);
    }

    public function logger(?string $channel = null): Logger
    {
        return new Logger($this->storagePath('logs/' . ($channel ?: 'app') . '.log'));
    }

    public function profiler(): Profiler
    {
        return $this->make(Profiler::class);
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

    private function ensureStorageDirectories(): void
    {
        foreach (['app', 'public', 'temporary', 'uploads'] as $directory) {
            $path = $this->storagePath($directory);
            if (!is_dir($path)) {
                mkdir($path, 0775, true);
            }
        }

        $publicStorage = $this->basePath('public/storage');
        if (!is_dir($publicStorage)) {
            $target = $this->storagePath('public');
            if (!@symlink($target, $publicStorage) && !is_dir($publicStorage)) {
                mkdir($publicStorage, 0775, true);
            }
        }
    }

    private function cleanupTemporaryStorage(): void
    {
        $lifetime = (int) $this->config->get('storage.temporary_lifetime', 3600);
        $cutoff = time() - max(60, $lifetime);

        foreach (glob($this->storagePath('temporary/*')) ?: [] as $file) {
            if (is_file($file) && (filemtime($file) ?: time()) < $cutoff) {
                @unlink($file);
            }
        }
    }

    private function loadConfig(): array
    {
        return ConfigLoader::load($this->basePath(), $this->storagePath());
    }
}
