<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$app = require dirname(__DIR__) . '/bootstrap/app.php';
app($app);

$assert = function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
};

$dispatch = function (\Nova\Http\Request $request) use ($app): \Nova\Http\Response {
    $app->setRequest($request);
    app()->session()->start();

    $csrf = new \Nova\Middleware\VerifyCsrfToken();
    $actions = new \Nova\Middleware\DispatchServerAction();

    return $csrf->handle($request, fn (\Nova\Http\Request $request): \Nova\Http\Response => $actions->handle(
        $request,
        fn (): \Nova\Http\Response => response('next', 404)
    ));
};

app()->session()->start();
$token = app()->csrf()->token();

$jsonRequest = new \Nova\Http\Request(
    'POST',
    '/users/actions/save',
    [],
    ['name' => 'Anmol'],
    [],
    [],
    [
        'HTTP_ACCEPT' => 'application/json',
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_CSRF_TOKEN' => $token,
        'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
    ]
);

$jsonResponse = $dispatch($jsonRequest);
$json = json_decode($jsonResponse->content(), true);
$assert($jsonResponse->status() === 200, 'Fetch action should return 200.');
$assert(($json['ok'] ?? false) === true, 'Fetch action should return ok JSON.');
$assert(($json['data']['name'] ?? null) === 'Anmol', 'Fetch action should return action data.');

$htmlRequest = new \Nova\Http\Request(
    'POST',
    '/users/actions/save',
    [],
    ['name' => 'Form User', '_token' => $token],
    [],
    [],
    ['HTTP_REFERER' => 'http://localhost/users']
);

$htmlResponse = $dispatch($htmlRequest);
$assert($htmlResponse instanceof \Nova\Http\RedirectResponse, 'Form action should redirect.');
$assert($htmlResponse->status() === 302, 'Successful form action should use redirect status.');

$invalidJsonRequest = new \Nova\Http\Request(
    'POST',
    '/users/actions/save',
    [],
    [],
    [],
    [],
    [
        'HTTP_ACCEPT' => 'application/json',
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_CSRF_TOKEN' => $token,
    ]
);

$invalidJsonResponse = $dispatch($invalidJsonRequest);
$invalidJson = json_decode($invalidJsonResponse->content(), true);
$assert($invalidJsonResponse->status() === 422, 'Invalid fetch action should return 422.');
$assert(isset($invalidJson['errors']['name']), 'Invalid fetch action should return validation errors.');

$invalidHtmlRequest = new \Nova\Http\Request(
    'POST',
    '/users/actions/save',
    [],
    ['_token' => $token],
    [],
    [],
    ['HTTP_REFERER' => 'http://localhost/users']
);

$invalidHtmlResponse = $dispatch($invalidHtmlRequest);
$assert($invalidHtmlResponse instanceof \Nova\Http\RedirectResponse, 'Invalid form action should redirect back.');
$assert($invalidHtmlResponse->status() === 303, 'Invalid form action should use see-other redirect.');

try {
    $dispatch(new \Nova\Http\Request(
        'POST',
        '/users/actions/save',
        [],
        ['name' => 'Bad Token'],
        [],
        [],
        ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json']
    ));
    $assert(false, 'Missing CSRF token should fail.');
} catch (\Nova\Exceptions\HttpException $exception) {
    $assert($exception->status() === 419, 'Missing CSRF token should throw 419.');
}

echo "Server action smoke test passed." . PHP_EOL;
