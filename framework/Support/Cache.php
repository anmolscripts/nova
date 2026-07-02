<?php

declare(strict_types=1);

namespace Nova\Support;

/**
 * Provides simple file-backed cache storage.
 */
final class Cache
{
    public function __construct(private readonly string $path)
    {
        if (!is_dir($this->path)) {
            mkdir($this->path, 0777, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->file($key);
        if (!is_file($file)) {
            return $default;
        }

        $payload = unserialize((string) file_get_contents($file));
        if (($payload['expires'] ?? 0) !== 0 && $payload['expires'] < time()) {
            @unlink($file);
            return $default;
        }

        return $payload['value'] ?? $default;
    }

    public function put(string $key, mixed $value, int $seconds = 3600): void
    {
        file_put_contents($this->file($key), serialize([
            'expires' => $seconds > 0 ? time() + $seconds : 0,
            'value' => $value,
        ]));
    }

    public function forget(string $key): void
    {
        @unlink($this->file($key));
    }

    public function clear(): void
    {
        foreach (glob($this->path . '/*.cache') ?: [] as $file) {
            @unlink($file);
        }
    }

    private function file(string $key): string
    {
        return $this->path . DIRECTORY_SEPARATOR . sha1($key) . '.cache';
    }
}
