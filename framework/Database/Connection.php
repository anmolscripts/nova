<?php

declare(strict_types=1);

namespace Nova\Database;

final class Connection
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function pdo(): \PDO
    {
        return $this->pdo;
    }

    public function table(string $table): QueryBuilder
    {
        return new QueryBuilder($this, $table);
    }

    public function select(string $table): QueryBuilder
    {
        return $this->table($table);
    }

    public function query(string $sql, array $bindings = []): array
    {
        $statement = $this->statement($sql, $bindings);
        return $statement->fetchAll();
    }

    public function statement(string $sql, array $bindings = []): \PDOStatement
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($bindings);
        return $statement;
    }

    public function procedure(string $name, array $params = []): array
    {
        $placeholders = implode(', ', array_fill(0, count($params), '?'));
        return $this->query("CALL {$name}({$placeholders})", array_values($params));
    }

    public function transaction(callable $callback): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $callback($this);
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }
}
