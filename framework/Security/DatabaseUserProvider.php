<?php

declare(strict_types=1);

namespace Nova\Security;

use Nova\Application\Application;

/**
 * Loads authenticated users from a database table.
 */
final class DatabaseUserProvider implements UserProvider
{
    public function __construct(private readonly Application $app, private readonly array $config)
    {
    }

    public function retrieveById(mixed $id): ?array
    {
        return $this->table()->where($this->idField(), $id)->first();
    }

    public function retrieveByCredentials(array $credentials): ?array
    {
        $identifier = $this->identifierField();
        if (!array_key_exists($identifier, $credentials)) {
            return null;
        }

        return $this->table()->where($identifier, $credentials[$identifier])->first();
    }

    public function validateCredentials(array $user, array $credentials): bool
    {
        $password = $credentials['password'] ?? null;
        $hash = $user[$this->passwordField()] ?? null;

        return is_string($password) && is_string($hash) && password_verify($password, $hash);
    }

    public function updateRememberToken(mixed $id, string $token): void
    {
        $field = $this->rememberField();
        if ($field === null) {
            return;
        }

        $this->table()->where($this->idField(), $id)->update([$field => hash('sha256', $token)]);
    }

    public function retrieveByRememberToken(mixed $id, string $token): ?array
    {
        $field = $this->rememberField();
        if ($field === null) {
            return null;
        }

        return $this->table()
            ->where($this->idField(), $id)
            ->where($field, hash('sha256', $token))
            ->first();
    }

    public function rehashPasswordIfNeeded(array $user, string $plainPassword): void
    {
        $hash = $user[$this->passwordField()] ?? null;
        $id = $user[$this->idField()] ?? null;

        if (!is_string($hash) || $id === null || !password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            return;
        }

        $this->table()->where($this->idField(), $id)->update([
            $this->passwordField() => password_hash($plainPassword, PASSWORD_DEFAULT),
        ]);
    }

    private function table(): \Nova\Database\QueryBuilder
    {
        return db($this->config['connection'] ?? null)->table((string) ($this->config['table'] ?? 'users'));
    }

    private function identifierField(): string
    {
        return (string) ($this->config['identifier'] ?? 'email');
    }

    private function idField(): string
    {
        return (string) ($this->config['id'] ?? 'id');
    }

    private function passwordField(): string
    {
        return (string) ($this->config['password'] ?? 'password');
    }

    private function rememberField(): ?string
    {
        return $this->config['remember_token'] ?? null;
    }
}
