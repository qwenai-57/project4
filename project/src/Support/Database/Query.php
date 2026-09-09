<?php

declare(strict_types=1);

/**
 * Query Builder Class - Fluent interface for database queries
 * Follows SOLID principles, optimized for PHP 8.2-8.5
 */

namespace App\Support\Database;

use PDOStatement;

class Query
{
    protected DB $db;
    protected string $table;
    protected array<string, mixed> $bindings = [];
    protected array<string> $selectColumns = ['*'];
    protected array<string, mixed> $wheres = [];
    protected array<string, string> $orders = [];
    protected ?int $limit = null;
    protected ?int $offset = null;
    protected array<string, mixed> $insertData = [];
    protected array<string, mixed> $updateData = [];
    protected array<string> $joinClauses = [];

    /**
     * Constructor
     *
     * @param DB $db
     * @param string $table
     */
    public function __construct(DB $db, string $table)
    {
        $this->db = $db;
        $this->table = $db->getTableName($table);
    }

    /**
     * Select columns
     *
     * @param string ...$columns
     * @return static
     */
    public function select(string ...$columns): static
    {
        $this->selectColumns = $columns ?: ['*'];
        return $this;
    }

    /**
     * Add a where clause
     *
     * @param string $column
     * @param mixed $operator
     * @param mixed $value
     * @return static
     */
    public function where(string $column, mixed $operator = null, mixed $value = null): static
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'AND'
        ];

        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Add a where IN clause
     *
     * @param string $column
     * @param array<int, mixed> $values
     * @return static
     */
    public function whereIn(string $column, array $values): static
    {
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->wheres[] = [
            'type' => 'raw',
            'condition' => "$column IN ($placeholders)",
            'boolean' => 'AND'
        ];

        $this->bindings = array_merge($this->bindings, $values);

        return $this;
    }

    /**
     * Add a OR WHERE clause
     *
     * @param string $column
     * @param mixed $operator
     * @param mixed $value
     * @return static
     */
    public function orWhere(string $column, mixed $operator = null, mixed $value = null): static
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'OR'
        ];

        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Add ORDER BY clause
     *
     * @param string $column
     * @param string $direction
     * @return static
     */
    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            $direction = 'ASC';
        }
        $this->orders[$column] = $direction;
        return $this;
    }

    /**
     * Set LIMIT
     *
     * @param int $limit
     * @return static
     */
    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set OFFSET
     *
     * @param int $offset
     * @return static
     */
    public function offset(int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Add JOIN clause
     *
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @param string $type
     * @return static
     */
    public function join(
        string $table,
        string $first,
        string $operator,
        string $second,
        string $type = 'INNER'
    ): static {
        $fullTable = $this->db->getTableName($table);
        $this->joinClauses[] = "$type JOIN $fullTable ON $first $operator $second";
        return $this;
    }

    /**
     * Add LEFT JOIN clause
     *
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @return static
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    /**
     * Get all results
     *
     * @return array<int, object>
     */
    public function get(): array
    {
        $sql = $this->buildSelectSql();
        $stmt = $this->execute($sql);
        return $stmt->fetchAll();
    }

    /**
     * Get first result
     *
     * @return object|null
     */
    public function first(): ?object
    {
        $this->limit = 1;
        $results = $this->get();
        return $results[0] ?? null;
    }

    /**
     * Find by ID
     *
     * @param int|string $id
     * @return object|null
     */
    public function find(int|string $id): ?object
    {
        return $this->where('id', $id)->first();
    }

    /**
     * Insert data
     *
     * @param array<string, mixed> $data
     * @return string|false Last insert ID or false on failure
     */
    public function insert(array $data): string|false
    {
        $columns = array_keys($data);
        $placeholders = implode(',', array_fill(0, count($data), '?'));
        $tableName = $this->table;

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $tableName,
            implode(',', $columns),
            $placeholders
        );

        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute(array_values($data));

        return $this->db->lastInsertId();
    }

    /**
     * Update data
     *
     * @param array<string, mixed> $data
     * @return bool
     */
    public function update(array $data): bool
    {
        if (empty($this->wheres)) {
            throw new \RuntimeException("Update without WHERE clause is not allowed for security reasons");
        }

        $setClauses = [];
        $values = array_values($data);

        foreach (array_keys($data) as $column) {
            $setClauses[] = "$column = ?";
        }

        $values = array_merge($values, $this->bindings);

        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $this->table,
            implode(', ', $setClauses),
            $this->buildWhereSql()
        );

        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Delete records
     *
     * @return bool
     */
    public function delete(): bool
    {
        if (empty($this->wheres)) {
            throw new \RuntimeException("Delete without WHERE clause is not allowed for security reasons");
        }

        $sql = sprintf(
            "DELETE FROM %s WHERE %s",
            $this->table,
            $this->buildWhereSql()
        );

        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute($this->bindings);
    }

    /**
     * Count results
     *
     * @return int
     */
    public function count(): int
    {
        $originalSelect = $this->selectColumns;
        $this->selectColumns = ['COUNT(*) as aggregate'];
        
        $result = $this->first();
        
        $this->selectColumns = $originalSelect;
        
        return (int) ($result?->aggregate ?? 0);
    }

    /**
     * Check if exists
     *
     * @return bool
     */
    public function exists(): bool
    {
        $originalSelect = $this->selectColumns;
        $this->selectColumns = ['1'];
        $this->limit = 1;
        
        $result = $this->first();
        
        $this->selectColumns = $originalSelect;
        
        return $result !== null;
    }

    /**
     * Build SELECT SQL
     *
     * @return string
     */
    protected function buildSelectSql(): string
    {
        $select = implode(', ', $this->selectColumns);
        $joins = !empty($this->joinClauses) ? ' ' . implode(' ', $this->joinClauses) : '';
        $where = !empty($this->wheres) ? ' WHERE ' . $this->buildWhereSql() : '';
        
        $orderBy = '';
        if (!empty($this->orders)) {
            $orderParts = [];
            foreach ($this->orders as $column => $direction) {
                $orderParts[] = "ORDER BY $column $direction";
            }
            $orderBy = ' ' . implode(', ', $orderParts);
        }

        $limitOffset = '';
        if ($this->limit !== null) {
            $limitOffset .= " LIMIT {$this->limit}";
        }
        if ($this->offset !== null) {
            $limitOffset .= " OFFSET {$this->offset}";
        }

        return "SELECT $select FROM {$this->table}{$joins}{$where}{$orderBy}{$limitOffset}";
    }

    /**
     * Build WHERE SQL
     *
     * @return string
     */
    protected function buildWhereSql(): string
    {
        $conditions = [];
        foreach ($this->wheres as $index => $where) {
            $boolean = $index > 0 ? " {$where['boolean']}" : '';
            
            if ($where['type'] === 'basic') {
                $conditions[] = "$boolean {$where['column']} {$where['operator']} ?";
            } elseif ($where['type'] === 'raw') {
                $conditions[] = "$boolean {$where['condition']}";
            }
        }

        return implode(' ', $conditions);
    }

    /**
     * Execute query
     *
     * @param string $sql
     * @return PDOStatement
     */
    protected function execute(string $sql): PDOStatement
    {
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($this->bindings);
        return $stmt;
    }

    /**
     * Reset the query builder state
     *
     * @return static
     */
    public function reset(): static
    {
        $this->bindings = [];
        $this->selectColumns = ['*'];
        $this->wheres = [];
        $this->orders = [];
        $this->limit = null;
        $this->offset = null;
        $this->insertData = [];
        $this->updateData = [];
        $this->joinClauses = [];
        return $this;
    }
}
