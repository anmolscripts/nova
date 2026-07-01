<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$app = require dirname(__DIR__) . '/bootstrap/app.php';
app($app);

@unlink($app->storagePath('framework/components.php'));
@unlink($app->storagePath('framework/pages.php'));

$assert = function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
};

$discovery = $app->make(\Nova\Component\ComponentDiscovery::class);
$components = $discovery->components();
$assert(isset($components['global:Button']), 'Global Button component was not discovered.');
$assert(isset($components['global:Card']), 'Global Card component was not discovered.');
$assert(isset($components['app/dashboard:StatsCard']), 'Dashboard StatsCard component was not discovered.');
$assert(is_file($app->storagePath('framework/components.php')), 'Component manifest was not generated.');

$engine = $app->make(\Nova\Component\ComponentEngine::class);
$button = $engine->render('Button', ['text' => 'Save']);
$assert(str_contains($button, 'nova-button'), 'Button component did not render.');
$assert(str_contains($button, 'nova-icon'), 'Nested Icon component did not render.');
$assert(substr_count($button . $engine->render('Button', ['text' => 'Save again']), 'component.ts') <= 1, 'Component JS asset was duplicated in development output.');

$dashboard = $app->make(\Nova\App\PageDiscovery::class)->match('/dashboard');
$assert($dashboard !== null, 'Dashboard page was not discovered.');
$app->setCurrentPage($dashboard);

$stats = $engine->render('StatsCard', ['label' => 'Revenue', 'value' => '$42,000']);
$assert(str_contains($stats, 'data-component-scope="dashboard"'), 'Local StatsCard did not override the global component.');

$card = $engine->render('Card', ['title' => 'Nested', 'body' => 'Card body']);
$assert(str_contains($card, 'nova-card'), 'Card component did not render.');
$assert(str_contains($card, 'nova-button'), 'Nested Button component did not render inside Card.');

echo "Component engine smoke test passed." . PHP_EOL;
