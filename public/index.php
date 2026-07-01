<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$app = require dirname(__DIR__) . '/bootstrap/app.php';
app($app);

$kernel = $app->make(\Nova\Http\Kernel::class);
$kernel->handle(\Nova\Http\Request::capture())->send();
