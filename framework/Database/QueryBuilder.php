<?php

declare(strict_types=1);

namespace Nova\Database;

/**
 * Builds and executes simple database queries.
 */
final class QueryBuilder
{
    private array $columns = ['*'];
    private array $wheres = [];
    private array $bindings = [];
    private array $orders = [];
    private array $groups = [];
    private ?int $limit = null;
    private ?int $offset = null;

    public function __construct(private readonly Connection $connection, private readonly string $table)
    {
    }

    public function select(string|array $columns = ['*']): self
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();

        return $this;
    }

    public function where(string $column, mixed $operator, mixed $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = ['AND', $column . ' ' . $operator . ' ?'];
        $this->bindings[] = $value;

        return $this;
    }

    public function orWhere(string $column, mixed $operator, mixed $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = ['OR', $column . ' ' . $operator . ' ?'];
        $this->bindings[] = $value;

        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        if ($values === []) {
            $this->wheres[] = ['AND', '1 = 0'];
            return $this;
        }

        $this->wheres[] = ['AND', $column . ' IN (' . implode(', ', array_fill(0, count($values), '?')) . ')'];
        array_push($this->bindings, ...array_values($values));

        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orders[] = $column . ' ' . $direction;

        return $this;
    }

    public function groupBy(string|array $columns): self
    {
        array_push($this->groups, ...(is_array($columns) ? $columns : func_get_args()));

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = max(0, $limit);

        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = max(0, $offset);

        return $this;
    }

    public function get(): array
    {
        return $this->connection->select($this->selectSql(), $this->bindings);
    }

    public function first(): ?array
    {
        $this->limit(1);

        return $this->get()[0] ?? null;
    }

    public function count(string $column = '*'): int
    {
        $sql = 'SELECT COUNT(' . $column . ') FROM ' . $this->table . $this->whereSql();

        return (int) $this->connection->scalar($sql, $this->bindings);
    }

    public function exists(): bool
    {
        $sql = 'SELECT 1 FROM ' . $this->table . $this->whereSql() . ' LIMIT 1';

        return $this->connection->scalar($sql, $this->bindings) !== null;
    }

    public function insert(array $data): bool
    {
        if ($data === []) {
            throw new DatabaseException('Insert data cannot be empty.');
        }

        $columns = array_keys($data);
        $sql = 'INSERT INTO ' . $this->table
            . ' (' . implode(', ', $columns) . ') VALUES ('
            . implode(', ', array_fill(0, count($columns), '?')) . ')';

        return $this->connection->statement($sql, array_values($data))->rowCount() > 0;
    }

    public function update(array $data): int
    {
        if ($data === []) {
            throw new DatabaseException('Update data cannot be empty.');
        }

        $sets = implode(', ', array_map(fn (string $column): string => $column . ' = ?', array_keys($data)));
        $sql = 'UPDATE ' . $this->table . ' SET ' . $sets . $this->whereSql();

        return $this->connection->statement($sql, array_merge(array_values($data), $this->bindings))->rowCount();
    }

    public function delete(): int
    {
        return $this->connection->statement('DELETE FROM ' . $this->table . $this->whereSql(), $this->bindings)->rowCount();
    }

    private function selectSql(): string
    {
        $sql = 'SELECT ' . implode(', ', $this->columns) . ' FROM ' . $this->table . $this->whereSql();

        if ($this->groups !== []) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groups);
        }

        if ($this->orders !== []) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orders);
        }

        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
        }

        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . $this->offset;
        }

        return $sql;
    }

    private function whereSql(): string
    {
        if ($this->wheres === []) {
            return '';
        }

        $parts = [];
        foreach ($this->wheres as $index => [$boolean, $clause]) {
            $parts[] = ($index === 0 ? '' : $boolean . ' ') . $clause;
        }

        return ' WHERE ' . implode(' ', $parts);
    }
}
