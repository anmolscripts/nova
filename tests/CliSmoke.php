<?php

declare(strict_types=1);

use Nova\Application\Application;
use Nova\Console\Console;

require dirname(__DIR__) . '/vendor/autoload.php';

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
};

$root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'nova-cli-' . bin2hex(random_bytes(4));
mkdir($root . DIRECTORY_SEPARATOR . 'app', 0777, true);
mkdir($root . DIRECTORY_SEPARATOR . 'config', 0777, true);
mkdir($root . DIRECTORY_SEPARATOR . 'public', 0777, true);
mkdir($root . DIRECTORY_SEPARATOR . 'storage', 0777, true);

$write = static function (string $file, string $content): void {
    $directory = dirname($file);
    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
    file_put_contents($file, $content);
};

$remove = static function (string $path) use (&$remove): void {
    if (!str_starts_with($path, sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'nova-cli-')) {
        return;
    }

    if (is_file($path)) {
        unlink($path);
        return;
    }

    if (!is_dir($path)) {
        return;
    }

    foreach (scandir($path) ?: [] as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $remove($path . DIRECTORY_SEPARATOR . $item);
    }
    rmdir($path);
};

$write($root . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php', "<?php\n\nreturn ['env' => 'testing', 'timezone' => 'UTC'];\n");
$write($root . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php', "<?php\n\nreturn ['default' => 'sqlite'];\n");

$app = new Application($root);
$app->bootstrap();
app($app);

$console = new Console($app);
$run = static function (array $argv) use ($console, $assert): void {
    ob_start();
    $status = $console->run($argv);
    ob_end_clean();
    $assert($status === 0, 'Command failed: ' . implode(' ', $argv));
};

$run(['nova', 'version']);
$run(['nova', 'about']);
$run(['nova', 'doctor']);
$run(['nova', 'make:page', 'Users/Profile']);
$run(['nova', 'make:component', 'Button']);
$run(['nova', 'make:action', 'users/profile', 'SaveUser']);
$run(['nova', 'make:policy', 'Product']);
$run(['nova', 'make:middleware', 'Admin']);
$run(['nova', 'make:procedure', 'sp_users']);
$run(['nova', 'make:migration', 'create_users_table']);
$run(['nova', 'make:seeder', 'Users']);
$run(['nova', 'page:cache']);
$run(['nova', 'route:cache']);
$run(['nova', 'config:cache']);
$run(['nova', 'component:cache']);
$run(['nova', 'optimize']);

$files = [
    'app/users/profile/page.php',
    'app/users/profile/page.latte',
    'app/users/profile/page.ts',
    'app/users/profile/page.scss',
    'app/users/profile/middleware.php',
    'app/users/profile/actions/.gitkeep',
    'app/users/profile/actions/save-user.php',
    'components/Button/component.php',
    'components/Button/component.latte',
    'components/Button/component.ts',
    'components/Button/component.scss',
    'app/policies/ProductPolicy.php',
    'app/middleware/Admin.php',
    'database/procedures/sp_users.sql',
    'database/seeders/UsersSeeder.php',
    'storage/framework/pages.php',
    'storage/framework/routes.php',
    'storage/framework/config/config.php',
    'storage/framework/components.php',
];

foreach ($files as $file) {
    $assert(is_file($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file)), "Expected file was not generated: {$file}");
}

$migrations = glob($root . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR . '*_create_users_table.php') ?: [];
$assert(count($migrations) === 1, 'Migration file was not generated.');

$run(['nova', 'optimize:clear']);
$assert(!is_file($root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'routes.php'), 'Route cache was not cleared.');
$assert(!is_file($root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'pages.php'), 'Page cache was not cleared.');
$assert(!is_file($root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'components.php'), 'Component cache was not cleared.');

$remove($root);

echo "CLI smoke test passed." . PHP_EOL;
