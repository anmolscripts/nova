<?php

declare(strict_types=1);

use Nova\Application\Application;
use Nova\Exceptions\ErrorHandler;
use Nova\Http\Kernel;

$basePath = dirname(__DIR__);

$app = new Application($basePath);
$app->bootstrap();
$app->singleton(Kernel::class, fn (Application $app) => new Kernel($app));

$handler = new ErrorHandler($app);
$handler->register();

return $app;
