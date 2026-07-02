<?php

declare(strict_types=1);

namespace Nova\Container;

use ReflectionClass;

/**
 * Resolves services from Nova dependency bindings.
 */
class Container
{
    private array $bindings = [];
    private array $instances = [];
    private static array $reflectors = [];

    public function bind(string $abstract, callable|string|null $concrete = null): void
    {
        $this->bindings[$abstract] = ['concrete' => $concrete ?? $abstract, 'shared' => false];
    }

    public function singleton(string $abstract, callable|string|null $concrete = null): void
    {
        $this->bindings[$abstract] = ['concrete' => $concrete ?? $abstract, 'shared' => true];
    }

    public function instance(string $abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    public function make(string $abstract): mixed
    {
        if (array_key_exists($abstract, $this->instances)) {
            return $this->instances[$abstract];
        }

        if ($abstract !== \Nova\Support\Profiler::class) {
            $profile = $this->instanceOfProfiler();
            if ($profile !== null) {
                $profile->record('container.resolve:' . $abstract);
            }
        }

        $binding = $this->bindings[$abstract] ?? ['concrete' => $abstract, 'shared' => false];
        $object = is_callable($binding['concrete'])
            ? $binding['concrete']($this)
            : $this->build($binding['concrete']);

        if ($binding['shared']) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    private function build(string $class): object
    {
        $profile = $this->instanceOfProfiler();
        if ($profile !== null) {
            $profile->record('container.build:' . $class);
        }

        $reflector = self::$reflectors[$class] ??= new ReflectionClass($class);
        $constructor = $reflector->getConstructor();

        if (!$constructor) {
            return new $class();
        }

        $dependencies = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $dependencies[] = $this->make($type->getName());
                continue;
            }
            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
                continue;
            }
            throw new ContainerException("Cannot resolve {$parameter->getName()} for {$class}");
        }

        return $reflector->newInstanceArgs($dependencies);
    }

    private function instanceOfProfiler(): ?\Nova\Support\Profiler
    {
        if (!$this instanceof \Nova\Application\Application) {
            return null;
        }

        return $this->instances[\Nova\Support\Profiler::class] ?? null;
    }
}
