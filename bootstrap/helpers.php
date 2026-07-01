<?php

declare(strict_types=1);

use Nova\Application\Application;
use Nova\Database\DatabaseManager;
use Nova\Http\RedirectResponse;
use Nova\Http\Response;
use Nova\Routing\Router;
use Nova\Support\Arr;
use Nova\Validation\Validator;
use Nova\View\ViewResponse;

function app(mixed $abstract = null): mixed
{
    static $app = null;

    if ($abstract instanceof Application) {
        $app = $abstract;
        return $app;
    }

    if (!$app) {
        $app = require dirname(__DIR__) . '/bootstrap/app.php';
    }

    return $abstract ? $app->make($abstract) : $app;
}

function base_path(string $path = ''): string
{
    return dirname(__DIR__) . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
}

function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false || $value === null) {
        return $default;
    }

    return match (strtolower((string) $value)) {
        'true' => true,
        'false' => false,
        'null' => null,
        'empty' => '',
        default => $value,
    };
}

function config(string $key, mixed $default = null): mixed
{
    return app()->config()->get($key, $default);
}

function request(?string $key = null, mixed $default = null): mixed
{
    $request = app()->request();
    return $key === null ? $request : $request->input($key, $default);
}

function response(mixed $body = '', int $status = 200, array $headers = []): Response
{
    return new Response((string) $body, $status, $headers);
}

function json(mixed $data, int $status = 200, array $headers = []): Response
{
    $headers['Content-Type'] = $headers['Content-Type'] ?? 'application/json; charset=UTF-8';
    return new Response(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), $status, $headers);
}

function redirect(string $to, int $status = 302): RedirectResponse
{
    return new RedirectResponse($to, $status);
}

function view(string $name, array $data = [], int $status = 200): ViewResponse
{
    return new ViewResponse(app()->view(), $name, $data, $status);
}

function render(string $template, array $data = []): string
{
    return app()->view()->render($template, $data);
}

function db(?string $connection = null): \Nova\Database\Connection
{
    return app()->make(DatabaseManager::class)->connection($connection);
}

function session(?string $key = null, mixed $default = null): mixed
{
    $session = app()->session();
    return $key === null ? $session : $session->get($key, $default);
}

function cookie(string $name, mixed $value = null, array $options = []): mixed
{
    if (func_num_args() === 1) {
        return $_COOKIE[$name] ?? null;
    }

    return response()->cookie($name, (string) $value, $options);
}

function flash(string $key, mixed $value = null): mixed
{
    $session = app()->session();
    if (func_num_args() === 1) {
        return $session->flash($key);
    }
    $session->flash($key, $value);
    return null;
}

function old(?string $key = null, mixed $default = null): mixed
{
    $old = session('_old_input', []);
    return $key === null ? $old : Arr::get($old, $key, $default);
}

function csrf(): string
{
    return app()->csrf()->token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . htmlspecialchars(csrf(), ENT_QUOTES, 'UTF-8') . '">';
}

function validate(array $data, array $rules, array $messages = []): array
{
    return Validator::make($data, $rules, $messages)->validate();
}

function abort(int $status = 404, ?string $message = null): never
{
    throw new \Nova\Exceptions\HttpException($status, $message);
}

function asset(string $path): string
{
    $path = ltrim($path, '/');
    $manifest = app()->basePath('public/assets/manifest.json');

    if (is_file($manifest)) {
        $assets = json_decode((string) file_get_contents($manifest), true);
        if (isset($assets[$path]['file'])) {
            return url('/assets/' . $assets[$path]['file']);
        }
    }

    return url('/' . $path);
}

function url(string $path = ''): string
{
    $base = rtrim((string) config('app.url', ''), '/');
    return $base . '/' . ltrim($path, '/');
}

function route(string $name, array $params = []): string
{
    return app()->make(Router::class)->url($name, $params);
}

function logger(?string $channel = null): \Nova\Support\Logger
{
    return app()->logger($channel);
}

function cache(?string $key = null, mixed $default = null): mixed
{
    $cache = app()->cache();
    return $key === null ? $cache : $cache->get($key, $default);
}

function auth(): \Nova\Security\AuthManager
{
    return app()->auth();
}

function storage(string $path = ''): string
{
    return app()->storagePath($path);
}

function upload(string $field): \Nova\Http\UploadedFile|array|null
{
    return request()->file($field);
}
