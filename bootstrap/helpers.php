<?php

declare(strict_types=1);

use Nova\Application\Application;
use Nova\Action\ActionResult;
use Nova\App\Layout;
use Nova\App\Page;
use Nova\Component\Component;
use Nova\Database\DatabaseManager;
use Nova\Http\RedirectResponse;
use Nova\Http\Response;
use Nova\Routing\Router;
use Nova\Support\Arr;
use Nova\Validation\Validator;
use Nova\Validation\ErrorBag;
use Nova\View\Asset;
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
    if ($key === null) {
        return $request;
    }

    $value = $request->input($key);

    return $value === null ? old($key, $default) : $value;
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

function db(?string $connection = null): DatabaseManager|\Nova\Database\Connection
{
    $manager = app()->make(DatabaseManager::class);
    return $connection === null ? $manager : $manager->connection($connection);
}

function session(?string $key = null, mixed $value = null): mixed
{
    $session = app()->session();
    if ($key === null) {
        return $session;
    }

    if (func_num_args() >= 2) {
        $session->put($key, $value);
        return null;
    }

    return $session->get($key);
}

function forget(string $key): void
{
    app()->session()->forget($key);
}

function destroy(): void
{
    app()->session()->destroy();
}

function regenerate(): void
{
    app()->session()->regenerate();
}

function cookie(string $name, mixed $value = null, array $options = []): mixed
{
    if (func_num_args() === 1) {
        return $_COOKIE[$name] ?? null;
    }

    return response()->cookie($name, (string) $value, $options);
}

function flash(?string $key = null, mixed $value = null): mixed
{
    $session = app()->session();
    if ($key === null) {
        return $session->get('_flash', []);
    }

    if (func_num_args() === 1) {
        return $session->flash($key);
    }
    $session->flash($key, $value);
    return null;
}

function old(?string $key = null, mixed $default = null): mixed
{
    $old = flash('_old_input') ?? session('_old_input') ?? [];
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

function validate(array $data, ?array $rules = null, array $messages = []): array
{
    if ($rules === null) {
        $rules = $data;
        $data = request()->all();
    } elseif (looks_like_validation_rules($data) && !looks_like_validation_rules($rules)) {
        $messages = $rules;
        $rules = $data;
        $data = request()->all();
    }

    return Validator::make($data, $rules, $messages)->validate();
}

function looks_like_validation_rules(array $values): bool
{
    if ($values === []) {
        return false;
    }

    $known = [
        'required', 'nullable', 'string', 'integer', 'numeric', 'boolean', 'email', 'url', 'date',
        'min', 'max', 'between', 'confirmed', 'same', 'different', 'in', 'not_in', 'regex',
        'array', 'file', 'image', 'mimes',
    ];

    foreach ($values as $value) {
        $rules = is_array($value) ? $value : explode('|', (string) $value);
        foreach ($rules as $rule) {
            $name = explode(':', (string) $rule, 2)[0];
            if (!in_array($name, $known, true)) {
                return false;
            }
        }
    }

    return true;
}

function abort(int $status = 404, ?string $message = null): never
{
    throw new \Nova\Exceptions\HttpException($status, $message);
}

function asset(string $path): string
{
    return app()->make(Asset::class)->tags($path);
}

function success(array|string $data = [], ?string $message = null, int $status = 200): mixed
{
    if (is_string($data) && $message === null) {
        flash('success', $data);
        return null;
    }

    return new ActionResult(true, $data, $status, $message);
}

function warning(string $message): void
{
    flash('warning', $message);
}

function info(string $message): void
{
    flash('info', $message);
}

function danger(string $message): void
{
    flash('danger', $message);
}

function error(array|string $errors, int $status = 422, ?string $message = null): ActionResult
{
    return new ActionResult(false, is_array($errors) ? $errors : ['error' => [$errors]], $status, $message ?? 'Action failed.');
}

function back(): RedirectResponse
{
    return redirect(back_url());
}

function back_url(): string
{
    return $_SERVER['HTTP_REFERER'] ?? url('/');
}

function errors(?string $key = null, mixed $default = []): mixed
{
    $bag = new ErrorBag(flash('errors') ?? []);

    return $key === null ? $bag : ($bag->get($key) ?: $default);
}

function url(string $path = ''): string
{
    $base = rtrim((string) config('app.url', ''), '/');
    return $base . '/' . ltrim($path, '/');
}

function page(): ?Page
{
    return app()->currentPage();
}

function layout(): ?Layout
{
    return app()->currentLayout();
}

function component(): ?Component
{
    return app()->currentComponent();
}

function route(?string $name = null, array $params = []): mixed
{
    if ($name === null) {
        return request()->routeParams();
    }

    if ($params === [] && request()->route($name) !== null) {
        return request()->route($name);
    }

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
