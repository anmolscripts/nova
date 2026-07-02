<?php

declare(strict_types=1);

namespace Nova\Config;

use Nova\Support\Arr;

/**
 * Stores loaded configuration values.
 */
final class Repository
{
    public function __construct(private array $items)
    {
    }

    public function all(): array
    {
        return $this->items;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->items, $key, $default);
    }

    public function set(string $key, mixed $value): void
    {
        Arr::set($this->items, $key, $value);
    }
}
