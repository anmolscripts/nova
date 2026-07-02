<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$app = require dirname(__DIR__) . '/bootstrap/app.php';
app($app);

$profiler = $app->profiler();
$profiler->start();
$profiler->record('test.start');
$app->make('stdClass');
$profiler->record('test.finish');
$profile = $profiler->finish();

if ($profile->duration() < 0) {
    fwrite(STDERR, "Profiler duration should not be negative." . PHP_EOL);
    exit(1);
}

if ($profile->memoryDelta() < 0) {
    fwrite(STDERR, "Profiler memory delta should not be negative." . PHP_EOL);
    exit(1);
}

if (count($profile->entries()) < 2) {
    fwrite(STDERR, "Profiler should record multiple entries." . PHP_EOL);
    exit(1);
}

echo "Profiler smoke test passed." . PHP_EOL;
