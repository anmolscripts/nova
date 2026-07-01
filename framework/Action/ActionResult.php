<?php

declare(strict_types=1);

namespace Nova\Action;

final class ActionResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly mixed $data = [],
        public readonly int $status = 200,
        public readonly ?string $message = null,
        public readonly ?string $redirectTo = null
    ) {
    }
}
