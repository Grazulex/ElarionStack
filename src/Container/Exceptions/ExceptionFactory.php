<?php

declare(strict_types=1);

namespace Elarion\Container\Exceptions;

/**
 * Factory for creating container exceptions
 *
 * Following SRP, this class is responsible ONLY for creating exceptions
 * with proper context and messages.
 *
 * Using static factory methods provides:
 * - Consistent exception creation
 * - Better error messages
 * - Easier testing and mocking
 */
final readonly class ExceptionFactory
{
    /**
     * Create a NotFoundException for a missing binding
     */
    public static function notFound(string $abstract): NotFoundException
    {
        return NotFoundException::forAbstract($abstract);
    }

    /**
     * Create a NotFoundException with a suggestion
     */
    public static function notFoundWithSuggestion(
        string $abstract,
        string $suggestion
    ): NotFoundException {
        return NotFoundException::withSuggestion($abstract, $suggestion);
    }

    /**
     * Create exception for non-instantiable class
     */
    public static function cannotInstantiate(string $class, string $reason): ContainerException
    {
        return ContainerException::cannotInstantiate($class, $reason);
    }

    /**
     * Create exception for abstract class instantiation attempt
     */
    public static function cannotInstantiateAbstract(string $class): ContainerException
    {
        return self::cannotInstantiate(
            $class,
            'Class is abstract and cannot be instantiated'
        );
    }

    /**
     * Create exception for interface instantiation attempt
     */
    public static function cannotInstantiateInterface(string $interface): ContainerException
    {
        return self::cannotInstantiate(
            $interface,
            'Interfaces cannot be instantiated. Did you forget to bind it to a concrete class?'
        );
    }

    /**
     * Create exception for unresolvable dependency
     */
    public static function unresolvableDependency(
        string $class,
        string $parameter,
        ?string $type = null
    ): ContainerException {
        return ContainerException::unresolvableDependency($class, $parameter, $type);
    }

    /**
     * Create exception for circular dependency
     *
     * @param array<int, string> $resolutionPath
     */
    public static function circularDependency(array $resolutionPath): CircularDependencyException
    {
        return CircularDependencyException::create($resolutionPath);
    }

    /**
     * Create exception when auto-wiring fails
     */
    public static function autoWiringFailed(string $class, string $reason): ContainerException
    {
        return ContainerException::autoWiringFailed($class, $reason);
    }

    /**
     * Create exception for reflection errors
     */
    public static function reflectionFailed(string $class, \Throwable $previous): ContainerException
    {
        return new ContainerException(
            sprintf('Reflection failed for class [%s]: %s', $class, $previous->getMessage()),
            0,
            $previous
        );
    }
}
