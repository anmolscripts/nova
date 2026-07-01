<?php

declare(strict_types=1);

namespace Nova\Middleware;

use Nova\Application\Application;

final class Pipeline
{
    private mixed $passable;
    private array $pipes = [];

    public function __construct(private readonly Application $app)
    {
    }

    public function send(mixed $passable): self
    {
        $this->passable = $passable;
        return $this;
    }

    public function through(array $pipes): self
    {
        $this->pipes = $pipes;
        return $this;
    }

    public function then(callable $destination): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            fn (callable $next, mixed $pipe) => fn (mixed $passable) => $this->callPipe($pipe, $passable, $next),
            $destination
        );

        return $pipeline($this->passable);
    }

    private function callPipe(mixed $pipe, mixed $passable, callable $next): mixed
    {
        if (is_string($pipe)) {
            $pipe = $this->app->make($pipe);
        }

        if (is_callable($pipe)) {
            return $pipe($passable, $next);
        }

        return $pipe->handle($passable, $next);
    }
}
