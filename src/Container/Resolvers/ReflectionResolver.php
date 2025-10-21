<?php

declare(strict_types=1);

namespace Elarion\Container\Resolvers;

use Elarion\Container\Contracts\AutoWiringInterface;
use Elarion\Container\Contracts\BindingRegistryInterface;
use Elarion\Container\Exceptions\ExceptionFactory;

/**
 * Resolver using PHP Reflection for automatic dependency injection
 *
 * This is the core of the container's auto-wiring capability.
 * Uses reflection to analyze class constructors and automatically
 * resolve their dependencies.
 *
 * Implements both ResolverInterface and AutoWiringInterface.
 */
final class ReflectionResolver extends AbstractResolver implements AutoWiringInterface
{
    private readonly ParameterResolver $parameterResolver;

    /**
     * Cache for reflection classes to avoid repeated reflection
     * Using PHP 8.5 property hooks for lazy initialization
     *
     * @var array<string, \ReflectionClass<object>>
     */
    private array $reflectionCache = [];

    public function __construct(
        BindingRegistryInterface $registry,
        ResolutionStrategy $strategy = ResolutionStrategy::AutoWire
    ) {
        parent::__construct($registry, $strategy);
        $this->parameterResolver = new ParameterResolver($this);
    }

    /**
     * {@inheritdoc}
     */
    public function canResolve(string $abstract): bool
    {
        // Can resolve if binding exists
        if ($this->registry->has($abstract)) {
            return true;
        }

        // Can resolve if auto-wiring is enabled and class can be auto-wired
        if ($this->strategy->allowsAutoWiring()) {
            return $this->canAutoWire($abstract);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function doResolve(string $abstract, array $parameters): mixed
    {
        // Try to get from registry first
        if ($this->registry->has($abstract)) {
            $binding = $this->registry->get($abstract);
            return $binding->resolve($parameters);
        }

        // If auto-wiring is enabled, try to build it
        if ($this->strategy->allowsAutoWiring()) {
            return $this->build($abstract, $parameters);
        }

        // Cannot resolve
        throw ExceptionFactory::notFound($abstract);
    }

    /**
     * {@inheritdoc}
     */
    public function build(string $concrete, array $parameters = []): object
    {
        try {
            /** @phpstan-var class-string<object> $concrete */
            $reflectionClass = $this->getReflectionClass($concrete);
        } catch (\ReflectionException $e) {
            // If class doesn't exist, throw NotFoundException
            if (!class_exists($concrete) && !interface_exists($concrete)) {
                throw ExceptionFactory::notFound($concrete);
            }
            throw ExceptionFactory::reflectionFailed($concrete, $e);
        }

        // Check if class is instantiable
        if (!$reflectionClass->isInstantiable()) {
            if ($reflectionClass->isInterface()) {
                throw ExceptionFactory::cannotInstantiateInterface($concrete);
            }

            if ($reflectionClass->isAbstract()) {
                throw ExceptionFactory::cannotInstantiateAbstract($concrete);
            }

            throw ExceptionFactory::cannotInstantiate(
                $concrete,
                'Class is not instantiable'
            );
        }

        // Get constructor
        $constructor = $reflectionClass->getConstructor();

        // No constructor - simple instantiation
        if ($constructor === null) {
            return $reflectionClass->newInstance();
        }

        // Resolve constructor dependencies
        $dependencies = $this->resolveConstructorDependencies($reflectionClass, $parameters);

        // Instantiate with dependencies
        return $reflectionClass->newInstanceArgs($dependencies);
    }

    /**
     * {@inheritdoc}
     */
    public function canAutoWire(string $class): bool
    {
        try {
            /** @phpstan-var class-string<object> $class */
            $reflectionClass = $this->getReflectionClass($class);

            // Must be instantiable
            if (!$reflectionClass->isInstantiable()) {
                return false;
            }

            // If no constructor, can auto-wire
            $constructor = $reflectionClass->getConstructor();
            if ($constructor === null) {
                return true;
            }

            // Check if all constructor parameters can be resolved
            foreach ($constructor->getParameters() as $parameter) {
                if (!$this->canResolveParameter($parameter)) {
                    return false;
                }
            }

            return true;
        } catch (\ReflectionException) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function resolveConstructorDependencies(
        \ReflectionClass $reflectionClass,
        array $parameters = []
    ): array {
        $constructor = $reflectionClass->getConstructor();

        if ($constructor === null) {
            return [];
        }

        return $this->resolveMethodDependencies($constructor, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function resolveMethodDependencies(
        \ReflectionMethod $method,
        array $parameters = []
    ): array {
        $dependencies = [];

        foreach ($method->getParameters() as $parameter) {
            $dependencies[] = $this->parameterResolver->resolveParameter($parameter, $parameters);
        }

        return $dependencies;
    }

    /**
     * Get a cached reflection class
     *
     * @template T of object
     * @param class-string<T> $class
     * @return \ReflectionClass<T>
     */
    private function getReflectionClass(string $class): \ReflectionClass
    {
        if (!isset($this->reflectionCache[$class])) {
            /** @var \ReflectionClass<T> $reflection */
            $reflection = new \ReflectionClass($class);
            $this->reflectionCache[$class] = $reflection;
        }

        /** @var \ReflectionClass<T> $cached */
        $cached = $this->reflectionCache[$class];
        return $cached;
    }

    /**
     * Check if a parameter can be resolved
     */
    private function canResolveParameter(\ReflectionParameter $parameter): bool
    {
        // Has default value - always resolvable
        if ($parameter->isDefaultValueAvailable() || $parameter->isOptional()) {
            return true;
        }

        $type = $parameter->getType();

        // No type - cannot auto-resolve
        if ($type === null) {
            return false;
        }

        // Check named types
        if ($type instanceof \ReflectionNamedType) {
            // Built-in types need defaults
            if ($type->isBuiltin()) {
                return false;
            }

            // Class types - check if resolvable
            return $this->canResolve($type->getName());
        }

        // Union types - at least one must be resolvable
        if ($type instanceof \ReflectionUnionType) {
            foreach ($type->getTypes() as $unionType) {
                if ($unionType instanceof \ReflectionNamedType && !$unionType->isBuiltin()) {
                    if ($this->canResolve($unionType->getName())) {
                        return true;
                    }
                }
            }
            return $type->allowsNull(); // Fallback to null if allowed
        }

        // Intersection types - try first concrete type
        if ($type instanceof \ReflectionIntersectionType) {
            foreach ($type->getTypes() as $intersectionType) {
                if ($intersectionType instanceof \ReflectionNamedType && !$intersectionType->isBuiltin()) {
                    return $this->canResolve($intersectionType->getName());
                }
            }
        }

        return false;
    }
}
