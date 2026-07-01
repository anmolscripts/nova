<?php

declare(strict_types=1);

namespace Nova\Validation;

use Nova\Support\Arr;

final class Validator
{
    private array $errors = [];

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
            session()->put('_old_input', $this->data);
            foreach ($this->errors as $field => $messages) {
                flash('errors.' . $field, $messages);
            }
            throw new ValidationException($this->errors);
        }

        return $this->data;
    }

    private function check(string $field, string $rule): void
    {
        [$name, $argument] = array_pad(explode(':', $rule, 2), 2, null);
        $value = Arr::get($this->data, $field);

        $valid = match ($name) {
            'required' => $value !== null && $value !== '',
            'email' => $value === null || filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'min' => $value === null || mb_strlen((string) $value) >= (int) $argument,
            'max' => $value === null || mb_strlen((string) $value) <= (int) $argument,
            'same' => $value === Arr::get($this->data, (string) $argument),
            'confirmed' => $value === Arr::get($this->data, $field . '_confirmation'),
            default => true,
        };

        if (!$valid) {
            $this->errors[$field][] = $this->messages[$field . '.' . $name] ?? "The {$field} field failed {$name} validation.";
        }
    }
}
