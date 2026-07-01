<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$app = require dirname(__DIR__) . '/bootstrap/app.php';
app($app);

$discovery = $app->make(\Nova\App\PageDiscovery::class);
@unlink($app->storagePath('framework/pages.php'));

$pages = $discovery->pages();
$byUri = [];
foreach ($pages as $page) {
    $byUri[$page->uri] = $page;
}

$assert = function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
};

$assert(isset($byUri['/']), 'Root page was not discovered.');
$assert(count($byUri['/']->layoutFiles) === 1, 'Root page should have one layout.');

$assert(isset($byUri['/users']), 'Nested users page was not discovered.');
$assert(count($byUri['/users']->layoutFiles) === 2, 'Users page should have root and users layouts.');

$assert(isset($byUri['/admin/reports/sales']), 'Three-level sales page was not discovered.');
$assert(count($byUri['/admin/reports/sales']->layoutFiles) === 3, 'Sales page should have root, admin, and reports layouts.');

$engine = $app->make(\Nova\App\LayoutEngine::class);
$content = $engine->render($byUri['/admin/reports/sales'], '<article data-page="sales">Sales</article>', ['title' => 'Sales']);

$root = strpos($content, 'nova-shell');
$admin = strpos($content, 'data-layout="admin"');
$reports = strpos($content, 'data-layout="reports"');
$page = strpos($content, 'data-page="sales"');

$assert($root !== false && $admin !== false && $reports !== false && $page !== false, 'Rendered output is missing layout markers.');
$assert($root < $admin && $admin < $reports && $reports < $page, 'Layouts did not wrap in root -> admin -> reports -> page order.');

$noLayout = new \Nova\App\Page(
    uri: '/no-layout',
    directory: $app->basePath('tests/fixtures/no-layout'),
    serverFile: null,
    templateFile: null,
    layoutFiles: [],
    typescriptFile: null,
    scssFile: null,
    loadingFile: null,
    errorFile: null,
    routeParameters: [],
    regex: '#^/no-layout$#'
);

$assert($engine->render($noLayout, '<p>No layout</p>', []) === '<p>No layout</p>', 'Pages without layouts should render directly.');

echo "Layout engine smoke test passed." . PHP_EOL;
