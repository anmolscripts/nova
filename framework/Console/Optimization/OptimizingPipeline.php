<?php

declare(strict_types=1);

namespace Nova\Console\Optimization;

use Nova\Application\Application;
use Nova\Config\ConfigLoader;
use Nova\Support\Profiler;

final class OptimizingPipeline
{
    /** @var array<int, CompilerInterface> */
    private array $compilers;

    public function __construct(private readonly Application $app, CompilerRegistry $registry)
    {
        $this->compilers = $registry->all();
    }

    public function run(): void
    {
        $startedAt = microtime(true);
        $startedMemory = memory_get_usage(true);
        $this->app->profiler()->start();
        $this->app->profiler()->record('optimizer.start');

        $warnings = $this->validateEnvironment();
        $results = [];
        foreach ($this->compilers as $compiler) {
            $this->app->profiler()->record('optimizer:' . $compiler->name());
            $results[] = $compiler->compile();
            $this->app->profiler()->record('optimizer:' . $compiler->name() . ':done');
        }

        $this->app->profiler()->record('optimizer.summary');
        $this->app->profiler()->finish();
        $this->report($results, $warnings, $startedAt, $startedMemory);
    }

    private function validateEnvironment(): array
    {
        $warnings = [];

        if (!version_compare(PHP_VERSION, '8.3.0', '>=')) {
            $warnings[] = 'PHP 8.3+ is required for production builds.';
        }

        foreach (['pdo', 'json', 'tokenizer'] as $extension) {
            if (!extension_loaded($extension)) {
                $warnings[] = 'Missing PHP extension: ' . $extension;
            }
        }

        if (!is_writable($this->app->storagePath())) {
            $warnings[] = 'Storage directory is not writable.';
        }

        $config = @ConfigLoader::loadDirectory($this->app->basePath('config'));
        if (!is_array($config)) {
            $warnings[] = 'Configuration files are invalid.';
        }

        return $warnings;
    }

    private function report(array $results, array $warnings, float $startedAt, int $startedMemory): void
    {
        $duration = round(microtime(true) - $startedAt, 6);
        $memory = memory_get_usage(true) - $startedMemory;

        $filesCreated = 0;
        $filesModified = 0;
        foreach ($results as $result) {
            $filesCreated += (int) ($result['created'] ?? 0);
            $filesModified += (int) ($result['modified'] ?? 0);
        }

        $allWarnings = $warnings;
        foreach ($results as $result) {
            foreach ((array) ($result['warnings'] ?? []) as $warning) {
                $allWarnings[] = $warning;
            }
        }

        echo PHP_EOL;
        echo 'Optimization Summary' . PHP_EOL;
        echo '====================' . PHP_EOL;
        echo 'Files Generated: ' . $filesCreated . PHP_EOL;
        echo 'Files Modified: ' . $filesModified . PHP_EOL;
        echo 'Time Taken: ' . $duration . 's' . PHP_EOL;
        echo 'Memory Used: ' . number_format($memory) . ' bytes' . PHP_EOL;
        if ($allWarnings !== []) {
            echo 'Warnings:' . PHP_EOL;
            foreach ($allWarnings as $warning) {
                echo ' - ' . $warning . PHP_EOL;
            }
        }
        echo PHP_EOL;
    }
}
