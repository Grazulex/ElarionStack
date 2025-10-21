<?php

declare(strict_types=1);

namespace Elarion\Routing;

use Elarion\Routing\Contracts\RouteCollectorInterface;

/**
 * Route group
 *
 * Manages grouped routes with shared attributes.
 * Following SRP - handles group logic only.
 */
final class RouteGroup
{
    /**
     * Create route group
     *
     * @param RouteCollectorInterface $collector Route collector
     * @param RouteAttributeStack $attributeStack Attribute stack
     */
    public function __construct(
        private readonly RouteCollectorInterface $collector,
        private readonly RouteAttributeStack $attributeStack
    ) {
    }

    /**
     * Execute group with attributes
     *
     * @param array{prefix?: string, middleware?: array<int, string|callable>, namespace?: string} $attributes
     * @param callable $callback Callback to register routes
     * @return void
     */
    public function execute(array $attributes, callable $callback): void
    {
        // Push attributes onto stack
        $this->attributeStack->push($attributes);

        // Execute callback with collector
        $callback($this->collector);

        // Pop attributes from stack
        $this->attributeStack->pop();
    }

    /**
     * Get current group attributes
     *
     * @return array{prefix: string, middleware: array<int, string|callable>, namespace: string}
     */
    public function getCurrentAttributes(): array
    {
        return $this->attributeStack->current();
    }

    /**
     * Apply group attributes to URI
     *
     * @param string $uri Base URI
     * @return string URI with prefix applied
     */
    public function applyPrefix(string $uri): string
    {
        $attributes = $this->getCurrentAttributes();
        $prefix = $attributes['prefix'];

        if ($prefix === '') {
            return $uri;
        }

        // Normalize slashes
        $prefix = '/' . trim($prefix, '/');
        $uri = '/' . ltrim($uri, '/');

        return $prefix . $uri;
    }

    /**
     * Apply group middleware to route middleware
     *
     * @param array<int, string|callable> $routeMiddleware Route-specific middleware
     * @return array<int, string|callable> Merged middleware
     */
    public function applyMiddleware(array $routeMiddleware): array
    {
        $attributes = $this->getCurrentAttributes();
        $groupMiddleware = $attributes['middleware'];

        // Group middleware comes first
        return array_merge($groupMiddleware, $routeMiddleware);
    }

    /**
     * Apply namespace to handler
     *
     * @param callable|array{0: class-string, 1: string} $handler Route handler
     * @return callable|array{0: class-string, 1: string} Handler with namespace applied
     */
    public function applyNamespace(callable|array $handler): callable|array
    {
        // Only apply to array handlers [Controller::class, 'method']
        if (! is_array($handler)) {
            return $handler;
        }

        $attributes = $this->getCurrentAttributes();
        $namespace = $attributes['namespace'];

        if ($namespace === '' || ! isset($handler[0])) {
            /** @var array{0: class-string, 1: string} */
            return $handler;
        }

        // If handler is already fully qualified (starts with \), don't prepend
        if (is_string($handler[0]) && str_starts_with($handler[0], '\\')) {
            /** @var array{0: class-string, 1: string} */
            return $handler;
        }

        // Prepend namespace
        if (is_string($handler[0])) {
            $handler[0] = $namespace . '\\' . $handler[0];
        }

        /** @var array{0: class-string, 1: string} */
        return $handler;
    }
}
