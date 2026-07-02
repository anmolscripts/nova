<?php

declare(strict_types=1);

namespace Nova\Validation;

use Nova\Storage\Security\FileGuard;
use Nova\Support\Arr;

/**
 * Validates data against Nova validation rules.
 */
final class Validator
{
    private array $errors = [];
    private const SENSITIVE_FIELDS = ['password', 'password_confirmation'];

    private function __construct(private readonly array $data, private readonly array $rules, private readonly array $messages = [])
    {
    }

    public static function make(array $data, array $rules, array $messages = []): self
    {
        return new self($data, $rules, $messages);
    }

    public function validate(): array
    {
        foreach ($this->rules as $field => $rules) {
            foreach (is_array($rules) ? $rules : explode('|', (string) $rules) as $rule) {
                $this->check($field, (string) $rule);
            }
        }

        if ($this->errors) {
            flash('_old_input', $this->oldInput());
            flash('errors', $this->errors);
            throw new ValidationException($this->errors);
        }

        return $this->data;
    }

    private function check(string $field, string $rule): void
    {
        [$name, $argument] = array_pad(explode(':', $rule, 2), 2, null);
        $value = Arr::get($this->data, $field);

        if (($name === 'nullable' && ($value === null || $value === '')) || ($value === null && $name !== 'required')) {
            return;
        }

        $valid = match ($name) {
            'required' => $value !== null && $value !== '',
            'nullable' => true,
            'string' => is_string($value),
            'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false,
            'numeric' => is_numeric($value),
            'boolean' => is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false'], true),
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'url' => filter_var($value, FILTER_VALIDATE_URL) !== false,
            'date' => strtotime((string) $value) !== false,
            'min' => $this->size($value) >= (float) $argument,
            'max' => $this->size($value) <= (float) $argument,
            'between' => $this->between($value, (string) $argument),
            'same' => $value === Arr::get($this->data, (string) $argument),
            'different' => $value !== Arr::get($this->data, (string) $argument),
            'confirmed' => $value === Arr::get($this->data, $field . '_confirmation'),
            'in' => in_array((string) $value, explode(',', (string) $argument), true),
            'not_in' => !in_array((string) $value, explode(',', (string) $argument), true),
            'regex' => @preg_match((string) $argument, (string) $value) === 1,
            'array' => is_array($value),
            'file' => $this->isFile($value),
            'image' => $this->isImage($value),
            'mimes' => $this->hasMime($value, (string) $argument),
            'extensions' => $this->hasExtension($value, (string) $argument),
            default => true,
        };

        if (!$valid) {
            $this->errors[$field][] = $this->messages[$field . '.' . $name] ?? "The {$field} field failed {$name} validation.";
        }
    }

    private function size(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_array($value)) {
            return count($value);
        }

        if ($this->isFile($value)) {
            return ((float) ($value->size ?? 0)) / 1024;
        }

        return (float) mb_strlen((string) $value);
    }

    private function between(mixed $value, string $argument): bool
    {
        [$min, $max] = array_map('floatval', array_pad(explode(',', $argument, 2), 2, 0));
        $size = $this->size($value);

        return $size >= $min && $size <= $max;
    }

    private function oldInput(): array
    {
        return array_diff_key($this->data, array_flip(self::SENSITIVE_FIELDS));
    }

    private function isFile(mixed $value): bool
    {
        return is_object($value)
            && method_exists($value, 'isValid')
            && $value->isValid()
            && FileGuard::extensionAllowed((string) ($value->name ?? ''));
    }

    private function isImage(mixed $value): bool
    {
        if (!$this->isFile($value)) {
            return false;
        }

        $mime = method_exists($value, 'mime') ? (string) $value->mime() : (string) ($value->type ?? '');

        return str_starts_with($mime, 'image/');
    }

    private function hasMime(mixed $value, string $argument): bool
    {
        if (!$this->isFile($value)) {
            return false;
        }

        $mime = strtolower(method_exists($value, 'mime') ? (string) $value->mime() : (string) ($value->type ?? ''));
        $allowed = array_map('strtolower', explode(',', $argument));
        foreach ($allowed as $allowedMime) {
            if (str_contains($allowedMime, '/') && $mime === $allowedMime) {
                return true;
            }
        }

        return $this->hasExtension($value, $argument);
    }

    private function hasExtension(mixed $value, string $argument): bool
    {
        if (!$this->isFile($value)) {
            return false;
        }

        $extension = strtolower(pathinfo((string) ($value->name ?? ''), PATHINFO_EXTENSION));

        return in_array($extension, array_map('strtolower', explode(',', $argument)), true);
    }
}
