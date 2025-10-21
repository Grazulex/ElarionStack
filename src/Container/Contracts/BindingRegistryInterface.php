<?php

declare(strict_types=1);

namespace Elarion\Container\Contracts;

/**
 * Registry for managing container bindings
 *
 * Following the Single Responsibility Principle (SRP),
 * this interface is solely responsible for binding storage and retrieval.
 * It does NOT handle resolution - that's the Resolver's job.
 */
interface BindingRegistryInterface
{
    /**
     * Register a binding in the registry
     *
     * @param string $abstract The abstract identifier (class or interface name)
     * @param BindingInterface $binding The binding implementation
     */
    public function bind(string $abstract, BindingInterface $binding): void;

    /**
     * Check if a binding exists for the given abstract
     */
    public function has(string $abstract): bool;

    /**
     * Retrieve a binding for the given abstract
     *
     * @throws \Psr\Container\NotFoundExceptionInterface If binding not found
     */
    public function get(string $abstract): BindingInterface;

    /**
     * Get all registered bindings
     *
     * @return array<string, BindingInterface>
     */
    public function all(): array;

    /**
     * Remove a binding from the registry
     */
    public function remove(string $abstract): void;
}
