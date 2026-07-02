<?php

declare(strict_types=1);

namespace Nova\Component;

/**
 * Describes a discovered Nova component.
 */
final class Component
{
    public function __construct(
        public readonly string $name,
        public readonly string $directory,
        public readonly string $templateFile,
        public readonly ?string $serverFile,
        public readonly ?string $typescriptFile,
        public readonly ?string $scssFile,
        public readonly ?string $scope
    ) {
    }

    public static function __set_state(array $data): self
    {
        return new self(
            $data['name'],
            $data['directory'],
            $data['templateFile'],
            $data['serverFile'],
            $data['typescriptFile'],
            $data['scssFile'],
            $data['scope']
        );
    }
}
