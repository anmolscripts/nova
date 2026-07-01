<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$app = require dirname(__DIR__) . '/bootstrap/app.php';
app($app);

@unlink($app->storagePath('framework/routes.php'));
@unlink($app->storagePath('framework/pages.php'));

$matcher = new \Nova\Routing\RouteMatcher($app);

$assert = function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
};

$cases = [
    '/' => ['uri' => '/', 'params' => []],
    '/about' => ['uri' => '/about', 'params' => []],
    '/users' => ['uri' => '/users', 'params' => []],
    '/users/12' => ['uri' => '/users/[id]', 'params' => ['id' => '12']],
    '/users/12/orders' => ['uri' => '/users/[id]/orders', 'params' => ['id' => '12']],
    '/products/mobile/iphone' => ['uri' => '/products/[category]/[product]', 'params' => ['category' => 'mobile', 'product' => 'iphone']],
    '/docs/php/framework' => ['uri' => '/docs/[...slug]', 'params' => ['slug' => ['php', 'framework']]],
    '/blog' => ['uri' => '/blog/[[...slug]]', 'params' => ['slug' => []]],
    '/blog/php' => ['uri' => '/blog/[[...slug]]', 'params' => ['slug' => ['php']]],
];

foreach ($cases as $path => $expected) {
    $match = $matcher->match($path);
    $assert($match !== null, "Expected route [{$path}] to match.");
    $assert($match->page->uri === $expected['uri'], "Route [{$path}] matched [{$match->page->uri}], expected [{$expected['uri']}].");
    $assert($match->parameters === $expected['params'], "Route [{$path}] parameters did not match.");
}

$assert($matcher->match('/missing-page') === null, 'Missing route should not match.');
$assert(is_file($app->storagePath('framework/routes.php')), 'Route cache was not generated.');

$cachedMatcher = new \Nova\Routing\RouteMatcher($app);
$cached = $cachedMatcher->match('/products/mobile/iphone');
$assert($cached !== null && $cached->parameters['category'] === 'mobile', 'Cached route did not match.');

echo "Route matcher smoke test passed." . PHP_EOL;
