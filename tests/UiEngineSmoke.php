<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
};

$files = [
    'resources/ts/nova/action.ts',
    'resources/ts/nova/dom.ts',
    'resources/ts/nova/events.ts',
    'resources/ts/nova/request.ts',
    'resources/ts/nova/response.ts',
    'resources/ts/nova/toast.ts',
    'resources/ts/nova/modal.ts',
    'resources/ts/nova/loader.ts',
    'config/ui.php',
];

foreach ($files as $file) {
    $assert(is_file($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file)), "Missing UI engine file: {$file}");
}

$action = file_get_contents($root . '/resources/ts/nova/action.ts') ?: '';
$dom = file_get_contents($root . '/resources/ts/nova/dom.ts') ?: '';
$response = file_get_contents($root . '/resources/ts/nova/response.ts') ?: '';
$loader = file_get_contents($root . '/resources/ts/nova/loader.ts') ?: '';
$toast = file_get_contents($root . '/resources/ts/nova/toast.ts') ?: '';
$app = file_get_contents($root . '/resources/ts/app.ts') ?: '';

$assert(str_contains($app, "import './nova'"), 'Nova UI engine is not auto-initialized from app.ts.');
$assert(str_contains($action, "document.addEventListener('click'"), 'Button action binding is missing.');
$assert(str_contains($action, "document.addEventListener('submit'"), 'Form action binding is missing.');
$assert(str_contains($dom, '[data-action]'), 'data-action discovery is missing.');
$assert(str_contains($loader, '.disabled = true'), 'Loading disabled state is missing.');
$assert(str_contains($loader, 'nova-progress'), 'Progress indicator is missing.');
$assert(str_contains($response, 'window.location.assign'), 'Redirect handling is missing.');
$assert(str_contains($response, 'showValidationErrors'), 'Validation error handling is missing.');
$assert(str_contains($response, 'refreshMany'), 'DOM refresh handling is missing.');
$assert(str_contains($toast, 'nova-toast'), 'Toast rendering is missing.');
$assert(str_contains($response, 'nova:error') && str_contains($response, 'nova:success'), 'Nova browser events are missing.');

echo "UI engine smoke test passed." . PHP_EOL;
