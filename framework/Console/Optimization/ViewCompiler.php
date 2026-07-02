<?php

declare(strict_types=1);

namespace Nova\Console\Optimization;

use Nova\View\ViewFactory;

final class ViewCompiler implements CompilerInterface
{
    public function __construct(private readonly \Nova\Application\Application $app)
    {
    }

    public function name(): string
    {
        return 'view';
    }

    public function compile(): array
    {
        $cacheDirectory = $this->app->storagePath('framework/views');
        $this->ensureDirectory($cacheDirectory);

        $warnings = [];
        foreach ($this->templateFiles() as $file) {
            try {
                $this->app->make(ViewFactory::class)->render($file, $this->templateContext());
            } catch (\Throwable $throwable) {
                $warnings[] = 'Unable to warm template ' . $file . ': ' . $throwable->getMessage();
            }
        }

        return ['created' => 0, 'modified' => 0, 'warnings' => $warnings];
    }

    public function clear(): void
    {
        foreach (glob($this->app->storagePath('framework/views/*')) ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function status(): string
    {
        return is_dir($this->app->storagePath('framework/views')) ? 'Generated' : 'Missing';
    }

    private function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
    }

    /** @return array<int, string> */
    private function templateFiles(): array
    {
        $files = [];
        $directories = [
            $this->app->basePath('app'),
            $this->app->basePath('components'),
            $this->app->basePath('resources/views'),
        ];

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)) as $file) {
                if ($file->isFile() && str_ends_with($file->getFilename(), '.latte')) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return array_values(array_unique($files));
    }

    private function templateContext(): array
    {
        return [
            'title' => '',
            'message' => '',
            'label' => '',
            'name' => '',
            'revenue' => 0,
            'category' => '',
            'product' => '',
            'content' => '',
            'page' => [],
            'currentPage' => null,
            'route' => [],
            'config' => $this->app->config()->all(),
            'user' => null,
            'errors' => [],
            'session' => [],
            'slug' => [],
            'value' => '',
        ];
    }
}
