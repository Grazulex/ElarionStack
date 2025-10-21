<?php

declare(strict_types=1);

namespace Elarion\Container\Contracts;

/**
 * Handles the resolution of dependencies
 *
 * Following SRP, the Resolver is responsible ONLY for instantiation logic,
 * not for managing bindings (that's the Registry's job).
 *
 * This allows for different resolution strategies (Reflection, Cached, etc.)
 * following the Strategy Pattern and Open/Closed Principle.
 */
interface ResolverInterface
{
    /**
     * Resolve a class to a concrete instance
     *
     * @param string $abstract The class or interface to resolve
     * @param array<string, mixed> $parameters Optional parameters for construction
     * @return mixed The resolved instance
     * @throws \Psr\Container\ContainerExceptionInterface If resolution fails
     */
    public function resolve(string $abstract, array $parameters = []): mixed;

    /**
     * Check if the resolver can resolve the given abstract
     */
    public function canResolve(string $abstract): bool;

    /**
     * Build a concrete instance of a class
     *
     * This method handles the actual instantiation with dependency injection
     *
     * @param string $concrete The concrete class name to build
     * @param array<string, mixed> $parameters Optional parameters to override
     * @return object The built instance
     * @throws \Psr\Container\ContainerExceptionInterface If build fails
     */
    public function build(string $concrete, array $parameters = []): object;
}
