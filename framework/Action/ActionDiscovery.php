<?php

declare(strict_types=1);

namespace Nova\Action;

use Nova\Application\Application;

final class ActionDiscovery
{
    public function __construct(private readonly Application $app)
    {
    }

    public function resolve(string $path): ?Action
    {
        $path = '/' . trim($path, '/');
        if (!preg_match('#^(?<page>/.*)?/actions/(?<name>[A-Za-z0-9_-]+)$#', $path, $matches)) {
            return null;
        }

        $pageUri = $matches['page'] ?: '/';
        $name = $matches['name'];
        $pageDirectory = $this->pageDirectory($pageUri);
        $file = $pageDirectory . DIRECTORY_SEPARATOR . 'actions' . DIRECTORY_SEPARATOR . $name . '.php';

        if (!is_file($file)) {
            return null;
        }

        return new Action($path, $file, $pageDirectory, $name);
    }

    private function pageDirectory(string $pageUri): string
    {
        $relative = trim($pageUri, '/');

        return $this->app->basePath('app' . ($relative === '' ? '' : DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative)));
    }
}
