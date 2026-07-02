<?php

declare(strict_types=1);

namespace Nova\Storage\Drivers;

use Nova\Storage\Contracts\StorageDriver;
use Nova\Storage\PathNormalizer;
use Nova\Storage\StorageException;

/**
 * Stores files in memory for tests.
 */
final class MemoryDriver implements StorageDriver
{
    private array $files = [];
    private array $modified = [];

    public function put(string $path, string $contents): bool
    {
        $path = PathNormalizer::normalize($path);
        $this->files[$path] = $contents;
        $this->modified[$path] = time();

        return true;
    }

    public function putFile(string $path, string $source): bool
    {
        return $this->put($path, (string) file_get_contents($source));
    }

    public function get(string $path): string
    {
        $path = PathNormalizer::normalize($path);
        if (!array_key_exists($path, $this->files)) {
            throw new StorageException('File does not exist.');
        }

        return $this->files[$path];
    }

    public function exists(string $path): bool
    {
        return array_key_exists(PathNormalizer::normalize($path), $this->files);
    }

    public function delete(string $path): bool
    {
        $path = PathNormalizer::normalize($path);
        unset($this->files[$path], $this->modified[$path]);

        return true;
    }

    public function copy(string $from, string $to): bool
    {
        return $this->put($to, $this->get($from));
    }

    public function move(string $from, string $to): bool
    {
        $contents = $this->get($from);
        $this->delete($from);

        return $this->put($to, $contents);
    }

    public function size(string $path): int
    {
        return strlen($this->get($path));
    }

    public function mime(string $path): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        return $finfo->buffer($this->get($path)) ?: 'application/octet-stream';
    }

    public function lastModified(string $path): int
    {
        return $this->modified[PathNormalizer::normalize($path)] ?? 0;
    }

    public function path(string $path): string
    {
        return 'memory://' . PathNormalizer::normalize($path);
    }

    public function url(string $path): ?string
    {
        return null;
    }

    public function directories(string $path = ''): array
    {
        $prefix = PathNormalizer::normalize($path, true);
        $prefix = $prefix === '' ? '' : $prefix . '/';
        $directories = [];

        foreach (array_keys($this->files) as $file) {
            if (!str_starts_with($file, $prefix)) {
                continue;
            }
            $remaining = substr($file, strlen($prefix));
            if (str_contains($remaining, '/')) {
                $directories[] = $prefix . strtok($remaining, '/');
            }
        }

        return array_values(array_unique($directories));
    }

    public function files(string $path = ''): array
    {
        $prefix = PathNormalizer::normalize($path, true);
        $prefix = $prefix === '' ? '' : $prefix . '/';

        return array_values(array_filter(array_keys($this->files), static fn (string $file): bool => str_starts_with($file, $prefix) && !str_contains(substr($file, strlen($prefix)), '/')));
    }
}
