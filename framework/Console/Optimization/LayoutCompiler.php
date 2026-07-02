<?php

declare(strict_types=1);

namespace Nova\Console\Optimization;

final class LayoutCompiler implements CompilerInterface
{
    public function __construct(private readonly \Nova\Application\Application $app)
    {
    }

    public function name(): string
    {
        return 'layout';
    }

    public function compile(): array
    {
        $path = $this->app->storagePath('framework/layouts.php');
        $created = !is_file($path) ? 1 : 0;
        $modified = is_file($path) ? 1 : 0;
        $this->ensureDirectory(dirname($path));
        file_put_contents($path, "<?php\n\nreturn [];\n");

        return ['created' => $created, 'modified' => $modified, 'warnings' => []];
    }

    public function clear(): void
    {
        $this->delete($this->app->storagePath('framework/layouts.php'));
    }

    public function status(): string
    {
        $file = $this->app->storagePath('framework/layouts.php');
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
