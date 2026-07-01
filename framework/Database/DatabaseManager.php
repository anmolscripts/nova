<?php

declare(strict_types=1);

namespace Nova\Database;

final class DatabaseManager
{
    private array $connections = [];

    public function __construct(private readonly array $config)
    {
    }

    public function connection(?string $name = null): Connection
    {
        $name ??= (string) ($this->config['default'] ?? 'mysql');
        if (!isset($this->connections[$name])) {
            $this->connections[$name] = new Connection($this->pdo($this->config['connections'][$name] ?? []));
        }
        return $this->connections[$name];
    }

    private function pdo(array $config): \PDO
    {
        $driver = $config['driver'] ?? 'mysql';
        if ($driver === 'sqlite') {
            $dsn = 'sqlite:' . $config['database'];
        } else {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $config['host'], $config['port'], $config['database'], $config['charset'] ?? 'utf8mb4');
        }

        return new \PDO($dsn, $config['username'] ?? null, $config['password'] ?? null, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);
    }
}
