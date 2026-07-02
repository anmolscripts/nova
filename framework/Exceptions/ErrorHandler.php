<?php

declare(strict_types=1);

namespace Nova\Exceptions;

use Nova\Application\Application;
use Nova\Http\Request;
use Nova\Http\Response;
use Nova\View\LatteEngine;

/**
 * Centralizes framework exception handling for HTTP and CLI requests.
 */
final class ErrorHandler
{
    /** @var array<int, string> */
    private array $statusPages = [
        400 => '400',
        401 => '401',
        403 => '403',
        404 => '404',
        419 => '419',
        422 => '422',
        429 => '429',
        500 => '500',
        503 => '503',
    ];

    public function __construct(private readonly Application $app)
    {
    }

    public function register(): void
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
    }

    public function handleError(int $severity, string $message, string $file = '', int $line = 0): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new \ErrorException($message, 0, $severity, $file, $line);
    }

    public function handleException(\Throwable $exception): void
    {
        $this->log($exception);

        $response = $this->render($exception);
        $response->send();
        exit(1);
    }

    public function render(\Throwable $exception): Response
    {
        $request = $this->app->request();
        $status = $this->statusCode($exception);

        if ($request->expectsJson()) {
            return $this->jsonResponse($exception, $status);
        }

        return $this->htmlResponse($exception, $status);
    }

    public function log(\Throwable $exception): void
    {
        $request = $this->app->request();
        $context = [
            'timestamp' => date('c'),
            'url' => $request->path(),
            'method' => $request->method(),
            'user_id' => $this->userId(),
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ];

        $this->app->logger()->error($exception->getMessage(), $context);
    }

    private function htmlResponse(\Throwable $exception, int $status): Response
    {
        if ($this->app->config()->get('app.debug', false)) {
            $content = $this->renderDebugPage($exception, $status);
            return response($content, $status);
        }

        $file = $this->statusPage($status);
        if ($file !== null) {
            $content = $this->renderStatusPage($file, $status, $exception->getMessage());
            return response($content, $status);
        }

        return response('<h1>' . $status . '</h1><p>' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>', $status);
    }

    private function jsonResponse(\Throwable $exception, int $status): Response
    {
        $payload = [
            'success' => false,
            'error' => [
                'type' => $exception::class,
                'message' => $this->app->config()->get('app.debug', false) ? $exception->getMessage() : 'An unexpected error occurred.',
                'code' => $status,
            ],
        ];

        return json($payload, $status);
    }

    private function renderDebugPage(\Throwable $exception, int $status): string
    {
        $request = $this->app->request();
        $context = [
            'exception' => $exception,
            'status' => $status,
            'request' => $request,
            'route' => $request->route('name') ?? null,
            'page' => $this->app->currentPage(),
            'action' => null,
            'componentTree' => null,
            'layoutChain' => null,
            'queries' => [],
            'executionTime' => round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4),
            'memoryUsage' => memory_get_usage(true),
            'environment' => $this->app->config()->get('app.env', 'production'),
        ];

        return $this->renderView('errors.debug', $context);
    }

    private function renderStatusPage(string $file, int $status, string $message): string
    {
        $path = $this->app->basePath('resources/errors/' . $file . '.latte');
        if (is_file($path)) {
            return $this->renderView($path, ['status' => $status, 'message' => $message]);
        }

        return '<h1>' . $status . '</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
    }

    private function renderView(string $view, array $data): string
    {
        if (str_ends_with($view, '.latte')) {
            return $this->app->make(LatteEngine::class)->render($view, $data);
        }

        $template = $this->app->config()->get('view.paths', []);
        if ($template === []) {
            return '<pre>' . htmlspecialchars((string) $data['message'] ?? '', ENT_QUOTES, 'UTF-8') . '</pre>';
        }

        return $this->app->view()->render($view, $data);
    }

    private function statusPage(int $status): ?string
    {
        if (!isset($this->statusPages[$status])) {
            return null;
        }

        $path = $this->app->basePath('resources/errors/' . $this->statusPages[$status] . '.latte');
        return is_file($path) ? $this->statusPages[$status] : null;
    }

    private function statusCode(\Throwable $exception): int
    {
        if ($exception instanceof HttpException) {
            return $exception->status();
        }

        if ($exception instanceof \ErrorException) {
            return 500;
        }

        return 500;
    }

    private function userId(): mixed
    {
        return $this->app->auth()->user()['id'] ?? null;
    }
}
