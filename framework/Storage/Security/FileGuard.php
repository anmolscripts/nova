<?php

declare(strict_types=1);

namespace Nova\Storage\Security;

use Nova\Storage\StoragePathException;

/**
 * Validates upload filenames and blocked extensions.
 */
final class FileGuard
{
    private const BLOCKED_EXTENSIONS = [
        'php', 'phtml', 'php3', 'php4', 'php5', 'phar', 'cgi', 'pl', 'py', 'rb', 'sh',
        'bat', 'cmd', 'com', 'exe', 'dll', 'msi', 'jsp', 'asp', 'aspx',
    ];

    public static function assertSafeFilename(string $filename): void
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($extension !== '' && in_array($extension, self::BLOCKED_EXTENSIONS, true)) {
            throw new StoragePathException('Executable uploads are not allowed.');
        }
    }

    public static function extensionAllowed(string $filename, array $allowed = []): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($extension === '' || in_array($extension, self::BLOCKED_EXTENSIONS, true)) {
            return false;
        }

        return $allowed === [] || in_array($extension, array_map('strtolower', $allowed), true);
    }
}
