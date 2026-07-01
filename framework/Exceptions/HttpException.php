<?php

declare(strict_types=1);

namespace Nova\Exceptions;

final class HttpException extends \RuntimeException
{
    public function __construct(private readonly int $status, ?string $message = null)
    {
        parent::__construct($message ?: self::defaultMessage($status), $status);
    }

    public function status(): int
    {
        return $this->status;
    }

    private static function defaultMessage(int $status): string
    {
        return match ($status) {
            403 => 'Forbidden',
            404 => 'Not Found',
            419 => 'Page Expired',
            default => 'HTTP Error',
        };
    }
}
