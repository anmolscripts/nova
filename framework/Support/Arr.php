<?php

declare(strict_types=1);

namespace Nova\Support;

/**
 * Provides helpers for nested array access.
 */
final class Arr
{
    public static function get(array|object|null $target, string $key, mixed $default = null): mixed
    {
        if ($target === null) {
            return $default;
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($target) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
                continue;
            }
            if (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
                continue;
            }
            return $default;
        }

        return $target;
    }

    public static function set(array &$target, string $key, mixed $value): void
    {
        $cursor = &$target;
        foreach (explode('.', $key) as $segment) {
            if (!isset($cursor[$segment]) || !is_array($cursor[$segment])) {
                $cursor[$segment] = [];
            }
            $cursor = &$cursor[$segment];
        }
        $cursor = $value;
    }
}
