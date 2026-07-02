<?php

declare(strict_types=1);

namespace Nova\Security;

use Nova\Application\Application;

/**
 * Authorizes actions against application policies.
 */
final class PolicyGate
{
    public function __construct(private readonly Application $app)
    {
    }

    public function allows(string $ability, mixed $subject, mixed $user = null): bool
    {
        $user ??= $this->app->auth()->user();
        $policy = $this->policy($subject);

        if (!$policy || !method_exists($policy, $ability)) {
            return false;
        }

        return (bool) $policy->{$ability}($user, $subject);
    }

    public function authorize(string $ability, mixed $subject, mixed $user = null): void
    {
        if (!$this->allows($ability, $subject, $user)) {
            abort(403, 'Forbidden');
        }
    }

    private function policy(mixed $subject): ?object
    {
        $name = is_object($subject) ? (new \ReflectionClass($subject))->getShortName() : ucfirst((string) ($subject['type'] ?? ''));
        if ($name === '') {
            return null;
        }

        $file = $this->app->basePath('app/policies/' . $name . 'Policy.php');
        if (!is_file($file)) {
            return null;
        }

        require_once $file;
        $class = 'App\\Policies\\' . $name . 'Policy';

        return class_exists($class) ? new $class() : null;
    }
}
