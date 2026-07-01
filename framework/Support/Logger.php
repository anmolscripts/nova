<?php

declare(strict_types=1);

namespace Nova\Support;

final class Logger
{
    public function __construct(private readonly string $path)
    {
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('info', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('error', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->write('debug', $message, $context);
    }

    private function write(string $level, string $message, array $context): void
    {
        $directory = dirname($this->path);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $line = sprintf("[%s] %s: %s %s\n", date('c'), strtoupper($level), $message, $context ? json_encode($context) : '');
        file_put_contents($this->path, $line, FILE_APPEND);
    }
}
