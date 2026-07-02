<?php

declare(strict_types=1);

namespace Nova\Http;

use Nova\Application\Application;
use Nova\Exceptions\ErrorHandler;
use Nova\Exceptions\HttpException;
use Nova\Middleware\Pipeline;
use Nova\Routing\Router;

/**
 * Runs the HTTP request through Nova middleware and routing.
 */
final class Kernel
{
    public function __construct(private readonly Application $app)
    {
    }

    public function handle(Request $request): Response
    {
        $this->app->setRequest($request);
        $this->app->profiler()->start();
        $this->app->profiler()->record('application.boot');

        try {
            $this->app->profiler()->record('request.received');
            $middleware = $this->app->config()->get('middleware.global', []);
            $response = (new Pipeline($this->app))
                ->send($request)
                ->through($middleware)
                ->then(fn (Request $request) => $this->app->make(Router::class)->dispatch($request));

            $this->app->profiler()->record('response.generated');
            return $response;
        } catch (HttpException $exception) {
            return $this->renderError($request, $exception);
        } catch (\Throwable $exception) {
            return $this->renderError($request, $exception);
        } finally {
            $this->app->profiler()->record('response.sent');
            $this->app->profiler()->finish();
        }
    }

    private function renderError(Request $request, \Throwable $exception): Response
    {
        $handler = new ErrorHandler($this->app);
        return $handler->render($exception);
    }
}
