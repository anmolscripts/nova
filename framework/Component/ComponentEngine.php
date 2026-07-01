<?php

declare(strict_types=1);

namespace Nova\Component;

use Nova\Application\Application;
use Nova\View\Asset;
use Nova\View\LatteEngine;

final class ComponentEngine
{
    private array $emittedAssets = [];

    public function __construct(private readonly Application $app)
    {
    }

    public function renderFromArguments(array $arguments): string
    {
        $name = $arguments[0] ?? null;
        if (!is_string($name) || $name === '') {
            throw new ComponentException('Component name must be provided.');
        }

        unset($arguments[0]);

        return $this->render($name, $arguments);
    }

    public function render(string $name, array $props = []): string
    {
        $component = $this->app->make(ComponentDiscovery::class)->resolve($name);
        $previous = $this->app->currentComponent();
        $this->app->setCurrentComponent($component);

        try {
            $serverData = $this->serverData($component);
            $data = array_merge($serverData, $props, ['component' => $component]);
            $html = $this->app->make(LatteEngine::class)->render($component->templateFile, $data);

            return $this->assetTags($component, 'scss') . $html . $this->assetTags($component, 'ts');
        } finally {
            $this->app->setCurrentComponent($previous);
        }
    }

    private function serverData(Component $component): array
    {
        if ($component->serverFile === null) {
            return [];
        }

        $data = require $component->serverFile;
        if (!is_array($data)) {
            throw new ComponentException('Component server file must return an array.');
        }

        return $data;
    }

    private function assetTags(Component $component, string $type): string
    {
        $file = $type === 'scss' ? $component->scssFile : $component->typescriptFile;
        if ($file === null || isset($this->emittedAssets[$file])) {
            return '';
        }

        $this->emittedAssets[$file] = true;
        $relative = str_replace('\\', '/', substr($file, strlen($this->app->basePath()) + 1));

        return $this->app->make(Asset::class)->tags($relative);
    }
}
