<?php

declare(strict_types=1);

namespace Nova\View;

use Latte\Engine;
use Latte\Loaders\FileLoader;
use Nova\Application\Application;
use Nova\Component\ComponentEngine;

/**
 * Renders Latte templates for Nova views.
 */
final class LatteEngine
{
    private Engine $latte;

    public function __construct(private readonly Application $app, private readonly Asset $asset)
    {
        $this->latte = new Engine();
        $this->latte->setTempDirectory($this->app->config()->get('view.cache', $this->app->storagePath('framework/views')));
        $this->latte->setLoader(new FileLoader($this->app->basePath()));
        $this->latte->addProvider('asset', $this->asset);
        $this->latte->addProvider('componentEngine', $this->app->make(ComponentEngine::class));
        $this->latte->addExtension(new LatteAssetExtension());
        $this->latte->addFunction('asset', fn (string $entry): string => $this->asset->tags($entry));
        $this->latte->addFunction('old', fn (?string $key = null, mixed $default = null): mixed => old($key, $default));
        $this->latte->addFunction('errors', fn (?string $key = null, mixed $default = []): mixed => errors($key, $default));
        $this->latte->addFunction('session', fn (?string $key = null, mixed $value = null): mixed => func_num_args() >= 2 ? session($key, $value) : session($key));
        $this->latte->addFunction('flash', fn (?string $key = null, mixed $value = null): mixed => func_num_args() >= 2 ? flash($key, $value) : flash($key));
        $this->latte->addFunction('auth', fn (): bool => auth());
        $this->latte->addFunction('user', fn (): ?array => user());
        $this->latte->addFunction('guest', fn (): bool => guest());
        $this->latte->addFunction('check', fn (): bool => check());
        $this->latte->addFunction('id', fn (): mixed => id());
        $this->latte->addFunction('can', fn (string $ability, mixed $subject, mixed $user = null): bool => can($ability, $subject, $user));
        $this->latte->addFunction('cannot', fn (string $ability, mixed $subject, mixed $user = null): bool => cannot($ability, $subject, $user));
    }

    public function render(string $template, array $data = [], ?string $layout = null): string
    {
        $template = $this->normalizeTemplate($template);
        $data += ['user' => user()];

        if ($layout !== null) {
            $content = $this->latte->renderToString($template, $data);
            return $this->latte->renderToString($this->normalizeTemplate($layout), $data + ['content' => $content]);
        }

        return $this->latte->renderToString($template, $data);
    }

    public function exists(string $name): bool
    {
        try {
            $this->normalizeTemplate($name);
            return true;
        } catch (ViewException) {
            return false;
        }
    }

    private function normalizeTemplate(string $name): string
    {
        if (is_file($name)) {
            return $this->relativeToBase($name);
        }

        $relative = str_ends_with($name, '.latte')
            ? str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $name)
            : str_replace('.', DIRECTORY_SEPARATOR, $name);
        foreach ($this->app->config()->get('view.paths', []) as $base) {
            foreach ([$relative, $relative . '.latte'] as $candidate) {
                $path = rtrim($base, '/\\') . DIRECTORY_SEPARATOR . $candidate;
                if (is_file($path)) {
                    return $this->relativeToBase($path);
                }
            }
        }

        throw new ViewException("Latte template [{$name}] not found.");
    }

    private function relativeToBase(string $path): string
    {
        $base = rtrim(str_replace('\\', '/', $this->app->basePath()), '/') . '/';
        $normalized = str_replace('\\', '/', $path);

        if (str_starts_with($normalized, $base)) {
            return substr($normalized, strlen($base));
        }

        return $normalized;
    }
}
