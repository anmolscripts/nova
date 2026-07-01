<?php

declare(strict_types=1);

namespace Nova\App;

use Nova\Application\Application;
use Nova\Exceptions\HttpException;
use Nova\Http\Response;
use Nova\View\Asset;
use Nova\View\LatteEngine;

final class PageRenderer
{
    public function __construct(private readonly Application $app)
    {
    }

    public function render(Page $page, array $routeParameters): Response
    {
        $this->app->setCurrentPage($page);

        if ($page->serverFile === null) {
            throw new HttpException(404, 'Page server file not found.');
        }

        if ($page->templateFile === null) {
            throw new HttpException(500, 'Page template file not found.');
        }

        $data = require $page->serverFile;
        if (!is_array($data)) {
            throw new \RuntimeException('Page server file must return an array.');
        }

        $data = array_merge($routeParameters, $data);
        $content = $this->app->make(LatteEngine::class)->render($page->templateFile, $data);
        $content = $this->assetTags($page, 'scss') . $content . $this->assetTags($page, 'ts');

        $content = $this->app->make(LayoutEngine::class)->render($page, $content, $data);

        return response($content)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    private function assetTags(Page $page, string $type): string
    {
        $file = $type === 'scss' ? $page->scssFile : $page->typescriptFile;
        if ($file === null) {
            return '';
        }

        $relative = str_replace('\\', '/', substr($file, strlen($this->app->basePath()) + 1));
        return $this->app->make(Asset::class)->tags($relative);
    }
}
