<?php

declare(strict_types=1);

namespace Elarion\Database\Query;

use Elarion\Database\Query\Grammar\Grammar;
use PDO;

/**
 * Query Builder
 *
 * Fluent interface for building and executing SQL queries.
 * Following Builder pattern with method chaining.
 */
class Builder
{
    /**
     * Query components
     *
     * @var array<string, mixed>
     */
    protected array $query = [
        'distinct' => false,
        'columns' => ['*'],
        'from' => null,
        'joins' => [],
        'wheres' => [],
        'groups' => [],
        'havings' => [],
        'orders' => [],
        'limit' => null,
        'offset' => null,
        'aggregate' => null,
    ];

    /**
     * Query bindings
     *
     * @var array<mixed>
     */
    protected array $bindings = [];

    /**
     * Create query builder
     *
     * @param PDO $connection PDO connection
     * @param Grammar $grammar SQL grammar
     */
    public function __construct(
        protected PDO $connection,
        protected Grammar $grammar
    ) {
    }

    /**
     * Set table
     *
     * @param string $table Table name
     * @return self Fluent interface
     */
    public function table(string $table): self
    {
        $this->query['from'] = $table;

        return $this;
    }

    /**
     * Alias for table()
     *
     * @param string $table Table name
     * @return self Fluent interface
     */
    public function from(string $table): self
    {
        return $this->table($table);
    }

    /**
     * Set columns to select
     *
     * @param string|array<string> ...$columns Columns
     * @return self Fluent interface
     */
    public function select(string|array ...$columns): self
    {
        $this->query['columns'] = [];

        foreach ($columns as $column) {
            if (is_array($column)) {
                /** @var array<string> $currentColumns */
                $currentColumns = $this->query['columns'];
                $this->query['columns'] = array_merge($currentColumns, $column);
            } else {
                $this->query['columns'][] = $column;
            }
        }

        return $this;
    }

    /**
     * Add columns to select
     *
     * @param string|array<string> ...$columns Columns
     * @return self Fluent interface
     */
    public function addSelect(string|array ...$columns): self
    {
        foreach ($columns as $column) {
            if (is_array($column)) {
                /** @var array<string> $currentColumns */
                $currentColumns = $this->query['columns'];
                $this->query['columns'] = array_merge($currentColumns, $column);
            } else {
                $this->query['columns'][] = $column;
            }
        }

        return $this;
    }

    /**
     * Set distinct flag
     *
     * @return self Fluent interface
     */
    public function distinct(): self
    {
        $this->query['distinct'] = true;

        return $this;
    }

    /**
     * Add WHERE clause
     *
     * @param string $column Column name
     * @param mixed $operator Operator or value
     * @param mixed $value Value (if operator provided)
     * @param string $boolean Boolean operator (and/or)
     * @return self Fluent interface
     */
    public function where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and'): self
    {
        // where('column', 'value') shorthand
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->query['wheres'][] = [
            'type' => 'Basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean,
        ];

        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Add OR WHERE clause
     *
     * @param string $column Column name
     * @param mixed $operator Operator or value
     * @param mixed $value Value (if operator provided)
     * @return self Fluent interface
     */
    public function orWhere(string $column, mixed $operator = null, mixed $value = null): self
    {
        return $this->where($column, $operator, $value, 'or');
    }

    /**
     * Add WHERE IN clause
     *
     * @param string $column Column name
     * @param array<mixed> $values Values
     * @param string $boolean Boolean operator
     * @param bool $not NOT IN flag
     * @return self Fluent interface
     */
    public function whereIn(string $column, array $values, string $boolean = 'and', bool $not = false): self
    {
        $type = $not ? 'NotIn' : 'In';

        $this->query['wheres'][] = [
            'type' => $type,
            'column' => $column,
            'values' => $values,
            'boolean' => $boolean,
        ];

        $this->bindings = array_merge($this->bindings, $values);

        return $this;
    }

    /**
     * Add WHERE NOT IN clause
     *
     * @param string $column Column name
     * @param array<mixed> $values Values
     * @return self Fluent interface
     */
    public function whereNotIn(string $column, array $values): self
    {
        return $this->whereIn($column, $values, 'and', true);
    }

    /**
     * Add WHERE NULL clause
     *
     * @param string $column Column name
     * @param string $boolean Boolean operator
     * @param bool $not NOT NULL flag
     * @return self Fluent interface
     */
    public function whereNull(string $column, string $boolean = 'and', bool $not = false): self
    {
        $type = $not ? 'NotNull' : 'Null';

        $this->query['wheres'][] = [
            'type' => $type,
            'column' => $column,
            'boolean' => $boolean,
        ];

        return $this;
    }

    /**
     * Add WHERE NOT NULL clause
     *
     * @param string $column Column name
     * @return self Fluent interface
     */
    public function whereNotNull(string $column): self
    {
        return $this->whereNull($column, 'and', true);
    }

    /**
     * Add WHERE BETWEEN clause
     *
     * @param string $column Column name
     * @param mixed $min Minimum value
     * @param mixed $max Maximum value
     * @param string $boolean Boolean operator
     * @return self Fluent interface
     */
    public function whereBetween(string $column, mixed $min, mixed $max, string $boolean = 'and'): self
    {
        $this->query['wheres'][] = [
            'type' => 'Between',
            'column' => $column,
            'min' => $min,
            'max' => $max,
            'boolean' => $boolean,
        ];

        $this->bindings[] = $min;
        $this->bindings[] = $max;

        return $this;
    }

    /**
     * Add JOIN clause
     *
     * @param string $table Table to join
     * @param string $first First column
     * @param string $operator Operator
     * @param string $second Second column
     * @param string $type Join type
     * @return self Fluent interface
     */
    public function join(string $table, string $first, string $operator, string $second, string $type = 'inner'): self
    {
        $this->query['joins'][] = [
            'type' => $type,
            'table' => $table,
            'conditions' => [
                [
                    'first' => $first,
                    'operator' => $operator,
                    'second' => $second,
                    'boolean' => 'and',
                ],
            ],
        ];

        return $this;
    }

    /**
     * Add LEFT JOIN clause
     *
     * @param string $table Table to join
     * @param string $first First column
     * @param string $operator Operator
     * @param string $second Second column
     * @return self Fluent interface
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'left');
    }

    /**
     * Add RIGHT JOIN clause
     *
     * @param string $table Table to join
     * @param string $first First column
     * @param string $operator Operator
     * @param string $second Second column
     * @return self Fluent interface
     */
    public function rightJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'right');
    }

    /**
     * Add ORDER BY clause
     *
     * @param string $column Column name
     * @param string $direction Sort direction
     * @return self Fluent interface
     */
    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->query['orders'][] = [
            'column' => $column,
            'direction' => strtolower($direction),
        ];

        return $this;
    }

    /**
     * Add GROUP BY clause
     *
     * @param string|array<string> ...$groups Columns to group by
     * @return self Fluent interface
     */
    public function groupBy(string|array ...$groups): self
    {
        foreach ($groups as $group) {
            if (is_array($group)) {
                /** @var array<string> $currentGroups */
                $currentGroups = $this->query['groups'];
                $this->query['groups'] = array_merge($currentGroups, $group);
            } else {
                $this->query['groups'][] = $group;
            }
        }

        return $this;
    }

    /**
     * Add HAVING clause
     *
     * @param string $column Column name
     * @param string $operator Operator
     * @param mixed $value Value
     * @param string $boolean Boolean operator
     * @return self Fluent interface
     */
    public function having(string $column, string $operator, mixed $value, string $boolean = 'and'): self
    {
        $this->query['havings'][] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean,
        ];

        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Set LIMIT
     *
     * @param int $limit Limit value
     * @return self Fluent interface
     */
    public function limit(int $limit): self
    {
        $this->query['limit'] = $limit;

        return $this;
    }

    /**
     * Alias for limit()
     *
     * @param int $limit Limit value
     * @return self Fluent interface
     */
    public function take(int $limit): self
    {
        return $this->limit($limit);
    }

    /**
     * Set OFFSET
     *
     * @param int $offset Offset value
     * @return self Fluent interface
     */
    public function offset(int $offset): self
    {
        $this->query['offset'] = $offset;

        return $this;
    }

    /**
     * Alias for offset()
     *
     * @param int $offset Offset value
     * @return self Fluent interface
     */
    public function skip(int $offset): self
    {
        return $this->offset($offset);
    }

    /**
     * Get all results
     *
     * @return array<array<string, mixed>> Results
     */
    public function get(): array
    {
        $sql = $this->grammar->compileSelect($this->query);

        return $this->execute($sql, $this->bindings);
    }

    /**
     * Get first result
     *
     * @return array<string, mixed>|null First result or null
     */
    public function first(): ?array
    {
        $results = $this->limit(1)->get();

        return $results[0] ?? null;
    }

    /**
     * Find by ID
     *
     * @param int|string $id ID value
     * @param string $column ID column name
     * @return array<string, mixed>|null Result or null
     */
    public function find(int|string $id, string $column = 'id'): ?array
    {
        return $this->where($column, '=', $id)->first();
    }

    /**
     * Get single column values
     *
     * @param string $column Column name
     * @return array<mixed> Column values
     */
    public function pluck(string $column): array
    {
        $results = $this->select($column)->get();

        return array_column($results, $column);
    }

    /**
     * Count results
     *
     * @return int Count
     */
    public function count(): int
    {
        $result = $this->aggregate('count', ['*']);

        return $result === null ? 0 : (int) $result;
    }

    /**
     * Get MAX value
     *
     * @param string $column Column name
     * @return mixed Max value
     */
    public function max(string $column): mixed
    {
        return $this->aggregate('max', [$column]);
    }

    /**
     * Get MIN value
     *
     * @param string $column Column name
     * @return mixed Min value
     */
    public function min(string $column): mixed
    {
        return $this->aggregate('min', [$column]);
    }

    /**
     * Get AVG value
     *
     * @param string $column Column name
     * @return mixed Average value
     */
    public function avg(string $column): mixed
    {
        return $this->aggregate('avg', [$column]);
    }

    /**
     * Get SUM value
     *
     * @param string $column Column name
     * @return mixed Sum value
     */
    public function sum(string $column): mixed
    {
        return $this->aggregate('sum', [$column]);
    }

    /**
     * Execute aggregate function
     *
     * @param string $function Function name
     * @param array<string> $columns Columns
     * @return mixed Aggregate result
     */
    protected function aggregate(string $function, array $columns): mixed
    {
        $this->query['aggregate'] = [
            'function' => $function,
            'columns' => $columns,
        ];

        $sql = $this->grammar->compileSelect($this->query);
        $results = $this->execute($sql, $this->bindings);

        return $results[0]['aggregate'] ?? null;
    }

    /**
     * Insert record
     *
     * @param array<string, mixed> $values Values to insert
     * @return bool Success
     */
    public function insert(array $values): bool
    {
        $sql = $this->grammar->compileInsert($this->query, $values);

        $this->execute($sql, array_values($values));

        return true;
    }

    /**
     * Insert and get ID
     *
     * @param array<string, mixed> $values Values to insert
     * @return int|string Last insert ID
     */
    public function insertGetId(array $values): int|string
    {
        $this->insert($values);

        $id = $this->connection->lastInsertId();

        return $id === false ? '0' : $id;
    }

    /**
     * Update records
     *
     * @param array<string, mixed> $values Values to update
     * @return int Affected rows
     */
    public function update(array $values): int
    {
        $sql = $this->grammar->compileUpdate($this->query, $values);

        $bindings = array_merge(array_values($values), $this->bindings);

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($bindings);

        return $stmt->rowCount();
    }

    /**
     * Increment column value
     *
     * @param string $column Column name
     * @param int $amount Amount to increment
     * @return int Affected rows
     */
    public function increment(string $column, int $amount = 1): int
    {
        // Build raw SQL for increment
        $wrapped = $this->grammar instanceof \Elarion\Database\Query\Grammar\MySqlGrammar
            ? "`{$column}`"
            : "\"{$column}\"";

        return $this->update([$column => new class ($wrapped, $amount) {
            public function __construct(public string $column, public int $amount)
            {
            }

            public function __toString(): string
            {
                return "{$this->column} + {$this->amount}";
            }
        }]);
    }

    /**
     * Decrement column value
     *
     * @param string $column Column name
     * @param int $amount Amount to decrement
     * @return int Affected rows
     */
    public function decrement(string $column, int $amount = 1): int
    {
        return $this->increment($column, -$amount);
    }

    /**
     * Delete records
     *
     * @return int Affected rows
     */
    public function delete(): int
    {
        $sql = $this->grammar->compileDelete($this->query);

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($this->bindings);

        return $stmt->rowCount();
    }

    /**
     * Execute SQL query
     *
     * @param string $sql SQL query
     * @param array<mixed> $bindings Bindings
     * @return array<array<string, mixed>> Results
     */
    protected function execute(string $sql, array $bindings = []): array
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($bindings);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get SQL query string (for debugging)
     *
     * @return string SQL query
     */
    public function toSql(): string
    {
        return $this->grammar->compileSelect($this->query);
    }

    /**
     * Get bindings (for debugging)
     *
     * @return array<mixed> Bindings
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }
}
