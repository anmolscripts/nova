<?php

declare(strict_types=1);

namespace Nova\Validation;

final class ErrorBag
{
    public function __construct(private readonly array $errors = [])
    {
    }

    public function all(): array
    {
        return $this->errors;
    }

    public function has(string $field): bool
    {
        return isset($this->errors[$field]) && $this->errors[$field] !== [];
    }

    public function first(string $field, ?string $default = null): ?string
    {
        return $this->errors[$field][0] ?? $default;
    }

    public function get(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    public function any(): bool
    {
        return $this->errors !== [];
    }
}
