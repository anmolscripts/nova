<?php

declare(strict_types=1);

namespace Nova\Support;

use Nova\Application\Application;
use Nova\Support\Profile;

/**
 * Collects lightweight request lifecycle timing and memory data.
 */
final class Profiler
{
    private Profile $profile;

    public function __construct(private readonly Application $app)
    {
        $this->profile = new Profile();
    }

    public function start(): void
    {
        $this->profile->start();
    }

    public function profile(): Profile
    {
        return $this->profile;
    }

    public function record(string $name, ?array $context = null): void
    {
        $this->profile->record($name, $context);
    }

    public function begin(string $name, ?array $context = null): string
    {
        return $this->profile->begin($name, $context);
    }

    public function end(string $id): void
    {
        $this->profile->end($id);
    }

    public function finish(): Profile
    {
        $this->profile->finish();
        return $this->profile;
    }
}
