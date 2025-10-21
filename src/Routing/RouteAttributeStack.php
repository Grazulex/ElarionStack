<?php

declare(strict_types=1);

namespace Elarion\Routing;

/**
 * Route attribute stack
 *
 * Manages route group attribute inheritance.
 * Following SRP - only handles attribute stacking/merging.
 */
final class RouteAttributeStack
{
    /**
     * Attribute stack
     *
     * @var array<int, array{prefix?: string, middleware?: array<int, string|callable>, namespace?: string}>
     */
    private array $stack = [];

    /**
     * Push attributes onto stack
     *
     * @param array{prefix?: string, middleware?: array<int, string|callable>, namespace?: string} $attributes
     * @return void
     */
    public function push(array $attributes): void
    {
        $this->stack[] = $attributes;
    }

    /**
     * Pop attributes from stack
     *
     * @return void
     */
    public function pop(): void
    {
        array_pop($this->stack);
    }

    /**
     * Get current merged attributes
     *
     * @return array{prefix: string, middleware: array<int, string|callable>, namespace: string}
     */
    public function current(): array
    {
        $merged = [
            'prefix' => '',
            'middleware' => [],
            'namespace' => '',
        ];

        foreach ($this->stack as $attributes) {
            // Merge prefix
            if (isset($attributes['prefix'])) {
                $merged['prefix'] = $this->mergePrefix($merged['prefix'], $attributes['prefix']);
            }

            // Merge middleware
            if (isset($attributes['middleware'])) {
                $merged['middleware'] = array_merge($merged['middleware'], $attributes['middleware']);
            }

            // Merge namespace
            if (isset($attributes['namespace'])) {
                $merged['namespace'] = $this->mergeNamespace($merged['namespace'], $attributes['namespace']);
            }
        }

        return $merged;
    }

    /**
     * Merge URI prefixes
     *
     * @param string $existing Existing prefix
     * @param string $new New prefix to append
     * @return string Merged prefix
     */
    private function mergePrefix(string $existing, string $new): string
    {
        // Normalize slashes
        $existing = trim($existing, '/');
        $new = trim($new, '/');

        if ($existing === '') {
            return $new;
        }

        if ($new === '') {
            return $existing;
        }

        return $existing . '/' . $new;
    }

    /**
     * Merge namespaces
     *
     * @param string $existing Existing namespace
     * @param string $new New namespace to append
     * @return string Merged namespace
     */
    private function mergeNamespace(string $existing, string $new): string
    {
        // Normalize backslashes
        $existing = trim($existing, '\\');
        $new = trim($new, '\\');

        if ($existing === '') {
            return $new;
        }

        if ($new === '') {
            return $existing;
        }

        return $existing . '\\' . $new;
    }

    /**
     * Check if stack is empty
     *
     * @return bool True if empty
     */
    public function isEmpty(): bool
    {
        return empty($this->stack);
    }

    /**
     * Get stack depth
     *
     * @return int Current depth
     */
    public function depth(): int
    {
        return count($this->stack);
    }
}
