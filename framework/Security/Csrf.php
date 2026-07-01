<?php

declare(strict_types=1);

namespace Nova\Security;

use Nova\Session\SessionManager;

final class Csrf
{
    public function __construct(private readonly SessionManager $session)
    {
    }

    public function token(): string
    {
        $token = $this->session->get('_token');
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            $this->session->put('_token', $token);
        }
        return $token;
    }

    public function verify(string $token): bool
    {
        return hash_equals((string) $this->session->get('_token', ''), $token);
    }
}
