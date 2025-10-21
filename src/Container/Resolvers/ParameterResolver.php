<?php

declare(strict_types=1);

namespace Elarion\Container\Resolvers;

use Elarion\Container\Contracts\ResolverInterface;
use Elarion\Container\Exceptions\ContainerException;
use Elarion\Container\Exceptions\ExceptionFactory;

/**
 * Resolves individual constructor/method parameters
 *
 * Following SRP, this class is focused ONLY on parameter resolution logic.
 * Handles union types, intersection types, and nullable types from PHP 8.5.
 */
final readonly class ParameterResolver
{
    public function __construct(
        private ResolverInterface $resolver
    ) {
    }

    /**
     * Resolve a single parameter
     *
     * @param \ReflectionParameter $parameter The parameter to resolve
     * @param array<string, mixed> $overrides Parameters that override auto-resolution
     * @return mixed The resolved value
     * @throws ContainerException If parameter cannot be resolved
     */
    public function resolveParameter(
        \ReflectionParameter $parameter,
        array $overrides = []
    ): mixed {
        $name = $parameter->getName();

        // Check if there's an override for this parameter
        if (array_key_exists($name, $overrides)) {
            return $overrides[$name];
        }

        // Get the parameter type
        $type = $parameter->getType();

        // No type hint - check for default value
        if ($type === null) {
            return $this->resolveUntyped($parameter);
        }

        // Handle different type scenarios
        return match (true) {
            $type instanceof \ReflectionNamedType => $this->resolveNamedType($parameter, $type),
            $type instanceof \ReflectionUnionType => $this->resolveUnionType($parameter, $type),
            $type instanceof \ReflectionIntersectionType => $this->resolveIntersectionType($parameter, $type),
            default => throw ExceptionFactory::autoWiringFailed(
                $parameter->getDeclaringClass()?->getName() ?? 'unknown',
                sprintf('Unsupported parameter type for [%s]', $name)
            ),
        };
    }

    /**
     * Resolve an untyped parameter
     */
    private function resolveUntyped(\ReflectionParameter $parameter): mixed
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw ExceptionFactory::unresolvableDependency(
            $parameter->getDeclaringClass()?->getName() ?? 'unknown',
            $parameter->getName()
        );
    }

    /**
     * Resolve a named type (class, int, string, etc.)
     */
    private function resolveNamedType(
        \ReflectionParameter $parameter,
        \ReflectionNamedType $type
    ): mixed {
        $typeName = $type->getName();

        // Built-in types (int, string, bool, etc.)
        if ($type->isBuiltin()) {
            return $this->resolveBuiltinType($parameter, $typeName);
        }

        // Class or interface type - try to resolve from container
        try {
            return $this->resolver->resolve($typeName);
        } catch (\Throwable $e) {
            // If nullable or has default, use those
            if ($type->allowsNull()) {
                return null;
            }

            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            // Re-throw with better context
            throw ExceptionFactory::unresolvableDependency(
                $parameter->getDeclaringClass()?->getName() ?? 'unknown',
                $parameter->getName(),
                $typeName
            );
        }
    }

    /**
     * Resolve a built-in type parameter
     */
    private function resolveBuiltinType(\ReflectionParameter $parameter, string $typeName): mixed
    {
        // For built-in types, we need a default value
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        // Variadic parameters default to empty array
        if ($parameter->isVariadic()) {
            return [];
        }

        throw ExceptionFactory::unresolvableDependency(
            $parameter->getDeclaringClass()?->getName() ?? 'unknown',
            $parameter->getName(),
            $typeName
        );
    }

    /**
     * Resolve a union type (PHP 8.0+)
     *
     * Try each type in order until one resolves successfully
     */
    private function resolveUnionType(
        \ReflectionParameter $parameter,
        \ReflectionUnionType $type
    ): mixed {
        $types = $type->getTypes();

        foreach ($types as $unionType) {
            if (!$unionType instanceof \ReflectionNamedType) {
                continue;
            }

            $typeName = $unionType->getName();

            // Skip built-in types in unions (handle them last)
            if ($unionType->isBuiltin()) {
                continue;
            }

            // Try to resolve class/interface types
            try {
                return $this->resolver->resolve($typeName);
            } catch (\Throwable) {
                continue; // Try next type
            }
        }

        // If no class types worked, try defaults
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        // If null is allowed
        if ($type->allowsNull()) {
            return null;
        }

        $namedTypes = array_filter($types, fn($t) => $t instanceof \ReflectionNamedType);
        throw ExceptionFactory::unresolvableDependency(
            $parameter->getDeclaringClass()?->getName() ?? 'unknown',
            $parameter->getName(),
            implode('|', array_map(fn(\ReflectionNamedType $t) => $t->getName(), $namedTypes))
        );
    }

    /**
     * Resolve an intersection type (PHP 8.1+)
     *
     * All types must be satisfied - resolve the first concrete class
     */
    private function resolveIntersectionType(
        \ReflectionParameter $parameter,
        \ReflectionIntersectionType $type
    ): mixed {
        $types = $type->getTypes();

        // Find the first concrete class type
        foreach ($types as $intersectionType) {
            if (!$intersectionType instanceof \ReflectionNamedType) {
                continue;
            }

            $typeName = $intersectionType->getName();

            if (!$intersectionType->isBuiltin()) {
                try {
                    // Attempt to resolve - the returned instance should satisfy all types
                    return $this->resolver->resolve($typeName);
                } catch (\Throwable) {
                    continue;
                }
            }
        }

        $namedTypes = array_filter($types, fn($t) => $t instanceof \ReflectionNamedType);
        throw ExceptionFactory::unresolvableDependency(
            $parameter->getDeclaringClass()?->getName() ?? 'unknown',
            $parameter->getName(),
            implode('&', array_map(fn(\ReflectionNamedType $t) => $t->getName(), $namedTypes))
        );
    }
}
