<?php

declare(strict_types=1);

namespace Nova\Support;

/**
 * Tracks memory usage during a request profile.
 */
final class MemoryTracker
{
    public function usage(): int
    {
        return memory_get_usage(true);
    }
}
