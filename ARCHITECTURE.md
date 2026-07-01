# Nova PHP Framework Architecture

Nova is a lightweight full stack PHP framework for PHP 8.3+, Composer, and ordinary shared hosting. It is inspired by the developer experience of Next.js App Router, but it stays native to PHP: request-response execution, no long-running process requirement, no server modules, no container runtime, and no mandatory ORM.

Nova applications are primarily built by creating files and functions. Framework internals may use classes, but application code should feel direct, functional, and easy to read.

## 1. Complete Architecture

### Core Philosophy

Nova is file-first, function-friendly, and shared-hosting-safe.

- File-first: folders and files define routes, layouts, API endpoints, middleware, actions, and components.
- Functional app code: most application files return responses, views, arrays, closures, or component output.
- Small internal kernel: OOP is used internally for isolation, testability, and extension points.
- No required ORM: database access centers on PDO, prepared statements, procedures, transactions, and a small query builder.
- Static-hosting adjacent, but dynamic PHP: public assets are static, while PHP requests enter through one front controller.
- Progressive complexity: simple apps remain simple; larger apps can use feature folders, service providers, plugins, events, and cached manifests.

### High-Level Layers

1. Public entry layer
   - `public/index.php`
   - Loads Composer, bootstrap, environment, config, application kernel.

2. Bootstrap layer
   - Creates the application instance.
   - Loads environment and configuration.
   - Registers framework services and app providers.
   - Builds or reads route/config/view manifests.

3. HTTP kernel
   - Converts PHP globals into a `Request`.
   - Runs global middleware.
   - Resolves a file route.
   - Runs route and segment middleware.
   - Invokes page/API/action file.
   - Normalizes result into a `Response`.
   - Sends response.

4. Routing layer
   - Scans `app/` and `api/`.
   - Supports static segments, dynamic segments, catch-all segments, route groups, nested layouts, loading/error/not-found pages, and middleware.
   - Can run dynamically in development or from cached manifests in production.

5. Rendering layer
   - Loads page output.
   - Applies nested layouts from root to leaf.
   - Renders components and templates.
   - Escapes output by default.

6. Infrastructure services
   - Container, config, env, session, cookies, cache, logger, database, validation, auth, events, filesystem, uploads, CSRF, error handling.

7. Developer API
   - Global helper functions expose the common surface: `view()`, `json()`, `redirect()`, `db()`, `request()`, `session()`, `auth()`, `validate()`, and related helpers.

## 2. Complete Folder Structure

Recommended project structure:

```text
project/
  app/
    layout.php
    page.php
    not-found.php
    error.php
    middleware.php
    loading.php
    dashboard/
      layout.php
      page.php
      middleware.php
      components/
      actions/
    users/
      page.php
      [id]/
        page.php
        edit/
          page.php
    (marketing)/
      layout.php
      about/
        page.php
    [...slug]/
      page.php

  api/
    users.php
    login.php
    orders/
      index.php
      [id].php

  components/
    Button.php
    Form/
      Input.php

  config/
    app.php
    auth.php
    cache.php
    database.php
    filesystems.php
    logging.php
    middleware.php
    session.php
    view.php

  bootstrap/
    app.php
    providers.php
    helpers.php

  framework/
    Application/
    Console/
    Container/
    Config/
    Database/
    Events/
    Exceptions/
    Filesystem/
    Http/
    Middleware/
    Routing/
    Security/
    Session/
    Support/
    Validation/
    View/

  database/
    migrations/
    procedures/
    seeders/

  plugins/

  public/
    index.php
    assets/
    favicon.ico
    .htaccess

  resources/
    views/
    templates/
    lang/
    css/
    js/

  routes/
    console.php

  storage/
    app/
    cache/
    framework/
      config/
      routes/
      views/
    logs/
    sessions/
    uploads/

  tests/
    Feature/
    Unit/

  vendor/
  .env
  .env.example
  composer.json
  nova
  package.json
  vite.config.js
```

### Folder Rules

- `app/` owns browser-facing pages and nested layouts.
- `api/` owns JSON/API endpoints.
- `components/` owns globally reusable PHP components.
- Feature-local `components/`, `actions/`, and `middleware/` folders belong to their parent route segment.
- `framework/` contains framework internals when developing Nova itself. Distributed apps normally receive this through Composer.
- `storage/` must be writable on shared hosting.
- `public/` is the web root when hosting allows it. If hosting cannot point to `public/`, Nova provides a safe fallback front controller at project root with clear deployment warnings.

## 3. Request Lifecycle

1. Web server receives a request.
2. Request is routed to `public/index.php`.
3. Composer autoload is loaded.
4. `bootstrap/app.php` creates `Application`.
5. Environment is loaded from `.env`.
6. Configuration is loaded from cached config or `config/*.php`.
7. `Http\Kernel` captures PHP globals into an immutable-ish `Request`.
8. Session, cookies, CSRF context, and uploaded files are initialized.
9. Global middleware pipeline runs.
10. Router resolves the matching page or API file.
11. Segment middleware pipeline runs.
12. Route file is executed in a scoped context.
13. Result is normalized into `Response`.
14. Exception handler converts failures into error responses.
15. Response headers, cookies, session writes, and body are sent.

## 4. Routing Lifecycle

### Route Discovery

Nova scans route roots:

- `app/**/page.php` for browser pages.
- `app/**/layout.php` for nested layouts.
- `app/**/middleware.php` for segment middleware.
- `app/**/not-found.php` for scoped 404 handling.
- `app/**/error.php` for scoped error handling.
- `api/**/*.php` for API endpoints.

### Segment Syntax

- `about/page.php` -> `/about`
- `users/[id]/page.php` -> `/users/{id}`
- `docs/[...slug]/page.php` -> `/docs/*`
- `(marketing)/about/page.php` -> `/about`, with route group layout support.
- `_private/` -> ignored by route discovery.

### HTTP Methods

Page routes default to `GET` and `HEAD`. Mutation behavior should be handled by colocated action files or API routes.

API files may return method maps:

```php
return [
    'GET' => fn () => json([...]),
    'POST' => fn () => json([...], 201),
];
```

### Route Manifest

In development, routes may be discovered on demand. In production, `php nova route:cache` creates a manifest containing:

- path pattern
- parameter names
- route file path
- layout chain
- middleware chain
- error boundary chain
- route name
- accepted methods

## 5. Rendering Lifecycle

1. Matched `page.php` is executed.
2. Page may return:
   - `view('name', data)`
   - `render('template', data)`
   - a string
   - a `Response`
   - an array, normalized to JSON only for API routes
3. Route data is passed into the leaf view.
4. Layout chain is built from root to leaf.
5. Leaf content is injected into nearest layout slot.
6. Parent layouts wrap child output in order.
7. Components are rendered as isolated callable templates.
8. Final HTML is wrapped in a `Response`.

### Layout Example

```text
app/layout.php
app/dashboard/layout.php
app/dashboard/users/page.php
```

Render order:

1. `users/page.php`
2. `dashboard/layout.php`
3. root `layout.php`

## 6. Configuration Lifecycle

1. `.env` is loaded first.
2. `config/*.php` files are loaded as arrays.
3. Environment values are read only during config loading.
4. Runtime code uses `config()`, not `env()`, for stable behavior.
5. Providers can merge package/plugin config.
6. `php nova config:cache` writes a single optimized config file.
7. Production reads cached config if present.

Configuration rules:

- Config files must be deterministic.
- Closures are not allowed in cached configuration.
- Environment names should be simple: `APP_ENV`, `APP_DEBUG`, `APP_URL`, `DB_HOST`.

## 7. Middleware Lifecycle

Middleware exists at four levels:

1. Global middleware from `config/middleware.php`
2. App root middleware from `app/middleware.php`
3. Segment middleware from nested route folders
4. Route-local middleware returned by page/API metadata

Middleware signature:

```php
function (Request $request, callable $next): Response
```

Functional middleware is preferred for app code. Class middleware is supported for reusable packages.

Execution:

1. Global middleware wraps everything.
2. Segment middleware runs from parent to child.
3. Route-local middleware runs last before route execution.
4. Response travels back outward through the same stack.

## 8. Authentication Lifecycle

Nova authentication is guard-based but lightweight.

1. Auth config defines guards and providers.
2. Default shared-hosting guard uses session cookies.
3. API guard may use bearer tokens.
4. Middleware calls `auth()->check()`.
5. User provider retrieves users through configured callback, query builder, or custom provider.
6. Login regenerates session ID.
7. Logout clears auth session keys and remember tokens.
8. CSRF protection applies to state-changing browser requests.

Core helpers:

- `auth()->user()`
- `auth()->id()`
- `auth()->check()`
- `auth()->guest()`
- `auth()->attempt($credentials)`
- `auth()->login($user)`
- `auth()->logout()`

Nova should not force a user model base class.

## 9. Database Lifecycle

1. Database config defines named connections.
2. `db()` lazily opens PDO connections.
3. Connections use exceptions, prepared statements, and UTF-8 defaults.
4. Query builder builds simple SQL for common operations.
5. Raw SQL remains first-class.
6. Stored procedures are first-class.
7. Transactions wrap callbacks and automatically commit or roll back.

Supported API:

- `db()->select($table)->where(...)->get()`
- `db()->table($table)->insert($data)`
- `db()->table($table)->update($data, $where)`
- `db()->table($table)->delete($where)`
- `db()->statement($sql, $bindings)`
- `db()->query($sql, $bindings)`
- `db()->procedure($name, $params)`
- `db()->transaction(fn () => ...)`

Design constraints:

- No mandatory active record.
- No magic model lifecycle.
- No hidden schema assumptions.
- Portable enough for MySQL/MariaDB on shared hosting.

## 10. Template Engine Lifecycle

Nova templates are compiled PHP with minimal syntax.

### Syntax

- `{{ title }}` escaped output
- `{!! html !!}` raw output
- `{{ user.name }}` nested array/object access
- `@if(condition)` / `@elseif(condition)` / `@else` / `@endif`
- `@foreach(users as user)` / `@endforeach`
- `@include('name', data)`
- `@component('Button', props)` / `@endcomponent`
- `@slot('name')` / `@endslot`
- `@extends('layout')`
- `@section('content')` / `@endsection`
- `@yield('content')`

### Compilation

1. Template path is resolved.
2. Cache key is generated from path and modified time.
3. Template is tokenized.
4. Supported directives are compiled to PHP.
5. Compiled file is written to `storage/framework/views`.
6. Compiled template is included in an isolated scope.
7. Output is captured and returned.

Security:

- Escaped output is default.
- Raw output is visually explicit.
- Include/component names are resolved through configured paths, not arbitrary filesystem paths.

## 11. Asset Compilation Lifecycle

Nova does not require Node at runtime. Vite is an optional development/build integration.

Development:

1. Developer runs `npm run dev`.
2. Vite dev server serves assets locally.
3. `asset()` checks Vite dev manifest or dev server URL.

Production:

1. Developer runs `npm run build`.
2. Vite writes hashed assets to `public/assets`.
3. Vite manifest is written to `public/assets/manifest.json`.
4. `asset('resources/js/app.js')` resolves to hashed production asset.

Shared hosting:

- Upload built `public/assets`.
- No Node process required.
- If no Vite manifest exists, `asset()` falls back to public path resolution.

## 12. Deployment Lifecycle

Preferred shared hosting deployment:

1. Run Composer locally or on host.
2. Run `php nova config:cache`.
3. Run `php nova route:cache`.
4. Run `php nova view:cache` when desired.
5. Run `npm run build` if using Vite.
6. Upload project files.
7. Point document root to `public/`.
8. Ensure `storage/` is writable.
9. Set production `.env`.
10. Verify `APP_DEBUG=false`.

Fallback deployment when document root cannot be changed:

- Place a minimal root `index.php` that forwards to `public/index.php`.
- Block direct access to sensitive folders using `.htaccess` where Apache is available.
- Nova should warn that this is less ideal and provide generated deny rules.

Runtime requirements:

- PHP 8.3+
- Composer autoload
- writable `storage/`
- PDO driver for selected database

## 13. CLI Lifecycle

The `nova` executable is a plain PHP CLI script.

Command lifecycle:

1. Load Composer.
2. Bootstrap application in console mode.
3. Load command registry.
4. Parse command name, arguments, and options.
5. Resolve command handler.
6. Execute command.
7. Return exit code.

Core commands:

- `php nova serve`
- `php nova make:page users/[id]`
- `php nova make:component Button`
- `php nova make:middleware auth`
- `php nova make:migration create_users_table`
- `php nova make:procedure sync_inventory`
- `php nova cache:clear`
- `php nova route:list`
- `php nova route:cache`
- `php nova config:cache`
- `php nova view:cache`

`serve` uses PHP's built-in server for local development only.

## 14. Extension System

Extensions are registered through service providers.

Provider responsibilities:

- bind services into the container
- register middleware
- register commands
- merge configuration
- register view paths
- register components
- register event listeners
- publish assets/config/templates

Provider phases:

1. `register(Application $app)` for container bindings.
2. `boot(Application $app)` for behavior that depends on registered services.

Application providers are listed in `bootstrap/providers.php`.

## 15. Plugin Architecture

Plugins are Composer packages or local folders under `plugins/`.

Plugin manifest:

```php
return [
    'name' => 'vendor/plugin',
    'providers' => [
        Vendor\Plugin\PluginServiceProvider::class,
    ],
    'commands' => [
        Vendor\Plugin\Console\InstallCommand::class,
    ],
    'config' => [
        'plugin' => __DIR__ . '/config/plugin.php',
    ],
];
```

Plugin capabilities:

- add helper functions through Composer files autoload
- provide components
- provide middleware
- provide CLI commands
- provide migrations and procedures
- provide views/templates
- provide public assets
- hook into events

Plugin constraints:

- Plugins must not require long-running servers.
- Plugins must degrade cleanly on shared hosting.
- Plugins should avoid global state except through Nova services.

## 16. Coding Standards

Framework internals:

- PHP 8.3 strict types.
- PSR-4 autoloading.
- PSR-12 formatting.
- Small final classes by default, extensible contracts where needed.
- Constructor dependency injection internally.
- Exceptions over silent failure.
- Interfaces for replaceable services.
- No framework service should depend on app-specific code.

Application code:

- Prefer functions, closures, and returned values.
- Keep route files small.
- Move reusable behavior to colocated actions/components.
- Use helpers for common framework services.
- Use config values instead of reading environment directly.

## 17. Naming Conventions

Routes:

- lowercase URL segments
- dynamic folders use `[id]`
- catch-all folders use `[...slug]`
- route groups use `(name)`
- private folders start with `_`

Files:

- `page.php` for pages
- `layout.php` for layouts
- `middleware.php` for segment middleware
- `error.php` for scoped errors
- `not-found.php` for scoped 404s
- `loading.php` reserved for progressive rendering patterns

Components:

- PascalCase component names: `Button.php`, `UserCard.php`
- feature-local components live near the route that owns them

Config:

- lowercase file names
- dot access keys: `config('database.default')`

CLI:

- namespace-style commands: `make:page`, `cache:clear`, `route:list`

## 18. Public API Specification

### Response Helpers

- `view(string $name, array $data = [], int $status = 200): Response`
- `render(string $template, array $data = []): string`
- `json(mixed $data, int $status = 200, array $headers = []): Response`
- `redirect(string $to, int $status = 302): Response`
- `response(mixed $body = '', int $status = 200, array $headers = []): Response`
- `abort(int $status = 404, ?string $message = null): never`

### Request Helpers

- `request(?string $key = null, mixed $default = null): mixed`
- `old(?string $key = null, mixed $default = null): mixed`
- `flash(string $key, mixed $value = null): mixed`
- `csrf(): string`

### Service Helpers

- `db(?string $connection = null): DatabaseConnection`
- `session(?string $key = null, mixed $default = null): mixed`
- `cookie(string $name, mixed $value = null, array $options = []): mixed`
- `cache(?string $key = null, mixed $default = null): mixed`
- `auth(?string $guard = null): AuthGuard`
- `logger(?string $channel = null): Logger`

### Config and URL Helpers

- `env(string $key, mixed $default = null): mixed`
- `config(string $key, mixed $default = null): mixed`
- `asset(string $path): string`
- `url(string $path = ''): string`
- `route(string $name, array $params = []): string`
- `storage(string $path = ''): string`

### Validation and Files

- `validate(array $data, array $rules, array $messages = []): array`
- `upload(string $field): UploadedFile|array|null`
- `component(string $name, array $props = [], array $slots = []): string`

## 19. Framework Internals Specification

### Application

Owns lifecycle state, base paths, environment, service container, providers, and boot status.

### Kernel

Coordinates HTTP and console execution. Keeps request handling separate from routing details.

### Container

Small dependency container supporting:

- singleton bindings
- factory bindings
- instance bindings
- interface aliases
- contextual resolution where necessary

### Router and Route Loader

Router matches requests against route manifests. Route Loader discovers file routes and compiles route metadata.

### Request and Response

Request wraps method, URI, headers, query, body, files, cookies, session reference, and route params.

Response wraps status, headers, cookies, and content. Specialized responses include JSON, redirect, stream, file download, and view responses.

### View Engine

Resolves views, compiles templates, renders components, manages sections/slots, and handles layout composition.

### Database

Wraps PDO connections, query builder, transaction manager, stored procedure calls, and SQL logging hooks.

### Logger

PSR-3 compatible logging with file channels by default.

### Middleware Pipeline

Builds callable stacks for global, segment, and route middleware.

### Session Manager

Supports file-backed sessions by default, using PHP sessions with secure cookie configuration and flash data helpers.

### Authentication

Provides guards, user providers, password verification, remember tokens, and auth middleware.

### Validation

Rule-based validator with simple rules first:

- `required`
- `email`
- `min`
- `max`
- `between`
- `confirmed`
- `same`
- `unique`
- `exists`
- `file`
- `image`
- `mimes`

### Exception Handler

Maps exceptions to debug pages, JSON errors, scoped error pages, and production-safe responses.

### CLI

Command registry, input parser, output formatter, generators, cache commands, and route inspection.

### Compiler

Compiles route manifests, config cache, template cache, and optimized provider metadata.

## 20. Future Roadmap

### Version 1.0

- File-based routing
- Nested layouts
- API routes
- Middleware
- Sessions
- CSRF
- Validation
- Database layer
- Template engine
- CLI generators
- Config and route caching
- Vite manifest integration
- Shared-hosting deployment guide

### Version 1.x

- Form actions colocated with route segments
- Better route groups
- Named route generation improvements
- Built-in pagination helpers
- Mailer abstraction
- Queue facade with sync and database drivers
- Scheduled commands using cron-compatible runner
- Localization helpers

### Version 2.x

- Optional typed request DTOs
- Optional model layer without forcing ORM
- Plugin marketplace conventions
- Advanced template diagnostics
- Component previews
- Static route pre-rendering for eligible pages
- First-party admin/auth starter kit

### Long-Term Principles

- Never require a long-running PHP server.
- Never require Node in production.
- Keep file routing predictable.
- Keep application code friendly to beginners.
- Keep internals replaceable for advanced teams.
- Prefer visible behavior over magic.
- Avoid turning Nova into a clone of existing PHP frameworks.
