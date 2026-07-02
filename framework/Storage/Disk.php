<?php

declare(strict_types=1);

namespace Nova\Storage;

use Nova\Http\DownloadResponse;
use Nova\Storage\Contracts\StorageDriver;

/**
 * Exposes Nova storage operations for a configured disk.
 */
final class Disk
{
    public function __construct(private readonly string $name, private readonly StorageDriver $driver)
    {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function put(string $path, string $contents): bool
    {
        return $this->driver->put($path, $contents);
    }

    public function putFile(string $path, string $source): bool
    {
        return $this->driver->putFile($path, $source);
    }

    public function get(string $path): string
    {
        return $this->driver->get($path);
    }

    public function exists(string $path): bool
    {
        return $this->driver->exists($path);
    }

    public function delete(string $path): bool
    {
        return $this->driver->delete($path);
    }

    public function copy(string $from, string $to): bool
    {
        return $this->driver->copy($from, $to);
    }

    public function move(string $from, string $to): bool
    {
        return $this->driver->move($from, $to);
    }

    public function size(string $path): int
    {
        return $this->driver->size($path);
    }

    public function mime(string $path): string
    {
        return $this->driver->mime($path);
    }

    public function lastModified(string $path): int
    {
        return $this->driver->lastModified($path);
    }

    public function path(string $path): string
    {
        return $this->driver->path($path);
    }

    public function url(string $path): string
    {
        return $this->driver->url($path) ?? throw new StorageException("Disk [{$this->name}] does not expose public URLs.");
    }

    public function temporaryUrl(string $path, int|string|\DateTimeInterface $expires = '+5 minutes'): string
    {
        $timestamp = $expires instanceof \DateTimeInterface ? $expires->getTimestamp() : (is_int($expires) ? $expires : strtotime($expires));
        $url = $this->url($path);
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . 'expires=' . $timestamp . '&signature=' . hash_hmac('sha256', PathNormalizer::normalize($path) . '|' . $timestamp, (string) config('app.key', 'nova'));
    }

    public function download(string $path, ?string $name = null, bool $inline = false): DownloadResponse
    {
        return new DownloadResponse($this, $path, $name, $inline);
    }

    public function directories(string $path = ''): array
    {
        return $this->driver->directories($path);
    }

    public function files(string $path = ''): array
    {
        return $this->driver->files($path);
    }
}
