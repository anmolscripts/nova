<?php

declare(strict_types=1);

namespace Nova\Console;

use Nova\Application\Application;
use Nova\Routing\RouteLoader;
use Nova\Routing\Router;

final class Console
{
    public function __construct(private readonly Application $app)
    {
    }

    public function run(array $argv): int
    {
        $command = $argv[1] ?? 'help';
        $args = array_slice($argv, 2);

        return match ($command) {
            'serve' => $this->serve($args),
            'make:page' => $this->makePage($args),
            'make:component' => $this->makeComponent($args),
            'make:middleware' => $this->makeMiddleware($args),
            'make:migration' => $this->makeMigration($args),
            'make:procedure' => $this->makeProcedure($args),
            'route:list' => $this->routeList(),
            'route:cache' => $this->routeCache(),
            'config:cache' => $this->configCache(),
            'cache:clear' => $this->cacheClear(),
            default => $this->help(),
        };
    }

    private function help(): int
    {
        $this->line('Nova commands:');
        $this->line('  serve [--host=127.0.0.1] [--port=8000]');
        $this->line('  make:page path');
        $this->line('  make:component Name');
        $this->line('  make:middleware name');
        $this->line('  make:migration name');
        $this->line('  make:procedure name');
        $this->line('  route:list');
        $this->line('  route:cache');
        $this->line('  config:cache');
        $this->line('  cache:clear');
        return 0;
    }

    private function serve(array $args): int
    {
        $host = $this->option($args, 'host', '127.0.0.1');
        $port = $this->option($args, 'port', '8000');
        $this->line("Nova development server: http://{$host}:{$port}");
        passthru(PHP_BINARY . ' -S ' . escapeshellarg($host . ':' . $port) . ' -t ' . escapeshellarg($this->app->basePath('public')));
        return 0;
    }

    private function makePage(array $args): int
    {
        $path = trim($args[0] ?? '', '/');
        if ($path === '') {
            $this->line('Page path is required.');
            return 1;
        }

        $file = $this->app->basePath('app/' . $path . '/page.php');
        return $this->write($file, "<?php\n\nreturn view('{$this->viewName($path)}', []);\n");
    }

    private function makeComponent(array $args): int
    {
        $name = trim($args[0] ?? '');
        if ($name === '') {
            $this->line('Component name is required.');
            return 1;
        }

        $file = $this->app->basePath('components/' . str_replace('\\', '/', $name) . '.php');
        return $this->write($file, "<?php\n\n/** @var array \$props */\n\n?>\n<div><?= htmlspecialchars(\$props['slot'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>\n");
    }

    private function makeMiddleware(array $args): int
    {
        $name = trim($args[0] ?? '');
        if ($name === '') {
            $this->line('Middleware name is required.');
            return 1;
        }

        $file = $this->app->basePath('app/' . $name . '/middleware.php');
        return $this->write($file, "<?php\n\nuse Nova\\Http\\Request;\n\nreturn function (Request \$request, callable \$next) {\n    return \$next(\$request);\n};\n");
    }

    private function makeMigration(array $args): int
    {
        $name = preg_replace('/[^a-zA-Z0-9_]/', '_', $args[0] ?? 'migration');
        $file = $this->app->basePath('database/migrations/' . date('Y_m_d_His') . '_' . $name . '.php');
        return $this->write($file, "<?php\n\nreturn [\n    'up' => function (\\Nova\\Database\\Connection \$db): void {\n        //\n    },\n    'down' => function (\\Nova\\Database\\Connection \$db): void {\n        //\n    },\n];\n");
    }

    private function makeProcedure(array $args): int
    {
        $name = preg_replace('/[^a-zA-Z0-9_]/', '_', $args[0] ?? 'procedure');
        $file = $this->app->basePath('database/procedures/' . $name . '.sql');
        return $this->write($file, "-- Write stored procedure SQL for {$name} here.\n");
    }

    private function routeList(): int
    {
        foreach ($this->app->make(Router::class)->all() as $route) {
            $this->line(sprintf('%-24s %-32s %s', $route->name, $route->path, $route->file));
        }
        return 0;
    }

    private function routeCache(): int
    {
        $routes = (new RouteLoader($this->app))->discover();
        $file = $this->app->storagePath('framework/routes/routes.php');
        $this->ensureDirectory(dirname($file));
        file_put_contents($file, "<?php\n\nreturn " . var_export($routes, true) . ";\n");
        $this->line('Route cache written.');
        return 0;
    }

    private function configCache(): int
    {
        $items = [];
        foreach (glob($this->app->basePath('config/*.php')) ?: [] as $file) {
            $items[basename($file, '.php')] = require $file;
        }

        $file = $this->app->storagePath('framework/config/config.php');
        $this->ensureDirectory(dirname($file));
        file_put_contents($file, "<?php\n\nreturn " . var_export($items, true) . ";\n");
        $this->line('Config cache written.');
        return 0;
    }

    private function cacheClear(): int
    {
        foreach ([
            $this->app->storagePath('framework/routes/routes.php'),
            $this->app->storagePath('framework/config/config.php'),
            ...glob($this->app->storagePath('framework/views/*.php')) ?: [],
            ...glob($this->app->storagePath('cache/*.cache')) ?: [],
        ] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        $this->line('Cache cleared.');
        return 0;
    }

    private function write(string $file, string $content): int
    {
        if (is_file($file)) {
            $this->line("File already exists: {$file}");
            return 1;
        }

        $this->ensureDirectory(dirname($file));
        file_put_contents($file, $content);
        $this->line("Created {$file}");
        return 0;
    }

    private function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
    }

    private function option(array $args, string $key, string $default): string
    {
        foreach ($args as $arg) {
            if (str_starts_with($arg, "--{$key}=")) {
                return substr($arg, strlen($key) + 3);
            }
        }
        return $default;
    }

    private function viewName(string $path): string
    {
        return str_replace('/', '.', trim($path, '/'));
    }

    private function line(string $message): void
    {
        fwrite(STDOUT, $message . PHP_EOL);
    }
}
