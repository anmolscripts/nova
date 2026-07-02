<?php

declare(strict_types=1);

namespace Nova\Storage\Drivers;

use Nova\Storage\Contracts\StorageDriver;
use Nova\Storage\PathNormalizer;
use Nova\Storage\StorageException;
use Nova\Storage\StoragePathException;

/**
 * Stores files on the local filesystem.
 */
class LocalDriver implements StorageDriver
{
    public function __construct(private readonly string $root, private readonly ?string $baseUrl = null)
    {
        if (!is_dir($this->root)) {
            mkdir($this->root, 0775, true);
        }
    }

    public function put(string $path, string $contents): bool
    {
        $target = $this->path($path);
        $this->ensureDirectory(dirname($target));

        return file_put_contents($target, $contents) !== false;
    }

    public function putFile(string $path, string $source): bool
    {
        if (!is_file($source)) {
            throw new StorageException('Source file does not exist.');
        }

        $target = $this->path($path);
        $this->ensureDirectory(dirname($target));

        if (is_uploaded_file($source)) {
            return move_uploaded_file($source, $target);
        }

        if (rename($source, $target)) {
            return true;
        }

        if (!copy($source, $target)) {
            return false;
        }

        @unlink($source);
        return true;
    }

    public function get(string $path): string
    {
        $file = $this->path($path);
        if (!is_file($file)) {
            throw new StorageException('File does not exist.');
        }

        return (string) file_get_contents($file);
    }

    public function exists(string $path): bool
    {
        return is_file($this->path($path));
    }

    public function delete(string $path): bool
    {
        $file = $this->path($path);

        return !is_file($file) || unlink($file);
    }

    public function copy(string $from, string $to): bool
    {
        $target = $this->path($to);
        $this->ensureDirectory(dirname($target));

        return copy($this->path($from), $target);
    }

    public function move(string $from, string $to): bool
    {
        $target = $this->path($to);
        $this->ensureDirectory(dirname($target));

        return rename($this->path($from), $target);
    }

    public function size(string $path): int
    {
        return filesize($this->path($path)) ?: 0;
    }

    public function mime(string $path): string
    {
        $file = $this->path($path);
        $mime = function_exists('mime_content_type') ? mime_content_type($file) : false;

        return $mime ?: 'application/octet-stream';
    }

    public function lastModified(string $path): int
    {
        return filemtime($this->path($path)) ?: 0;
    }

    public function path(string $path): string
    {
        $path = PathNormalizer::normalize($path);
        $root = rtrim($this->root, '/\\');
        $fullPath = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        $directory = dirname($fullPath);
        $realDirectory = realpath(is_dir($directory) ? $directory : $this->nearestExistingDirectory($directory));
        $realRoot = realpath($root);

        $realRoot = $realRoot === false ? false : rtrim($realRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $realDirectory = $realDirectory === false ? false : rtrim($realDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if ($realRoot === false || $realDirectory === false || !str_starts_with($realDirectory, $realRoot)) {
            throw new StoragePathException('Storage path escapes the disk root.');
        }

        return $fullPath;
    }

    public function url(string $path): ?string
    {
        if ($this->baseUrl === null) {
            return null;
        }

        return rtrim($this->baseUrl, '/') . '/' . PathNormalizer::normalize($path);
    }

    public function directories(string $path = ''): array
    {
        $directory = $this->directoryPath($path);
        $items = [];

        foreach (glob($directory . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [] as $item) {
            $items[] = $this->relativePath($item);
        }

        sort($items);
        return $items;
    }

    public function files(string $path = ''): array
    {
        $directory = $this->directoryPath($path);
        $items = [];

        foreach (glob($directory . DIRECTORY_SEPARATOR . '*') ?: [] as $item) {
            if (is_file($item)) {
                $items[] = $this->relativePath($item);
            }
        }

        sort($items);
        return $items;
    }

    private function directoryPath(string $path): string
    {
        $path = PathNormalizer::normalize($path, true);
        $directory = $path === '' ? rtrim($this->root, '/\\') : $this->path($path);

        return is_dir($directory) ? $directory : dirname($directory . DIRECTORY_SEPARATOR . '.');
    }

    private function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
    }

    private function nearestExistingDirectory(string $directory): string
    {
        while (!is_dir($directory)) {
            $parent = dirname($directory);
            if ($parent === $directory) {
                break;
            }
            $directory = $parent;
        }

        return $directory;
    }

    private function relativePath(string $path): string
    {
        return str_replace('\\', '/', ltrim(substr($path, strlen(rtrim($this->root, '/\\'))), '/\\'));
    }
}
