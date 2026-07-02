<?php

declare(strict_types=1);

namespace Nova\Routing;

/**
 * Describes an application route.
 */
final class Route
{
    public function __construct(
        public readonly string $name,
        public readonly string $path,
        public readonly string $regex,
        public readonly array $params,
        public readonly string $file,
        public readonly array $layouts,
        public readonly array $middleware = [],
        public readonly bool $api = false
    ) {
    }

    public static function __set_state(array $data): self
    {
        return new self(
            $data['name'],
            $data['path'],
            $data['regex'],
            $data['params'],
            $data['file'],
            $data['layouts'],
            $data['middleware'] ?? [],
            $data['api'] ?? false
        );
    }
}
