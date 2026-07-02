<?php

declare(strict_types=1);

namespace Nova\Security;

/**
 * Defines a source for authenticated users.
 */
interface UserProvider
{
    public function retrieveById(mixed $id): ?array;

    public function retrieveByCredentials(array $credentials): ?array;

    public function validateCredentials(array $user, array $credentials): bool;

    public function updateRememberToken(mixed $id, string $token): void;

    public function retrieveByRememberToken(mixed $id, string $token): ?array;

    public function rehashPasswordIfNeeded(array $user, string $plainPassword): void;
}
