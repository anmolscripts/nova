<?php

declare(strict_types=1);

namespace Nova\App;

/**
 * Describes a discovered Nova page.
 */
final class Page
{
    public function __construct(
        public readonly string $uri,
        public readonly string $directory,
        public readonly ?string $serverFile,
        public readonly ?string $templateFile,
        public readonly array $layoutFiles,
        public readonly ?string $typescriptFile,
        public readonly ?string $scssFile,
        public readonly ?string $loadingFile,
        public readonly ?string $errorFile,
        public readonly array $routeParameters,
        public readonly string $regex
    ) {
    }

    public static function __set_state(array $data): self
    {
        return new self(
            $data['uri'],
            $data['directory'],
            $data['serverFile'],
            $data['templateFile'],
            $data['layoutFiles'],
            $data['typescriptFile'],
            $data['scssFile'],
            $data['loadingFile'],
            $data['errorFile'],
            $data['routeParameters'],
            $data['regex']
        );
    }
}
