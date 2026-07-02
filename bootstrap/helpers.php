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
use Nova\Storage\Disk;
use Nova\Storage\Image;
use Nova\Storage\StorageManager;
use Nova\Storage\UploadFile;
use Nova\Support\Arr;
use Nova\Support\Profiler;
use Nova\Validation\Validator;
use Nova\Validation\ErrorBag;
use Nova\View\Asset;
use Nova\View\ViewResponse;

/**
 * Get the application instance or resolve a service from the container.
 */
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

/**
 * Build an absolute path relative to the project root.
 */
function base_path(string $path = ''): string
{
    return dirname(__DIR__) . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
}

/**
 * Read an environment value with common string booleans normalized.
 */
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

/**
 * Read a configuration value using dot notation.
 */
function config(string $key, mixed $default = null): mixed
{
    return app()->config()->get($key, $default);
}

/**
 * Get the current request profiler.
 */
function profiler(): ?Profiler
{
    $app = app();
    return $app instanceof Application ? $app->profiler() : null;
}

/**
 * Get the current request or one input value.
 */
function request(?string $key = null, mixed $default = null): mixed
{
    $request = app()->request();
    if ($key === null) {
        return $request;
    }

    $value = $request->input($key);

    return $value === null ? old($key, $default) : $value;
}

/**
 * Create a plain HTTP response.
 *
 * @param array<string,string> $headers
 */
function response(mixed $body = '', int $status = 200, array $headers = []): Response
{
    return new Response((string) $body, $status, $headers);
}

/**
 * Create a JSON HTTP response.
 *
 * @param array<string,string> $headers
 */
function json(mixed $data, int $status = 200, array $headers = []): Response
{
    $headers['Content-Type'] = $headers['Content-Type'] ?? 'application/json; charset=UTF-8';
    return new Response(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), $status, $headers);
}

/**
 * Create an HTTP redirect response.
 */
function redirect(string $to, int $status = 302): RedirectResponse
{
    return new RedirectResponse($to, $status);
}

/**
 * Create a response that renders a view.
 *
 * @param array<string,mixed> $data
 */
function view(string $name, array $data = [], int $status = 200): ViewResponse
{
    return new ViewResponse(app()->view(), $name, $data, $status);
}

/**
 * Render a template to a string.
 *
 * @param array<string,mixed> $data
 */
function render(string $template, array $data = []): string
{
    return app()->view()->render($template, $data);
}

/**
 * Get the database manager or a named database connection.
 */
function db(?string $connection = null): DatabaseManager|\Nova\Database\Connection
{
    $manager = app()->make(DatabaseManager::class);
    return $connection === null ? $manager : $manager->connection($connection);
}

/**
 * Get the session manager, read a session value, or write a session value.
 */
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

/**
 * Remove a value from the session.
 */
function forget(string $key): void
{
    app()->session()->forget($key);
}

/**
 * Destroy the current session.
 */
function destroy(): void
{
    app()->session()->destroy();
}

/**
 * Regenerate the current session identifier.
 */
function regenerate(): void
{
    app()->session()->regenerate();
}

/**
 * Read a cookie or queue a cookie on a new response.
 *
 * @param array<string,mixed> $options
 */
function cookie(string $name, mixed $value = null, array $options = []): mixed
{
    if (func_num_args() === 1) {
        return $_COOKIE[$name] ?? null;
    }

    return response()->cookie($name, (string) $value, $options);
}

/**
 * Get all flash data, read one flash value, or flash one value.
 */
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

/**
 * Read old input flashed from the previous request.
 */
function old(?string $key = null, mixed $default = null): mixed
{
    $old = flash('_old_input') ?? session('_old_input') ?? [];
    return $key === null ? $old : Arr::get($old, $key, $default);
}

/**
 * Get the current CSRF token.
 */
function csrf(): string
{
    return app()->csrf()->token();
}

/**
 * Render a hidden CSRF form input.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . htmlspecialchars(csrf(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Validate request input or the provided data array.
 *
 * @param array<string,mixed> $data
 * @param array<string,mixed>|null $rules
 * @param array<string,string> $messages
 * @return array<string,mixed>
 */
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

/**
 * Determine whether an array resembles validation rule definitions.
 *
 * @internal
 */
function looks_like_validation_rules(array $values): bool
{
    if ($values === []) {
        return false;
    }

    $known = [
        'required', 'nullable', 'string', 'integer', 'numeric', 'boolean', 'email', 'url', 'date',
        'min', 'max', 'between', 'confirmed', 'same', 'different', 'in', 'not_in', 'regex',
        'array', 'file', 'image', 'mimes', 'extensions',
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

/**
 * Stop request handling with an HTTP exception.
 */
function abort(int $status = 404, ?string $message = null): never
{
    throw new \Nova\Exceptions\HttpException($status, $message);
}

/**
 * Render Vite asset tags for a source asset.
 */
function asset(string $path): string
{
    return app()->make(Asset::class)->tags($path);
}

/**
 * Flash a success message or create a successful action result.
 *
 * @param array<string,mixed>|string $data
 */
function success(array|string $data = [], ?string $message = null, int $status = 200): mixed
{
    if (is_string($data) && $message === null) {
        flash('success', $data);
        return null;
    }

    return new ActionResult(true, $data, $status, $message);
}

/**
 * Flash a warning message.
 */
function warning(string $message): void
{
    flash('warning', $message);
}

/**
 * Flash an informational message.
 */
function info(string $message): void
{
    flash('info', $message);
}

/**
 * Flash a danger message.
 */
function danger(string $message): void
{
    flash('danger', $message);
}

/**
 * Create a failed action result.
 *
 * @param array<string,mixed>|string $errors
 */
function error(array|string $errors, int $status = 422, ?string $message = null): ActionResult
{
    return new ActionResult(false, is_array($errors) ? $errors : ['error' => [$errors]], $status, $message ?? 'Action failed.');
}

/**
 * Redirect back to the previous URL.
 */
function back(): RedirectResponse
{
    return redirect(back_url());
}

/**
 * Get the previous URL or the application URL.
 */
function back_url(): string
{
    return $_SERVER['HTTP_REFERER'] ?? url('/');
}

/**
 * Get the validation error bag or errors for one field.
 */
function errors(?string $key = null, mixed $default = []): mixed
{
    $bag = new ErrorBag(flash('errors') ?? []);

    return $key === null ? $bag : ($bag->get($key) ?: $default);
}

/**
 * Build an absolute application URL.
 */
function url(string $path = ''): string
{
    $base = rtrim((string) config('app.url', ''), '/');
    return $base . '/' . ltrim($path, '/');
}

/**
 * Get the current resolved page.
 */
function page(): ?Page
{
    return app()->currentPage();
}

/**
 * Get the current resolved layout.
 */
function layout(): ?Layout
{
    return app()->currentLayout();
}

/**
 * Get the current rendering component.
 */
function component(): ?Component
{
    return app()->currentComponent();
}

/**
 * Get route parameters, read one current route parameter, or generate a named route URL.
 *
 * @param array<string,mixed> $params
 */
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

/**
 * Get a logger for the default or named channel.
 */
function logger(?string $channel = null): \Nova\Support\Logger
{
    return app()->logger($channel);
}

/**
 * Get the cache repository or read one cached value.
 */
function cache(?string $key = null, mixed $default = null): mixed
{
    $cache = app()->cache();
    return $key === null ? $cache : $cache->get($key, $default);
}

/**
 * Determine whether a user is authenticated.
 */
function auth(): bool
{
    return app()->auth()->check();
}

/**
 * Get the authenticated user record.
 *
 * @return array<string,mixed>|null
 */
function user(): ?array
{
    return app()->auth()->user();
}

/**
 * Determine whether the current request is unauthenticated.
 */
function guest(): bool
{
    return app()->auth()->guest();
}

/**
 * Determine whether a user is authenticated.
 */
function check(): bool
{
    return app()->auth()->check();
}

/**
 * Get the authenticated user's identifier.
 */
function id(): mixed
{
    return app()->auth()->id();
}

/**
 * Log in a user by identifier.
 */
function login(mixed $id, bool $remember = false): void
{
    app()->auth()->login($id, $remember);
}

/**
 * Log out the current user.
 */
function logout(): void
{
    app()->auth()->logout();
}

/**
 * Attempt to authenticate with credentials.
 *
 * @param array<string,mixed> $credentials
 */
function attempt(array $credentials, bool $remember = false): bool
{
    return app()->auth()->attempt($credentials, $remember);
}

/**
 * Hash a password using PHP's default password hasher.
 */
function hash_password(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify a password against a password hash.
 */
function verify_password(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

/**
 * Determine whether a user can perform an ability on a subject.
 */
function can(string $ability, mixed $subject, mixed $user = null): bool
{
    return (new \Nova\Security\PolicyGate(app()))->allows($ability, $subject, $user);
}

/**
 * Determine whether a user cannot perform an ability on a subject.
 */
function cannot(string $ability, mixed $subject, mixed $user = null): bool
{
    return !can($ability, $subject, $user);
}

/**
 * Authorize an ability or throw an HTTP exception.
 */
function authorize(string $ability, mixed $subject, mixed $user = null): void
{
    (new \Nova\Security\PolicyGate(app()))->authorize($ability, $subject, $user);
}

/**
 * Get the storage manager, a named storage disk, or a legacy storage path.
 */
function storage(?string $disk = null): Disk|StorageManager|string
{
    $manager = app()->storage();

    if ($disk === null) {
        return $manager;
    }

    return config("storage.disks.{$disk}") !== null ? $manager->disk($disk) : app()->storagePath($disk);
}

/**
 * Get an uploaded file or uploaded file array from the request.
 *
 * @return UploadFile|array<int,UploadFile>|null
 */
function upload(string $field): UploadFile|array|null
{
    return request()->file($field);
}

/**
 * Create a stored-file download response.
 */
function download(string $path, ?string $name = null, bool $inline = false, string $disk = 'local'): \Nova\Http\DownloadResponse
{
    return storage($disk)->download($path, $name, $inline);
}

/**
 * Build a public storage asset URL.
 */
function asset_url(string $path): string
{
    return storage('public')->url($path);
}

/**
 * Create an image helper for a local image path.
 */
function image(string $path): Image
{
    return new Image($path);
}
