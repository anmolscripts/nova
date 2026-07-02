<?php

declare(strict_types=1);

namespace Nova\Console;

use Nova\App\PageDiscovery;
use Nova\Application\Application;
use Nova\Component\ComponentDiscovery;
use Nova\Config\ConfigLoader;
use Nova\Console\Optimization\CompilerInterface;
use Nova\Console\Optimization\CompilerRegistry;
use Nova\Console\Optimization\OptimizingPipeline;
use Nova\Routing\RouteMatcher;

/**
 * Dispatches Nova CLI commands.
 */
final class Console
{
    private const Version = '0.1.0';

    public function __construct(private readonly Application $app)
    {
    }

    public function run(array $argv): int
    {
        $command = $argv[1] ?? 'help';
        $args = array_slice($argv, 2);

        return match ($command) {
            'serve' => $this->serve($args),
            'doctor' => $this->doctor(),
            'about' => $this->about(),
            'version' => $this->version(),
            'make:page' => $this->makePage($args),
            'make:component' => $this->makeComponent($args),
            'make:action' => $this->makeAction($args),
            'make:policy' => $this->makePolicy($args),
            'make:middleware' => $this->makeMiddleware($args),
            'make:migration' => $this->makeMigration($args),
            'make:procedure' => $this->makeProcedure($args),
            'make:seeder' => $this->makeSeeder($args),
            'page:cache' => $this->pageCache(),
            'route:cache' => $this->routeCache(),
            'config:cache' => $this->configCache(),
            'component:cache' => $this->componentCache(),
            'optimize' => $this->optimize(),
            'optimize:clear', 'cache:clear' => $this->optimizeClear(),
            'optimize:status' => $this->optimizeStatus(),
            default => $this->help($command === 'help' ? null : $command),
        };
    }

    private function help(?string $unknown = null): int
    {
        if ($unknown !== null) {
            $this->error("Unknown command: {$unknown}");
        }

        $this->line('Nova CLI');
        $this->line('Usage: php nova <command> [arguments] [options]');
        $this->line('');
        $this->line('Core:');
        $this->line('  serve [--host=127.0.0.1] [--port=8000] [--open]');
        $this->line('  doctor');
        $this->line('  about');
        $this->line('  version');
        $this->line('');
        $this->line('Generators:');
        $this->line('  make:page Users');
        $this->line('  make:component Button');
        $this->line('  make:action users SaveUser');
        $this->line('  make:policy Product');
        $this->line('  make:middleware Admin');
        $this->line('  make:migration create_users_table');
        $this->line('  make:procedure sp_users');
        $this->line('  make:seeder Users');
        $this->line('');
        $this->line('Cache:');
        $this->line('  page:cache');
        $this->line('  route:cache');
        $this->line('  config:cache');
        $this->line('  component:cache');
        $this->line('  optimize');
        $this->line('  optimize:clear');
        $this->line('  optimize:status');

        return $unknown === null ? 0 : 1;
    }

    private function serve(array $args): int
    {
        $host = $this->option($args, 'host', '127.0.0.1');
        $port = (int) $this->option($args, 'port', '8000');
        $port = $this->availablePort($host, $port);
        $url = "http://{$host}:{$port}";

        $this->successLine('Nova development server');
        $this->line("Environment: " . $this->app->config()->get('app.env', 'local'));
        $this->line("URL: {$url}");
        $this->line('Press Ctrl+C to stop.');

        if ($this->hasFlag($args, 'open')) {
            $this->openBrowser($url);
        }

        passthru(PHP_BINARY . ' -S ' . escapeshellarg($host . ':' . $port) . ' -t ' . escapeshellarg($this->app->basePath('public')));

        return 0;
    }

    private function doctor(): int
    {
        $checks = [
            ['PHP >= 8.5', version_compare(PHP_VERSION, '8.5.0', '>=')],
            ['PDO extension', extension_loaded('pdo')],
            ['PDO drivers', \PDO::getAvailableDrivers() !== []],
            ['JSON extension', extension_loaded('json')],
            ['Tokenizer extension', extension_loaded('tokenizer')],
            ['Composer', $this->commandAvailable('composer --version')],
            ['Node', $this->commandAvailable('node --version')],
            ['npm', $this->commandAvailable('npm --version')],
            ['storage writable', is_writable($this->app->storagePath())],
            ['APP_ENV set', (bool) $this->app->config()->get('app.env')],
        ];

        $failed = 0;
        foreach ($checks as [$label, $ok]) {
            $this->line(($ok ? $this->green('PASS') : $this->red('FAIL')) . " {$label}");
            $failed += $ok ? 0 : 1;
        }

        return $failed === 0 ? 0 : 1;
    }

    private function about(): int
    {
        $this->line('Nova Framework');
        $this->line('Nova: ' . self::Version);
        $this->line('PHP: ' . PHP_VERSION);
        $this->line('Environment: ' . $this->app->config()->get('app.env', 'unknown'));
        $this->line('Database: ' . $this->app->config()->get('database.default', 'unknown'));
        $this->line('Node: ' . trim($this->runProcess('node --version') ?: 'not found'));

        return 0;
    }

    private function version(): int
    {
        $this->line(self::Version);

        return 0;
    }

    private function makePage(array $args): int
    {
        $name = $this->required($args, 0, 'Page name is required.');
        if ($name === null) {
            return 1;
        }

        $slug = $this->routePath($name);
        $title = $this->title($name);
        $directory = $this->app->basePath('app/' . $slug);

        return $this->writeMany([
            $directory . '/page.php' => $this->stub('page/page.php', ['title' => $title]),
            $directory . '/page.latte' => $this->stub('page/page.latte', ['title' => $title]),
            $directory . '/page.ts' => $this->stub('page/page.ts', ['name' => $slug]),
            $directory . '/page.scss' => $this->stub('page/page.scss', ['class' => str_replace('/', '-', $slug)]),
            $directory . '/middleware.php' => $this->stub('page/middleware.php'),
            $directory . '/actions/.gitkeep' => '',
        ]);
    }

    private function makeComponent(array $args): int
    {
        $name = $this->required($args, 0, 'Component name is required.');
        if ($name === null) {
            return 1;
        }

        $class = $this->studly($name);
        $directory = $this->app->basePath('components/' . $class);

        return $this->writeMany([
            $directory . '/component.php' => $this->stub('component/component.php', ['name' => $class, 'class' => $this->kebab($class)]),
            $directory . '/component.latte' => $this->stub('component/component.latte', ['name' => $class, 'class' => $this->kebab($class)]),
            $directory . '/component.ts' => $this->stub('component/component.ts', ['name' => $class, 'class' => $this->kebab($class)]),
            $directory . '/component.scss' => $this->stub('component/component.scss', ['class' => $this->kebab($class)]),
        ]);
    }

    private function makeAction(array $args): int
    {
        $page = $this->required($args, 0, 'Page path is required.');
        $name = $this->required($args, 1, 'Action name is required.');
        if ($page === null || $name === null) {
            return 1;
        }

        $file = $this->app->basePath('app/' . $this->routePath($page) . '/actions/' . $this->kebab($name) . '.php');

        return $this->write($file, $this->stub('action/action.php', ['name' => $this->title($name)]));
    }

    private function makePolicy(array $args): int
    {
        $name = $this->required($args, 0, 'Policy name is required.');
        if ($name === null) {
            return 1;
        }

        $class = $this->studly($name) . 'Policy';

        return $this->write($this->app->basePath('app/policies/' . $class . '.php'), $this->stub('policy/policy.php', ['class' => $class]));
    }

    private function makeMiddleware(array $args): int
    {
        $name = $this->required($args, 0, 'Middleware name is required.');
        if ($name === null) {
            return 1;
        }

        $class = $this->studly($name);

        return $this->write($this->app->basePath('app/middleware/' . $class . '.php'), $this->stub('middleware/middleware.php', ['class' => $class]));
    }

    private function makeProcedure(array $args): int
    {
        $name = $this->required($args, 0, 'Procedure name is required.');
        if ($name === null) {
            return 1;
        }

        $procedure = preg_replace('/[^A-Za-z0-9_]/', '_', $name);

        return $this->write($this->app->basePath('database/procedures/' . $procedure . '.sql'), $this->stub('procedure/procedure.sql', ['name' => $procedure]));
    }

    private function makeMigration(array $args): int
    {
        $name = preg_replace('/[^A-Za-z0-9_]/', '_', $args[0] ?? 'migration');
        $file = $this->app->basePath('database/migrations/' . date('Y_m_d_His') . '_' . $name . '.php');

        return $this->write($file, $this->stub('migration/migration.php', ['name' => $name]));
    }

    private function makeSeeder(array $args): int
    {
        $name = $this->studly($args[0] ?? 'Database') . 'Seeder';

        return $this->write($this->app->basePath('database/seeders/' . $name . '.php'), $this->stub('seeder/seeder.php', ['class' => $name]));
    }

    private function pageCache(): int
    {
        $this->delete($this->app->storagePath('framework/pages.php'));
        $this->app->make(PageDiscovery::class)->pages();
        $this->successLine('Page cache written.');

        return 0;
    }

    private function routeCache(): int
    {
        $this->delete($this->app->storagePath('framework/routes.php'));
        (new RouteMatcher($this->app))->writeCache();
        $this->successLine('Route cache written.');

        return 0;
    }

    private function componentCache(): int
    {
        $this->delete($this->app->storagePath('framework/components.php'));
        $this->app->make(ComponentDiscovery::class)->components();
        $this->successLine('Component cache written.');

        return 0;
    }

    private function configCache(): int
    {
        $items = ConfigLoader::loadDirectory($this->app->basePath('config'));

        $file = $this->app->storagePath('framework/config/config.php');
        $this->ensureDirectory(dirname($file));
        file_put_contents($file, "<?php\n\nreturn " . var_export($items, true) . ";\n");
        $this->successLine('Config cache written.');

        return 0;
    }

    private function optimize(): int
    {
        $pipeline = new OptimizingPipeline($this->app, $this->compilerRegistry());
        $pipeline->run();
        return 0;
    }

    private function optimizeClear(): int
    {
        foreach ($this->compilerRegistry()->all() as $compiler) {
            $compiler->clear();
        }

        foreach (glob($this->app->storagePath('framework/views/*')) ?: [] as $file) {
            $this->delete($file);
        }

        foreach (glob($this->app->storagePath('cache/*.cache')) ?: [] as $file) {
            $this->delete($file);
        }

        $this->successLine('Optimization caches cleared.');

        return 0;
    }

    private function optimizeStatus(): int
    {
        $registry = $this->compilerRegistry();
        $this->line('Optimization Status');
        $this->line('===================');

        foreach ($registry->all() as $compiler) {
            $status = $compiler->status();
            $label = match ($compiler->name()) {
                'config' => 'Config Cache',
                'route' => 'Route Cache',
                'page' => 'Page Cache',
                'component' => 'Component Cache',
                'view' => 'View Cache',
                'asset' => 'Asset Manifest',
                'layout' => 'Layout Cache',
                default => ucfirst($compiler->name()) . ' Cache',
            };
            $this->line(sprintf('%s: %s', $label, $status));
        }

        return 0;
    }

    private function compilerRegistry(): CompilerRegistry
    {
        $registry = new CompilerRegistry();
        foreach ($this->app->config()->get('optimize.compilers', []) as $compiler) {
            $instance = is_string($compiler) ? $this->app->make($compiler) : $compiler;
            if ($instance instanceof CompilerInterface) {
                $registry->register($instance);
            }
        }

        if ($registry->all() === []) {
            $registry->register(new \Nova\Console\Optimization\ConfigCompiler($this->app));
            $registry->register(new \Nova\Console\Optimization\RouteCompiler($this->app));
            $registry->register(new \Nova\Console\Optimization\PageCompiler($this->app));
            $registry->register(new \Nova\Console\Optimization\LayoutCompiler($this->app));
            $registry->register(new \Nova\Console\Optimization\ComponentCompiler($this->app));
            $registry->register(new \Nova\Console\Optimization\ViewCompiler($this->app));
            $registry->register(new \Nova\Console\Optimization\AssetCompiler($this->app));
        }

        return $registry;
    }

    private function writeMany(array $files): int
    {
        $status = 0;
        foreach ($files as $file => $content) {
            $status = max($status, $this->write($file, $content));
        }

        return $status;
    }

    private function write(string $file, string $content): int
    {
        if (is_file($file)) {
            $this->error("File already exists: {$file}");
            return 1;
        }

        $this->ensureDirectory(dirname($file));
        file_put_contents($file, $content);
        $this->successLine("Created {$file}");

        return 0;
    }

    private function stub(string $name, array $data = []): string
    {
        $path = $this->stubPath($name);
        $content = is_file($path) ? (string) file_get_contents($path) : '';

        foreach ($data as $key => $value) {
            $content = str_replace('{{ ' . $key . ' }}', (string) $value, $content);
        }

        return $content;
    }

    private function stubPath(string $name): string
    {
        $local = $this->app->basePath('stubs/' . $name);
        if (is_file($local)) {
            return $local;
        }

        return dirname(__DIR__, 2) . '/stubs/' . $name;
    }

    private function required(array $args, int $index, string $message): ?string
    {
        $value = trim((string) ($args[$index] ?? ''));
        if ($value === '') {
            $this->error($message);
            return null;
        }

        return $value;
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

    private function hasFlag(array $args, string $key): bool
    {
        return in_array("--{$key}", $args, true);
    }

    private function routePath(string $name): string
    {
        return trim(str_replace('\\', '/', $this->kebab($name)), '/');
    }

    private function studly(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_', '/', '\\'], ' ', $name)));
    }

    private function kebab(string $name): string
    {
        $segments = preg_split('#[\\\\/]#', $name) ?: [];
        $segments = array_map(static function (string $segment): string {
            $segment = str_replace('_', '-', $segment);
            $segment = preg_replace('/(?<!^)[A-Z]/', '-$0', $segment);

            return strtolower(trim((string) $segment, '-'));
        }, $segments);

        return trim(implode('/', array_filter($segments, static fn (string $segment): bool => $segment !== '')), '/');
    }

    private function title(string $name): string
    {
        return trim(ucwords(str_replace(['-', '_', '/', '\\'], ' ', $this->kebab($name))));
    }

    private function availablePort(string $host, int $port): int
    {
        while (@fsockopen($host, $port, $code, $message, 0.05)) {
            $port++;
        }

        return $port;
    }

    private function openBrowser(string $url): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            pclose(popen('start "" ' . escapeshellarg($url), 'r'));
            return;
        }

        $command = PHP_OS_FAMILY === 'Darwin' ? 'open ' : 'xdg-open ';
        @exec($command . escapeshellarg($url) . ' > /dev/null 2>&1 &');
    }

    private function commandAvailable(string $command): bool
    {
        return $this->runProcess($command) !== '';
    }

    private function runProcess(string $command): string
    {
        $output = [];
        $redirect = PHP_OS_FAMILY === 'Windows' ? ' 2>NUL' : ' 2>/dev/null';
        @exec($command . $redirect, $output);

        return trim(implode("\n", $output));
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

    private function successLine(string $message): void
    {
        $this->line($this->green($message));
    }

    private function error(string $message): void
    {
        $this->line($this->red($message));
    }

    private function green(string $message): string
    {
        return "\033[32m{$message}\033[0m";
    }

    private function red(string $message): string
    {
        return "\033[31m{$message}\033[0m";
    }

    private function line(string $message): void
    {
        fwrite(STDOUT, $message . PHP_EOL);
    }
}
