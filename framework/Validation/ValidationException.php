<?php

declare(strict_types=1);

namespace Nova\Validation;

/**
 * Represents failed validation with field errors.
 */
final class ValidationException extends \Nova\Exceptions\NovaException
{
    public function __construct(public readonly array $errors)
    {
        parent::__construct('Validation failed.');
    }
}
