<?php

declare(strict_types=1);

namespace Nova\Storage;

/**
 * Normalizes and validates storage paths.
 */
final class PathNormalizer
{
    public static function normalize(string $path, bool $allowEmpty = false): string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = preg_replace('#/+#', '/', $path) ?: '';
        $path = trim($path, '/');

        if ($path === '') {
            if ($allowEmpty) {
                return '';
            }
            throw new StoragePathException('Storage path cannot be empty.');
        }

        $segments = explode('/', $path);
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..' || str_contains($segment, "\0")) {
                throw new StoragePathException('Storage path contains unsafe segments.');
            }
        }

        return implode('/', $segments);
    }

    public static function filename(string $name): string
    {
        $name = basename(str_replace('\\', '/', trim($name)));
        $name = preg_replace('/[^A-Za-z0-9._-]/', '-', $name) ?: '';
        $name = trim($name, '.-');

        if ($name === '' || $name === '.' || $name === '..' || str_contains($name, "\0")) {
            throw new StoragePathException('Invalid filename.');
        }

        return $name;
    }
}
