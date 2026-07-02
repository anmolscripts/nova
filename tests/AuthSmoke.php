<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$app = require dirname(__DIR__) . '/bootstrap/app.php';
app($app);
app()->session()->start();

@unlink($app->storagePath('framework/pages.php'));
@unlink($app->storagePath('framework/routes.php'));

$app->config()->set('database.default', 'auth_test');
$app->config()->set('database.connections.auth_test', [
    'driver' => 'sqlite',
    'database' => ':memory:',
]);
$app->config()->set('auth.provider.connection', 'auth_test');
$app->config()->set('auth.provider.table', 'users');
$app->config()->set('auth.provider.identifier', 'email');
$app->config()->set('auth.provider.id', 'id');
$app->config()->set('auth.provider.password', 'password');
$app->config()->set('auth.provider.remember_token', 'remember_token');
$app->config()->set('auth.login', '/login');
$app->config()->set('auth.redirect', '/');

$assert = function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
};

db()->statement('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT NOT NULL, password TEXT NOT NULL, role TEXT NOT NULL, remember_token TEXT)');
db()->insert('users', [
    'email' => 'admin@example.com',
    'password' => hash_password('secret'),
    'role' => 'admin',
    'remember_token' => null,
]);
db()->insert('users', [
    'email' => 'user@example.com',
    'password' => hash_password('secret'),
    'role' => 'user',
    'remember_token' => null,
]);

$assert(guest(), 'Initial state should be guest.');
$assert(!attempt(['email' => 'admin@example.com', 'password' => 'bad']), 'Invalid attempt should fail.');
$assert(attempt(['email' => 'admin@example.com', 'password' => 'secret'], true), 'Valid attempt should pass.');
$assert(auth(), 'auth() should be true after attempt.');
$assert(check(), 'check() should be true after attempt.');
$assert((int) id() === 1, 'id() should return authenticated id.');
$assert(user()['email'] === 'admin@example.com', 'user() should load provider user.');

$rememberHash = db()->table('users')->where('id', 1)->first()['remember_token'] ?? null;
$assert(is_string($rememberHash) && $rememberHash !== '', 'Remember token should be stored.');

$product = ['type' => 'Product', 'id' => 10];
$assert(can('update', $product), 'Admin should pass policy.');
$assert(!cannot('update', $product), 'cannot() should invert can().');
authorize('update', $product);

logout();
$assert(guest(), 'logout() should clear auth state.');

$token = 'known-token';
app()->auth()->provider()->updateRememberToken(1, $token);
$_COOKIE[config('auth.remember.cookie')] = base64_encode('1|' . $token);
app()->auth()->restoreFromRememberCookie();
$assert((int) id() === 1, 'Remember cookie should restore user id.');
unset($_COOKIE[config('auth.remember.cookie')]);

logout();
login(2);
$assert(user()['email'] === 'user@example.com', 'login(id) should authenticate by id.');
$assert(!can('update', $product), 'Non-admin should fail policy.');
try {
    authorize('update', $product);
    $assert(false, 'authorize() should abort unauthorized users.');
} catch (\Nova\Exceptions\HttpException $exception) {
    $assert($exception->status() === 403, 'authorize() should throw 403.');
}
logout();

$kernel = new \Nova\Http\Kernel($app);
$protected = $kernel->handle(new \Nova\Http\Request('GET', '/protected', [], [], [], [], []));
$assert($protected instanceof \Nova\Http\RedirectResponse && $protected->status() === 302, 'auth middleware should redirect guests.');

login(1);
$protectedOk = $kernel->handle(new \Nova\Http\Request('GET', '/protected', [], [], [], [], []));
$assert($protectedOk->status() === 200 && str_contains($protectedOk->content(), 'Protected'), 'auth middleware should allow authenticated users.');

$loginPage = $kernel->handle(new \Nova\Http\Request('GET', '/login', [], [], [], [], []));
$assert($loginPage instanceof \Nova\Http\RedirectResponse && $loginPage->status() === 302, 'guest middleware should redirect authenticated users.');

$latte = app()->make(\Nova\View\LatteEngine::class)->render('app/protected/page.latte', ['title' => 'Protected']);
$assert(str_contains($latte, 'Protected'), 'Latte render should still work with auth globals.');

echo "Auth smoke test passed." . PHP_EOL;
