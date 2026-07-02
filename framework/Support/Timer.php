<?php

declare(strict_types=1);

namespace Nova\Support;

/**
 * Lightweight timer helper for profiling.
 */
final class Timer
{
    public function start(): float
    {
        return microtime(true);
    }

    public function stop(float $startedAt): float
    {
        return round(microtime(true) - $startedAt, 6);
    }
}
