<?php

declare(strict_types=1);

namespace Nova\Database;

final class Connection
{
    public function __construct(
        private readonly \PDO $pdo,
        private readonly array $config = [],
        private readonly array $logging = []
    ) {
    }

    public function pdo(): \PDO
    {
        return $this->pdo;
    }

    public function table(string $table): QueryBuilder
    {
        return new QueryBuilder($this, $table);
    }

    public function select(string $sql, array $bindings = []): array
    {
        return $this->query($sql, $bindings);
    }

    public function first(string $sql, array $bindings = []): ?array
    {
        return $this->query($sql, $bindings)[0] ?? null;
    }

    public function insert(string $table, array $data): bool
    {
        return $this->table($table)->insert($data);
    }

    public function update(string $table, array $data, array $where = []): int
    {
        $builder = $this->table($table);
        foreach ($where as $column => $value) {
            $builder->where((string) $column, $value);
        }

        return $builder->update($data);
    }

    public function delete(string $table, array $where = []): int
    {
        $builder = $this->table($table);
        foreach ($where as $column => $value) {
            $builder->where((string) $column, $value);
        }

        return $builder->delete();
    }

    public function query(string $sql, array $bindings = []): array
    {
        return $this->statement($sql, $bindings)->fetchAll();
    }

    public function scalar(string $sql, array $bindings = []): mixed
    {
        $statement = $this->statement($sql, $bindings);
        $value = $statement->fetchColumn();

        return $value === false ? null : $value;
    }

    public function statement(string $sql, array $bindings = []): \PDOStatement
    {
        $started = microtime(true);

        try {
            $statement = $this->pdo->prepare($sql);
            $this->bind($statement, $bindings);
            $statement->execute();
            $this->log($sql, $bindings, $started);

            return $statement;
        } catch (\PDOException $exception) {
            throw DatabaseException::fromThrowable($exception, $sql, $bindings);
        }
    }

    public function procedure(string $name, array $params = [], array $out = []): array
    {
        $driver = (string) ($this->config['driver'] ?? $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME));
        $bindings = array_values($params);
        $outNames = array_keys($out);
        $placeholders = array_fill(0, count($params), '?');

        foreach ($outNames as $name) {
            $placeholders[] = $this->outPlaceholder((string) $name, $driver);
        }

        $sql = $this->procedureSql($name, $placeholders, $driver);
        $statement = $this->statement($sql, $bindings);
        $sets = $this->resultSets($statement);

        return [
            'sets' => $sets,
            'first' => $sets[0] ?? [],
            'out' => $outNames === [] ? [] : $this->outValues($outNames, $driver),
        ];
    }

    public function transaction(callable $callback): mixed
    {
        $this->begin();

        try {
            $result = $callback($this);
            $this->commit();

            return $result;
        } catch (\Throwable $exception) {
            $this->rollback();
            throw $exception instanceof DatabaseException ? $exception : DatabaseException::fromThrowable($exception);
        }
    }

    public function begin(): void
    {
        try {
            $this->pdo->beginTransaction();
        } catch (\PDOException $exception) {
            throw DatabaseException::fromThrowable($exception);
        }
    }

    public function commit(): void
    {
        try {
            $this->pdo->commit();
        } catch (\PDOException $exception) {
            throw DatabaseException::fromThrowable($exception);
        }
    }

    public function rollback(): void
    {
        try {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
        } catch (\PDOException $exception) {
            throw DatabaseException::fromThrowable($exception);
        }
    }

    private function bind(\PDOStatement $statement, array $bindings): void
    {
        foreach ($bindings as $key => $value) {
            if (is_array($value) || (is_object($value) && !$value instanceof \Stringable)) {
                throw new DatabaseException('Database bindings must be scalar, null, or stringable values.');
            }

            $parameter = is_int($key) ? $key + 1 : ':' . ltrim((string) $key, ':');
            $statement->bindValue($parameter, $value, $this->type($value));
        }
    }

    private function type(mixed $value): int
    {
        return match (true) {
            is_int($value) => \PDO::PARAM_INT,
            is_bool($value) => \PDO::PARAM_BOOL,
            $value === null => \PDO::PARAM_NULL,
            default => \PDO::PARAM_STR,
        };
    }

    private function resultSets(\PDOStatement $statement): array
    {
        $sets = [];
        while (true) {
            $sets[] = $statement->fetchAll();

            try {
                if (!$statement->nextRowset()) {
                    break;
                }
            } catch (\PDOException) {
                break;
            }
        }

        return $sets;
    }

    private function procedureSql(string $name, array $placeholders, string $driver): string
    {
        $arguments = implode(', ', $placeholders);

        return match ($driver) {
            'sqlite' => 'SELECT ' . $name . '(' . $arguments . ') AS result',
            'pgsql', 'postgres', 'postgresql' => 'SELECT * FROM ' . $name . '(' . $arguments . ')',
            default => 'CALL ' . $name . '(' . $arguments . ')',
        };
    }

    private function outPlaceholder(string $name, string $driver): string
    {
        return match ($driver) {
            'pgsql', 'postgres', 'postgresql' => ':' . $name,
            default => '@' . $name,
        };
    }

    private function outValues(array $names, string $driver): array
    {
        if (in_array($driver, ['pgsql', 'postgres', 'postgresql'], true)) {
            return [];
        }

        $columns = implode(', ', array_map(fn (string $name): string => '@' . $name . ' AS ' . $name, $names));

        return $this->first('SELECT ' . $columns) ?? [];
    }

    private function log(string $sql, array $bindings, float $started): void
    {
        if (!($this->logging['enabled'] ?? false)) {
            return;
        }

        $path = $this->logging['path'] ?? dirname(__DIR__, 2) . '/storage/logs/database.log';
        $directory = dirname((string) $path);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents((string) $path, json_encode([
            'sql' => $sql,
            'bindings' => $bindings,
            'time_ms' => round((microtime(true) - $started) * 1000, 3),
        ], JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
    }
}
