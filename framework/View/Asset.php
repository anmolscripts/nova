<?php

declare(strict_types=1);

namespace Nova\View;

use Nova\Application\Application;

final class Asset
{
    private ?bool $devServerAvailable = null;
    private ?array $manifest = null;

    public function __construct(private readonly Application $app)
    {
    }

    public function tags(string $entry): string
    {
        $source = $this->source($entry);

        if ($this->isDevServerAvailable()) {
            return $this->devTags($source);
        }

        return $this->productionTags($source);
    }

    public function url(string $entry): string
    {
        $source = $this->source($entry);

        if ($this->isDevServerAvailable()) {
            return $this->devServerUrl('/' . ltrim($source, '/'));
        }

        $manifest = $this->manifest();
        if (isset($manifest[$source]['file'])) {
            return url($this->buildPath() . '/' . $manifest[$source]['file']);
        }

        return url($source);
    }

    private function devTags(string $source): string
    {
        $url = htmlspecialchars($this->devServerUrl('/' . ltrim($source, '/')), ENT_QUOTES, 'UTF-8');

        if ($this->isStyle($source)) {
            return '<link rel="stylesheet" href="' . $url . '">';
        }

        return '<script type="module" src="' . $url . '"></script>';
    }

    private function productionTags(string $source): string
    {
        $manifest = $this->manifest();
        if (!isset($manifest[$source])) {
            return '';
        }

        $entry = $manifest[$source];
        $tags = [];

        if ($this->isStyle($source) && isset($entry['file'])) {
            $tags[] = '<link rel="stylesheet" href="' . htmlspecialchars(url($this->buildPath() . '/' . $entry['file']), ENT_QUOTES, 'UTF-8') . '">';
        }

        if (!$this->isStyle($source) && isset($entry['file'])) {
            $tags[] = '<script type="module" src="' . htmlspecialchars(url($this->buildPath() . '/' . $entry['file']), ENT_QUOTES, 'UTF-8') . '"></script>';
        }

        return implode("\n", array_unique($tags));
    }

    private function source(string $entry): string
    {
        $entries = $this->app->config()->get('view.assets.entries', []);
        return $entries[$entry] ?? ltrim($entry, '/');
    }

    private function isDevServerAvailable(): bool
    {
        if ($this->devServerAvailable !== null) {
            return $this->devServerAvailable;
        }

        $parts = parse_url($this->devServer());
        $host = $parts['host'] ?? '127.0.0.1';
        $port = (int) ($parts['port'] ?? 5173);
        $socket = @fsockopen($host, $port, $errorCode, $errorMessage, 0.05);

        if (is_resource($socket)) {
            fclose($socket);
            return $this->devServerAvailable = true;
        }

        return $this->devServerAvailable = false;
    }

    private function manifest(): array
    {
        if ($this->manifest !== null) {
            return $this->manifest;
        }

        $path = $this->app->config()->get('view.assets.manifest');
        if (!is_string($path) || !is_file($path)) {
            return $this->manifest = [];
        }

        return $this->manifest = json_decode((string) file_get_contents($path), true) ?: [];
    }

    private function devServerUrl(string $path): string
    {
        return rtrim($this->devServer(), '/') . '/' . ltrim($path, '/');
    }

    private function devServer(): string
    {
        return (string) $this->app->config()->get('view.assets.dev_server', 'http://127.0.0.1:5173');
    }

    private function buildPath(): string
    {
        return trim((string) $this->app->config()->get('view.assets.build_path', 'assets'), '/');
    }

    private function isStyle(string $source): bool
    {
        return in_array(pathinfo($source, PATHINFO_EXTENSION), ['css', 'scss', 'sass'], true);
    }
}
