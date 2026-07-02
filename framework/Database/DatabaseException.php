<?php

declare(strict_types=1);

namespace Nova\Database;

/**
 * Represents a database operation failure.
 */
final class DatabaseException extends \Nova\Exceptions\NovaException
{
    public static function fromThrowable(\Throwable $throwable, string $sql = '', array $bindings = []): self
    {
        $message = 'Database operation failed.';
        if ($sql !== '') {
            $message .= ' SQL: ' . $sql;
        }

        return new self($message, previous: $throwable);
    }
}
