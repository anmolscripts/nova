<?php

declare(strict_types=1);

namespace Nova\Support;

/**
 * A single timed and memory-captured profile entry.
 */
final class ProfileEntry
{
    private ?float $finishedAt = null;
    private ?int $finishedMemory = null;

    public function __construct(
        private readonly string $name,
        private readonly float $startedAt,
        private readonly int $startedMemory,
        private readonly array $context = [],
        private readonly string $id = ''
    ) {
    }

    public function finish(float $finishedAt, int $finishedMemory): self
    {
        $this->finishedAt = $finishedAt;
        $this->finishedMemory = $finishedMemory;
        return $this;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'duration' => round(($this->finishedAt ?? $this->startedAt) - $this->startedAt, 6),
            'memory_start' => $this->startedMemory,
            'memory_end' => $this->finishedMemory ?? $this->startedMemory,
            'memory_delta' => ($this->finishedMemory ?? $this->startedMemory) - $this->startedMemory,
            'context' => $this->context,
        ];
    }
}
