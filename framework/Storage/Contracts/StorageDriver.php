<?php

declare(strict_types=1);

namespace Nova\Storage\Contracts;

/**
 * Defines the contract implemented by storage drivers.
 */
interface StorageDriver
{
    public function put(string $path, string $contents): bool;

    public function putFile(string $path, string $source): bool;

    public function get(string $path): string;

    public function exists(string $path): bool;

    public function delete(string $path): bool;

    public function copy(string $from, string $to): bool;

    public function move(string $from, string $to): bool;

    public function size(string $path): int;

    public function mime(string $path): string;

    public function lastModified(string $path): int;

    public function path(string $path): string;

    public function url(string $path): ?string;

    public function directories(string $path = ''): array;

    public function files(string $path = ''): array;
}
