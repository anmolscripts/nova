<?php

declare(strict_types=1);

namespace Nova\Support;

use Nova\Support\ProfileEntry;

/**
 * Represents a single request profile with timing and memory metrics.
 */
final class Profile
{
    /** @var array<int, ProfileEntry> */
    private array $entries = [];
    private float $startedAt;
    private float $finishedAt;
    private int $initialMemory;
    private int $peakMemory;
    private int $finalMemory;
    private int $queryCount = 0;
    private float $queryTime = 0.0;
    private int $cacheHits = 0;
    private int $cacheMisses = 0;
    private int $cacheRebuilds = 0;
    private int $manifestReads = 0;
    private int $manifestWrites = 0;
    private int $filesystemScans = 0;
    private int $viewCompilations = 0;
    private int $assetManifestReads = 0;

    public function __construct()
    {
        $this->startedAt = microtime(true);
        $this->initialMemory = memory_get_usage(true);
        $this->peakMemory = $this->initialMemory;
        $this->finalMemory = $this->initialMemory;
    }

    public function start(): void
    {
        $this->startedAt = microtime(true);
        $this->initialMemory = memory_get_usage(true);
        $this->peakMemory = $this->initialMemory;
        $this->finalMemory = $this->initialMemory;
    }

    public function record(string $name, ?array $context = null): void
    {
        $this->entries[] = new ProfileEntry($name, microtime(true), memory_get_usage(true), $context ?? []);
        $this->peakMemory = max($this->peakMemory, memory_get_usage(true));
    }

    public function begin(string $name, ?array $context = null): string
    {
        $id = 'entry-' . count($this->entries);
        $this->entries[] = new ProfileEntry($name, microtime(true), memory_get_usage(true), $context ?? [], $id);
        $this->peakMemory = max($this->peakMemory, memory_get_usage(true));
        return $id;
    }

    public function end(string $id): void
    {
        foreach ($this->entries as $index => $entry) {
            if ($entry->id() === $id) {
                $this->entries[$index] = $entry->finish(microtime(true), memory_get_usage(true));
                $this->peakMemory = max($this->peakMemory, memory_get_usage(true));
                return;
            }
        }
    }

    public function finish(): void
    {
        $this->finishedAt = microtime(true);
        $this->finalMemory = memory_get_usage(true);
        $this->peakMemory = max($this->peakMemory, $this->finalMemory);
    }

    public function entries(): array
    {
        return $this->entries;
    }

    public function duration(): float
    {
        return round(($this->finishedAt ?? microtime(true)) - $this->startedAt, 6);
    }

    public function memoryDelta(): int
    {
        return $this->finalMemory - $this->initialMemory;
    }

    public function initialMemory(): int
    {
        return $this->initialMemory;
    }

    public function peakMemory(): int
    {
        return $this->peakMemory;
    }

    public function finalMemory(): int
    {
        return $this->finalMemory;
    }

    public function incrementQueryCount(float $time = 0.0): void
    {
        $this->queryCount++;
        $this->queryTime += $time;
    }

    public function incrementCacheHit(): void
    {
        $this->cacheHits++;
    }

    public function incrementCacheMiss(): void
    {
        $this->cacheMisses++;
    }

    public function incrementCacheRebuild(): void
    {
        $this->cacheRebuilds++;
    }

    public function incrementManifestRead(): void
    {
        $this->manifestReads++;
    }

    public function incrementManifestWrite(): void
    {
        $this->manifestWrites++;
    }

    public function incrementFilesystemScan(): void
    {
        $this->filesystemScans++;
    }

    public function incrementViewCompilation(): void
    {
        $this->viewCompilations++;
    }

    public function incrementAssetManifestRead(): void
    {
        $this->assetManifestReads++;
    }

    public function toArray(): array
    {
        return [
            'duration' => $this->duration(),
            'memory' => [
                'initial' => $this->initialMemory(),
                'peak' => $this->peakMemory(),
                'final' => $this->finalMemory(),
                'delta' => $this->memoryDelta(),
            ],
            'queries' => [
                'count' => $this->queryCount,
                'time' => round($this->queryTime, 6),
            ],
            'cache' => [
                'hits' => $this->cacheHits,
                'misses' => $this->cacheMisses,
                'rebuilds' => $this->cacheRebuilds,
            ],
            'filesystem' => [
                'manifest_reads' => $this->manifestReads,
                'manifest_writes' => $this->manifestWrites,
                'scans' => $this->filesystemScans,
                'view_compilations' => $this->viewCompilations,
                'asset_manifest_reads' => $this->assetManifestReads,
            ],
            'entries' => array_map(static fn (ProfileEntry $entry): array => $entry->toArray(), $this->entries()),
        ];
    }
}
