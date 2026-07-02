<?php

declare(strict_types=1);

namespace Nova\Config;

/**
 * Loads Nova configuration arrays from disk.
 */
final class ConfigLoader
{
    public static function load(string $basePath, string $storagePath): array
    {
        $cached = $storagePath . DIRECTORY_SEPARATOR . 'framework/config/config.php';
        if (is_file($cached)) {
            return require $cached;
        }

        return self::loadDirectory($basePath . DIRECTORY_SEPARATOR . 'config');
    }

    public static function loadDirectory(string $directory): array
    {
        $items = [];
        foreach (glob(rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
            $items[basename($file, '.php')] = require $file;
        }

        return $items;
    }
}
