<?php

declare(strict_types=1);

namespace Nova\App;

/**
 * Describes a discovered page layout.
 */
final class Layout
{
    public function __construct(
        public readonly string $file,
        public readonly string $directory,
        public readonly int $depth,
        public readonly Page $page
    ) {
    }

    public function name(): string
    {
        return basename($this->directory);
    }
}
