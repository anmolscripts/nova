<?php

declare(strict_types=1);

namespace Nova\Console\Optimization;

interface CompilerInterface
{
    public function name(): string;

    public function compile(): array;

    public function clear(): void;

    public function status(): string;
}
