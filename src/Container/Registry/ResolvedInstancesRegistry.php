<?php

declare(strict_types=1);

namespace Elarion\Container\Registry;

/**
 * Registry for tracking resolved singleton instances
 *
 * Separates singleton instance management from binding storage (SRP).
 * Uses WeakMap internally to prevent memory leaks.
 *
 * PHP 8.5 Features:
 * - WeakMap for automatic garbage collection
 * - Readonly properties where applicable
 */
final class ResolvedInstancesRegistry
{
    /**
     * Storage for resolved instances
     * Using WeakMap to allow garbage collection when no other references exist
     *
     * @var \WeakMap<object, true>
     */
    private \WeakMap $instances;

    /**
     * Storage for instance metadata (abstract -> instance)
     *
     * @var array<string, object>
     */
    private array $instanceMap = [];

    public function __construct()
    {
        $this->instances = new \WeakMap();
    }

    /**
     * Store a resolved instance
     */
    public function store(string $abstract, object $instance): void
    {
        $this->instanceMap[$abstract] = $instance;
        $this->instances[$instance] = true;
    }

    /**
     * Check if an instance has been resolved for an abstract
     */
    public function has(string $abstract): bool
    {
        return isset($this->instanceMap[$abstract]);
    }

    /**
     * Get a resolved instance
     */
    public function get(string $abstract): ?object
    {
        return $this->instanceMap[$abstract] ?? null;
    }

    /**
     * Remove a resolved instance
     */
    public function remove(string $abstract): void
    {
        if (isset($this->instanceMap[$abstract])) {
            unset($this->instances[$this->instanceMap[$abstract]]);
            unset($this->instanceMap[$abstract]);
        }
    }

    /**
     * Clear all resolved instances
     */
    public function clear(): void
    {
        $this->instanceMap = [];
        $this->instances = new \WeakMap();
    }

    /**
     * Get all resolved abstracts
     *
     * @return array<int, string>
     */
    public function getAbstracts(): array
    {
        return array_keys($this->instanceMap);
    }

    /**
     * Get the count of resolved instances
     */
    public function count(): int
    {
        return count($this->instanceMap);
    }

    /**
     * Check if a specific instance is tracked
     */
    public function isTracked(object $instance): bool
    {
        return isset($this->instances[$instance]);
    }
}
