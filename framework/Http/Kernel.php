<?php

declare(strict_types=1);

namespace Nova\Http;

use Nova\Application\Application;
use Nova\Exceptions\HttpException;
use Nova\Middleware\Pipeline;
use Nova\Routing\Router;

final class Kernel
{
    public function __construct(private readonly Application $app)
    {
    }

    public function handle(Request $request): Response
    {
        $this->app->setRequest($request);

        try {
            $middleware = $this->app->config()->get('middleware.global', []);
            return (new Pipeline($this->app))
                ->send($request)
                ->through($middleware)
                ->then(fn (Request $request) => $this->app->make(Router::class)->dispatch($request));
        } catch (HttpException $exception) {
            return $this->error($request, $exception->status(), $exception->getMessage());
        } catch (\Throwable $exception) {
            $this->app->logger()->error($exception->getMessage(), ['exception' => $exception::class]);
            if ($this->app->config()->get('app.debug', false)) {
                return $this->error($request, 500, $exception->getMessage() . "\n\n" . $exception->getTraceAsString());
            }
            return $this->error($request, 500, 'Server Error');
        }
    }

    private function error(Request $request, int $status, string $message): Response
    {
        if ($request->expectsJson()) {
            return json(['error' => $message ?: 'Error', 'status' => $status], $status);
        }

        return response('<h1>' . $status . '</h1><pre>' . htmlspecialchars($message ?: 'Error', ENT_QUOTES, 'UTF-8') . '</pre>', $status);
    }
}
