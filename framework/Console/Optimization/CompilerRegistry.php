<?php

declare(strict_types=1);

namespace Nova\Console\Optimization;

final class CompilerRegistry
{
    /** @var array<int, CompilerInterface> */
    private array $compilers = [];

    public function register(CompilerInterface $compiler): void
    {
        $this->compilers[] = $compiler;
    }

    /** @return array<int, CompilerInterface> */
    public function all(): array
    {
        return $this->compilers;
    }
}
