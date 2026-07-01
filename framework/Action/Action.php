<?php

declare(strict_types=1);

namespace Nova\Action;

final class Action
{
    public function __construct(
        public readonly string $uri,
        public readonly string $file,
        public readonly string $pageDirectory,
        public readonly string $name
    ) {
    }
}
