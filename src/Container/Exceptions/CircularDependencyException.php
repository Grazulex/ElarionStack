<?php

declare(strict_types=1);

namespace Elarion\Container\Exceptions;

/**
 * Exception thrown when a circular dependency is detected
 *
 * Circular dependencies occur when class A depends on B, and B depends on A,
 * creating an infinite resolution loop.
 */
class CircularDependencyException extends ContainerException
{
    /**
     * Create an exception with the dependency chain
     *
     * @param array<int, string> $resolutionPath The path showing the circular dependency
     */
    public static function create(array $resolutionPath): self
    {
        $chain = implode(' -> ', $resolutionPath);

        return new self(
            sprintf(
                'Circular dependency detected: %s',
                $chain
            )
        );
    }

    /**
     * Create an exception when attempting to resolve a class already in the resolution stack
     *
     * @param array<int, string> $resolutionStack
     */
    public static function whileResolving(string $class, array $resolutionStack): self
    {
        $stack = array_merge($resolutionStack, [$class]);

        return self::create($stack);
    }
}
