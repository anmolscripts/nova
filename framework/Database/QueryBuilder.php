<?php

declare(strict_types=1);

namespace Nova\Database;

final class QueryBuilder
{
    private array $wheres = [];
    private array $bindings = [];
    private ?int $limit = null;

    public function __construct(private readonly Connection $connection, private readonly string $table)
    {
    }

    public function where(string $column, mixed $operator, mixed $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        $this->wheres[] = "{$column} {$operator} ?";
        $this->bindings[] = $value;
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function get(array $columns = ['*']): array
    {
        $sql = 'SELECT ' . implode(', ', $columns) . ' FROM ' . $this->table . $this->whereSql();
        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
        }
        return $this->connection->query($sql, $this->bindings);
    }

    public function first(array $columns = ['*']): ?array
    {
        return $this->limit(1)->get($columns)[0] ?? null;
    }

    public function insert(array $data): bool
    {
        $columns = array_keys($data);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $sql = 'INSERT INTO ' . $this->table . ' (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')';
        return $this->connection->statement($sql, array_values($data))->rowCount() > 0;
    }

    public function update(array $data): int
    {
        $sets = implode(', ', array_map(fn ($column) => "{$column} = ?", array_keys($data)));
        $sql = 'UPDATE ' . $this->table . ' SET ' . $sets . $this->whereSql();
        return $this->connection->statement($sql, array_merge(array_values($data), $this->bindings))->rowCount();
    }

    public function delete(): int
    {
        return $this->connection->statement('DELETE FROM ' . $this->table . $this->whereSql(), $this->bindings)->rowCount();
    }

    private function whereSql(): string
    {
        return $this->wheres ? ' WHERE ' . implode(' AND ', $this->wheres) : '';
    }
}
