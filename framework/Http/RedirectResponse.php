<?php

declare(strict_types=1);

namespace Nova\Http;

/**
 * Represents an HTTP redirect response.
 */
final class RedirectResponse extends Response
{
    public function __construct(string $to, int $status = 302)
    {
        parent::__construct('', $status, ['Location' => $to]);
    }
}
