<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$app = require dirname(__DIR__) . '/bootstrap/app.php';
app($app);

$console = new \Nova\Console\Console($app);
$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
};

ob_start();
$status = $console->run(['nova', 'optimize']);
$output = ob_get_clean();
$assert($status === 0, 'Optimize command should succeed.');
$assert(str_contains($output, 'Optimization Summary') || str_contains($output, 'Nova optimized'), 'Optimize output should include a summary.');

$expectedFiles = [
    'storage/framework/config/config.php',
    'storage/framework/pages.php',
    'storage/framework/routes.php',
    'storage/framework/layouts.php',
    'storage/framework/components.php',
];

foreach ($expectedFiles as $file) {
    $assert(is_file($app->storagePath($file)), "Expected optimized file was not generated: {$file}");
}

ob_start();
$status = $console->run(['nova', 'optimize:status']);
$statusOutput = ob_get_clean();
$assert($status === 0, 'Optimize status command should succeed.');
$assert(str_contains($statusOutput, 'Config Cache') && str_contains($statusOutput, 'Route Cache'), 'Optimize status output should include cache headings.');

ob_start();
$status = $console->run(['nova', 'optimize:clear']);
$clearOutput = ob_get_clean();
$assert($status === 0, 'Optimize clear command should succeed.');
$assert(str_contains($clearOutput, 'cleared') || str_contains($clearOutput, 'Cleared'), 'Optimize clear output should mention clearing caches.');

ob_start();
$status = $console->run(['nova', 'optimize']);
$repeatOutput = ob_get_clean();
$assert($status === 0, 'Repeated optimize should succeed.');
$assert(str_contains($repeatOutput, 'Optimization Summary') || str_contains($repeatOutput, 'Nova optimized'), 'Repeated optimize should still produce a summary.');

echo 'Optimize command smoke test passed.' . PHP_EOL;
