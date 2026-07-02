<?php

declare(strict_types=1);

namespace Nova\Console\Optimization;

use Nova\Config\ConfigLoader;

final class ConfigCompiler implements CompilerInterface
{
    public function __construct(private readonly \Nova\Application\Application $app)
    {
    }

    public function name(): string
    {
        return 'config';
    }

    public function compile(): array
    {
        $items = ConfigLoader::loadDirectory($this->app->basePath('config'));
        $file = $this->app->storagePath('framework/config/config.php');
        $this->ensureDirectory(dirname($file));
        $created = !is_file($file) ? 1 : 0;
        $modified = is_file($file) ? 1 : 0;
        file_put_contents($file, "<?php\n\nreturn " . var_export($items, true) . ";\n");

        return ['created' => $created, 'modified' => $modified, 'warnings' => []];
    }

    public function clear(): void
    {
        $this->delete($this->app->storagePath('framework/config/config.php'));
    }

    public function status(): string
    {
        $file = $this->app->storagePath('framework/config/config.php');
        return is_file($file) ? 'Generated' : 'Missing';
    }

    private function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
    }

    private function delete(string $file): void
    {
        if (is_file($file)) {
            unlink($file);
        }
    }
}
