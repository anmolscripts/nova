<?php

declare(strict_types=1);

namespace Nova\Storage;

use Nova\Application\Application;
use Nova\Storage\Contracts\StorageDriver;
use Nova\Storage\Drivers\LocalDriver;
use Nova\Storage\Drivers\MemoryDriver;
use Nova\Storage\Drivers\PublicDriver;

/**
 * Resolves configured storage disks.
 */
final class StorageManager
{
    private array $disks = [];

    public function __construct(private readonly Application $app)
    {
    }

    public function disk(?string $name = null): Disk
    {
        $name ??= (string) $this->app->config()->get('storage.default', 'local');

        if (!isset($this->disks[$name])) {
            $this->disks[$name] = new Disk($name, $this->driver($name));
        }

        return $this->disks[$name];
    }

    public function extend(string $name, StorageDriver $driver): void
    {
        $this->disks[$name] = new Disk($name, $driver);
    }

    private function driver(string $name): StorageDriver
    {
        $config = $this->app->config()->get("storage.disks.{$name}");
        if (!is_array($config)) {
            throw new StoragePathException("Storage disk [{$name}] is not configured.");
        }

        return match ($config['driver'] ?? 'local') {
            'local' => new LocalDriver((string) $config['root'], $config['url'] ?? null),
            'public' => new PublicDriver((string) $config['root'], $config['url'] ?? null),
            'memory' => new MemoryDriver(),
            default => $this->customDriver($name, $config),
        };
    }

    private function customDriver(string $name, array $config): StorageDriver
    {
        $class = $config['driver'] ?? null;
        if (!is_string($class) || !is_a($class, StorageDriver::class, true)) {
            throw new StoragePathException("Storage disk [{$name}] uses an unsupported driver.");
        }

        return new $class($config);
    }
}
