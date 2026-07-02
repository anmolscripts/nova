<?php

declare(strict_types=1);

namespace Nova\Database;

/**
 * Resolves configured database connections.
 */
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
            $connectionConfig = $this->config['connections'][$name] ?? [];
            $this->connections[$name] = new Connection($this->pdo($connectionConfig), $connectionConfig, $this->config['logging'] ?? []);
        }
        return $this->connections[$name];
    }

    public function table(string $table): QueryBuilder
    {
        return $this->connection()->table($table);
    }

    public function select(string $sql, array $bindings = []): array
    {
        return $this->connection()->select($sql, $bindings);
    }

    public function first(string $sql, array $bindings = []): ?array
    {
        return $this->connection()->first($sql, $bindings);
    }

    public function insert(string $table, array $data): bool
    {
        return $this->connection()->insert($table, $data);
    }

    public function update(string $table, array $data, array $where = []): int
    {
        return $this->connection()->update($table, $data, $where);
    }

    public function delete(string $table, array $where = []): int
    {
        return $this->connection()->delete($table, $where);
    }

    public function statement(string $sql, array $bindings = []): \PDOStatement
    {
        return $this->connection()->statement($sql, $bindings);
    }

    public function scalar(string $sql, array $bindings = []): mixed
    {
        return $this->connection()->scalar($sql, $bindings);
    }

    public function transaction(callable $callback): mixed
    {
        return $this->connection()->transaction($callback);
    }

    public function begin(): void
    {
        $this->connection()->begin();
    }

    public function commit(): void
    {
        $this->connection()->commit();
    }

    public function rollback(): void
    {
        $this->connection()->rollback();
    }

    public function procedure(string $name, array $params = [], array $out = []): array
    {
        return $this->connection()->procedure($name, $params, $out);
    }

    private function pdo(array $config): \PDO
    {
        $driver = $config['driver'] ?? 'mysql';
        $dsn = match ($driver) {
            'sqlite' => 'sqlite:' . ($config['database'] ?? ':memory:'),
            'pgsql', 'postgres', 'postgresql' => sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $config['host'] ?? '127.0.0.1',
                $config['port'] ?? '5432',
                $config['database'] ?? ''
            ),
            'mysql', 'mariadb' => sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $config['host'] ?? '127.0.0.1',
                $config['port'] ?? '3306',
                $config['database'] ?? '',
                $config['charset'] ?? 'utf8mb4'
            ),
            default => throw new DatabaseException("Unsupported database driver [{$driver}]."),
        };

        try {
            return new \PDO($dsn, $config['username'] ?? null, $config['password'] ?? null, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (\PDOException $exception) {
            throw DatabaseException::fromThrowable($exception);
        }
    }
}
