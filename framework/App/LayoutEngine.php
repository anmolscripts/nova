<?php

declare(strict_types=1);

namespace Nova\App;

use Nova\Application\Application;
use Nova\View\LatteEngine;

final class LayoutEngine
{
    public function __construct(private readonly Application $app)
    {
    }

    public function render(Page $page, string $content, array $data): string
    {
        if ($page->layoutFiles === []) {
            return $content;
        }

        foreach (array_reverse($page->layoutFiles) as $index => $layoutFile) {
            $layout = new Layout(
                file: $layoutFile,
                directory: dirname($layoutFile),
                depth: count($page->layoutFiles) - $index,
                page: $page
            );

            $this->app->setCurrentLayout($layout);
            $content = $this->renderLayout($layout, $page, $content, $data);
        }

        return $content;
    }

    private function renderLayout(Layout $layout, Page $page, string $content, array $data): string
    {
        try {
            return $this->app->make(LatteEngine::class)->render($layout->file, array_merge($data, [
                'content' => $content,
                'page' => $this->pagePayload($page),
                'currentPage' => $page,
                'route' => $this->app->request()->routeParams(),
                'config' => $this->app->config()->all(),
            ]));
        } catch (\Throwable $exception) {
            throw new LayoutRenderException(
                'Unable to render layout [' . $layout->file . ']: ' . $exception->getMessage(),
                previous: $exception
            );
        }
    }

    private function pagePayload(Page $page): array
    {
        return [
            'uri' => $page->uri,
            'directory' => $page->directory,
            'assets' => array_values(array_filter([
                $page->typescriptFile,
                $page->scssFile,
            ])),
            'routeParameters' => $page->routeParameters,
            'template' => $page->templateFile,
        ];
    }
}
