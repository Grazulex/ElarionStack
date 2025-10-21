<?php

declare(strict_types=1);

namespace Elarion\Database\Query\Grammar;

/**
 * PostgreSQL Grammar
 *
 * PostgreSQL-specific SQL generation with double-quote wrapping.
 */
final class PostgresGrammar extends Grammar
{
    /**
     * {@inheritdoc}
     */
    protected function wrap(string $value): string
    {
        if ($value === '*') {
            return $value;
        }

        // Handle table.column
        if (str_contains($value, '.')) {
            $segments = explode('.', $value);

            return implode('.', array_map(fn ($s) => $this->wrapSegment($s), $segments));
        }

        return $this->wrapSegment($value);
    }

    /**
     * Wrap single segment with double quotes
     *
     * @param string $segment Segment to wrap
     * @return string Wrapped segment
     */
    private function wrapSegment(string $segment): string
    {
        if ($segment === '*') {
            return $segment;
        }

        return '"' . str_replace('"', '""', $segment) . '"';
    }
}
