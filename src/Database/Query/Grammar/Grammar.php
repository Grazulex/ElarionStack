<?php

declare(strict_types=1);

namespace Elarion\Database\Query\Grammar;

/**
 * Base Grammar for SQL Generation
 *
 * Abstract base for driver-specific SQL generation.
 * Following Strategy pattern for different SQL dialects.
 */
abstract class Grammar
{
    /**
     * Grammar components for SELECT
     *
     * @var array<string>
     */
    protected array $selectComponents = [
        'aggregate',
        'columns',
        'from',
        'joins',
        'wheres',
        'groups',
        'havings',
        'orders',
        'limit',
        'offset',
    ];

    /**
     * Compile SELECT query
     *
     * @param array<string, mixed> $query Query components
     * @return string SQL query
     */
    public function compileSelect(array $query): string
    {
        $sql = [];

        foreach ($this->selectComponents as $component) {
            $value = $query[$component] ?? null;

            if ($value === null) {
                continue;
            }

            // Skip empty arrays (joins, wheres, groups, havings, orders)
            if (is_array($value) && empty($value)) {
                continue;
            }

            $method = 'compile' . ucfirst($component);
            $sql[] = $this->$method($query, $value);
        }

        return $this->concatenate($sql);
    }

    /**
     * Compile INSERT query
     *
     * @param array<string, mixed> $query Query components
     * @param array<string, mixed> $values Values to insert
     * @return string SQL query
     */
    public function compileInsert(array $query, array $values): string
    {
        $table = $this->wrapTable((string) ($query['from'] ?? ''));
        $columns = $this->columnize(array_keys($values));
        $parameters = $this->parameterize($values);

        return "insert into {$table} ({$columns}) values ({$parameters})";
    }

    /**
     * Compile UPDATE query
     *
     * @param array<string, mixed> $query Query components
     * @param array<string, mixed> $values Values to update
     * @return string SQL query
     */
    public function compileUpdate(array $query, array $values): string
    {
        $table = $this->wrapTable((string) ($query['from'] ?? ''));

        $columns = [];
        foreach ($values as $key => $value) {
            $columns[] = $this->wrap($key) . ' = ?';
        }

        $columns = implode(', ', $columns);

        $wheres = $query['wheres'] ?? [];
        $where = is_array($wheres) ? $this->compileWheres($query, $wheres) : '';

        return trim("update {$table} set {$columns} {$where}");
    }

    /**
     * Compile DELETE query
     *
     * @param array<string, mixed> $query Query components
     * @return string SQL query
     */
    public function compileDelete(array $query): string
    {
        $table = $this->wrapTable((string) ($query['from'] ?? ''));
        $wheres = $query['wheres'] ?? [];
        $where = is_array($wheres) ? $this->compileWheres($query, $wheres) : '';

        return trim("delete from {$table} {$where}");
    }

    /**
     * Compile aggregate function
     *
     * @param array<string, mixed> $query Query components
     * @param array<string, mixed> $aggregate Aggregate data
     * @return string SQL fragment
     */
    protected function compileAggregate(array $query, array $aggregate): string
    {
        /** @var array<string> $columns */
        $columns = $aggregate['columns'] ?? ['*'];
        $column = $this->columnize($columns);

        if ($query['distinct'] ?? false) {
            $column = 'distinct ' . $column;
        }

        $function = (string) ($aggregate['function'] ?? 'count');

        return 'select ' . $function . '(' . $column . ') as aggregate';
    }

    /**
     * Compile columns
     *
     * @param array<string, mixed> $query Query components
     * @param array<string> $columns Columns to select
     * @return string SQL fragment
     */
    protected function compileColumns(array $query, array $columns): string
    {
        if (! empty($query['aggregate'])) {
            return '';
        }

        $select = $query['distinct'] ?? false ? 'select distinct ' : 'select ';

        return $select . $this->columnize($columns);
    }

    /**
     * Compile FROM clause
     *
     * @param array<string, mixed> $query Query components
     * @param string $table Table name
     * @return string SQL fragment
     */
    protected function compileFrom(array $query, string $table): string
    {
        return 'from ' . $this->wrapTable($table);
    }

    /**
     * Compile JOIN clauses
     *
     * @param array<string, mixed> $query Query components
     * @param array<array<string, mixed>> $joins Join data
     * @return string SQL fragment
     */
    protected function compileJoins(array $query, array $joins): string
    {
        $sql = [];

        foreach ($joins as $join) {
            $table = (string) ($join['table'] ?? '');
            $type = (string) ($join['type'] ?? 'inner');
            $conditions = $join['conditions'] ?? [];

            $clauses = [];
            if (is_array($conditions)) {
                foreach ($conditions as $condition) {
                    $boolean = (string) ($condition['boolean'] ?? 'and');
                    $first = (string) ($condition['first'] ?? '');
                    $operator = (string) ($condition['operator'] ?? '=');
                    $second = (string) ($condition['second'] ?? '');

                    $clauses[] = sprintf(
                        '%s %s %s %s',
                        $boolean,
                        $this->wrap($first),
                        $operator,
                        $this->wrap($second)
                    );
                }
            }

            $on = implode(' ', $clauses);
            $on = preg_replace('/and |or /', '', $on, 1) ?? ''; // Remove first boolean

            $sql[] = "{$type} join {$this->wrapTable($table)} on {$on}";
        }

        return implode(' ', $sql);
    }

    /**
     * Compile WHERE clauses
     *
     * @param array<string, mixed> $query Query components
     * @param array<array<string, mixed>> $wheres Where conditions
     * @return string SQL fragment
     */
    protected function compileWheres(array $query, array $wheres): string
    {
        if (empty($wheres)) {
            return '';
        }

        $sql = [];

        foreach ($wheres as $where) {
            $type = (string) ($where['type'] ?? 'Basic');
            $boolean = (string) ($where['boolean'] ?? 'and');
            $method = 'compileWhere' . $type;
            $sql[] = $boolean . ' ' . $this->$method($where);
        }

        $sql = implode(' ', $sql);

        // Remove first boolean (AND/OR)
        $result = preg_replace('/and |or /', '', $sql, 1);

        return 'where ' . ($result ?? '');
    }

    /**
     * Compile basic WHERE
     *
     * @param array<string, mixed> $where Where data
     * @return string SQL fragment
     */
    protected function compileWhereBasic(array $where): string
    {
        $column = (string) ($where['column'] ?? '');
        $operator = (string) ($where['operator'] ?? '=');

        return $this->wrap($column) . ' ' . $operator . ' ?';
    }

    /**
     * Compile WHERE IN
     *
     * @param array<string, mixed> $where Where data
     * @return string SQL fragment
     */
    protected function compileWhereIn(array $where): string
    {
        $column = (string) ($where['column'] ?? '');
        /** @var array<mixed> $values */
        $values = $where['values'] ?? [];
        $placeholders = $this->parameterize($values);

        return $this->wrap($column) . ' in (' . $placeholders . ')';
    }

    /**
     * Compile WHERE NOT IN
     *
     * @param array<string, mixed> $where Where data
     * @return string SQL fragment
     */
    protected function compileWhereNotIn(array $where): string
    {
        $column = (string) ($where['column'] ?? '');
        /** @var array<mixed> $values */
        $values = $where['values'] ?? [];
        $placeholders = $this->parameterize($values);

        return $this->wrap($column) . ' not in (' . $placeholders . ')';
    }

    /**
     * Compile WHERE NULL
     *
     * @param array<string, mixed> $where Where data
     * @return string SQL fragment
     */
    protected function compileWhereNull(array $where): string
    {
        $column = (string) ($where['column'] ?? '');

        return $this->wrap($column) . ' is null';
    }

    /**
     * Compile WHERE NOT NULL
     *
     * @param array<string, mixed> $where Where data
     * @return string SQL fragment
     */
    protected function compileWhereNotNull(array $where): string
    {
        $column = (string) ($where['column'] ?? '');

        return $this->wrap($column) . ' is not null';
    }

    /**
     * Compile WHERE BETWEEN
     *
     * @param array<string, mixed> $where Where data
     * @return string SQL fragment
     */
    protected function compileWhereBetween(array $where): string
    {
        $column = (string) ($where['column'] ?? '');

        return $this->wrap($column) . ' between ? and ?';
    }

    /**
     * Compile GROUP BY
     *
     * @param array<string, mixed> $query Query components
     * @param array<string> $groups Columns to group by
     * @return string SQL fragment
     */
    protected function compileGroups(array $query, array $groups): string
    {
        return 'group by ' . $this->columnize($groups);
    }

    /**
     * Compile HAVING
     *
     * @param array<string, mixed> $query Query components
     * @param array<array<string, mixed>> $havings Having conditions
     * @return string SQL fragment
     */
    protected function compileHavings(array $query, array $havings): string
    {
        $sql = [];

        foreach ($havings as $having) {
            $boolean = (string) ($having['boolean'] ?? 'and');
            $column = (string) ($having['column'] ?? '');
            $operator = (string) ($having['operator'] ?? '=');

            $sql[] = $boolean . ' ' . $this->wrap($column) . ' ' . $operator . ' ?';
        }

        $sql = implode(' ', $sql);
        $result = preg_replace('/and |or /', '', $sql, 1);

        return 'having ' . ($result ?? '');
    }

    /**
     * Compile ORDER BY
     *
     * @param array<string, mixed> $query Query components
     * @param array<array<string, mixed>> $orders Order data
     * @return string SQL fragment
     */
    protected function compileOrders(array $query, array $orders): string
    {
        $sql = [];

        foreach ($orders as $order) {
            $column = (string) ($order['column'] ?? '');
            $direction = (string) ($order['direction'] ?? 'asc');

            $sql[] = $this->wrap($column) . ' ' . $direction;
        }

        return 'order by ' . implode(', ', $sql);
    }

    /**
     * Compile LIMIT
     *
     * @param array<string, mixed> $query Query components
     * @param int $limit Limit value
     * @return string SQL fragment
     */
    protected function compileLimit(array $query, int $limit): string
    {
        return 'limit ' . $limit;
    }

    /**
     * Compile OFFSET
     *
     * @param array<string, mixed> $query Query components
     * @param int $offset Offset value
     * @return string SQL fragment
     */
    protected function compileOffset(array $query, int $offset): string
    {
        return 'offset ' . $offset;
    }

    /**
     * Wrap table name
     *
     * @param string $table Table name
     * @return string Wrapped table name
     */
    protected function wrapTable(string $table): string
    {
        return $this->wrap($table);
    }

    /**
     * Wrap column/table identifier
     *
     * @param string $value Value to wrap
     * @return string Wrapped value
     */
    abstract protected function wrap(string $value): string;

    /**
     * Create parameter placeholders
     *
     * @param array<mixed> $values Values
     * @return string Comma-separated ? placeholders
     */
    protected function parameterize(array $values): string
    {
        return implode(', ', array_fill(0, count($values), '?'));
    }

    /**
     * Format column names
     *
     * @param array<string> $columns Columns
     * @return string Comma-separated wrapped columns
     */
    protected function columnize(array $columns): string
    {
        return implode(', ', array_map(fn ($c) => $this->wrap($c), $columns));
    }

    /**
     * Concatenate SQL parts
     *
     * @param array<string> $segments SQL segments
     * @return string Concatenated SQL
     */
    protected function concatenate(array $segments): string
    {
        return implode(' ', array_filter($segments, fn ($s) => $s !== ''));
    }
}
