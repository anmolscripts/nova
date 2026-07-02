<?php

declare(strict_types=1);

namespace Nova\Console\Optimization;

final class AssetCompiler implements CompilerInterface
{
    public function __construct(private readonly \Nova\Application\Application $app)
    {
    }

    public function name(): string
    {
        return 'asset';
    }

    public function compile(): array
    {
        $manifest = $this->app->config()->get('view.assets.manifest');
        $warnings = [];
        if (!is_string($manifest) || !is_file($manifest)) {
            $warnings[] = 'Asset manifest missing.';
        }

        return ['created' => 0, 'modified' => 0, 'warnings' => $warnings];
    }

    public function clear(): void
    {
    }

    public function status(): string
    {
        $manifest = $this->app->config()->get('view.assets.manifest');
        return is_string($manifest) && is_file($manifest) ? 'Generated' : 'Missing';
    }
}
