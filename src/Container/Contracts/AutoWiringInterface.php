<?php

declare(strict_types=1);

namespace Elarion\Container\Contracts;

/**
 * Provides auto-wiring capabilities for dependency resolution
 *
 * Auto-wiring uses reflection to automatically resolve constructor dependencies
 * without requiring explicit binding for every class.
 *
 * This interface is separated (ISP) from ResolverInterface to allow
 * implementations that don't support auto-wiring.
 */
interface AutoWiringInterface
{
    /**
     * Resolve constructor dependencies automatically using reflection
     *
     * @param \ReflectionClass<object> $reflectionClass The class to analyze
     * @param array<string, mixed> $parameters Parameters to override automatic resolution
     * @return array<int, mixed> The resolved constructor arguments
     * @throws \Psr\Container\ContainerExceptionInterface If auto-wiring fails
     */
    public function resolveConstructorDependencies(
        \ReflectionClass $reflectionClass,
        array $parameters = []
    ): array;

    /**
     * Check if a class can be auto-wired
     *
     * A class can be auto-wired if:
     * - It's instantiable (not abstract or interface)
     * - Its constructor dependencies can be resolved
     */
    public function canAutoWire(string $class): bool;

    /**
     * Resolve dependencies for a method
     *
     * @param \ReflectionMethod $method The method to analyze
     * @param array<string, mixed> $parameters Parameters to override automatic resolution
     * @return array<int, mixed> The resolved method arguments
     * @throws \Psr\Container\ContainerExceptionInterface If resolution fails
     */
    public function resolveMethodDependencies(
        \ReflectionMethod $method,
        array $parameters = []
    ): array;
}
