<?php

declare(strict_types=1);

namespace Elarion\Container;

use Elarion\Container\Contracts\AutoWiringInterface;
use Elarion\Container\Exceptions\ContainerException;

/**
 * Handles method injection for automatic dependency resolution
 *
 * Allows calling any method with automatic dependency injection,
 * not just constructors.
 *
 * Following SRP: Only responsible for method calling with injection
 *
 * Example:
 * ```
 * $injector->call($controller, 'index', ['id' => 123]);
 * // Automatically resolves dependencies like Request, Database, etc.
 * ```
 */
final readonly class MethodInjector
{
    public function __construct(
        private Container $container,
        private AutoWiringInterface $autoWiring
    ) {
    }

    /**
     * Call a method with dependency injection
     *
     * @param object|class-string $target The object or class containing the method
     * @param string $method The method name
     * @param array<string, mixed> $parameters Parameters to override auto-resolution
     * @return mixed The method's return value
     * @throws ContainerException If method cannot be called
     */
    public function call(
        object|string $target,
        string $method,
        array $parameters = []
    ): mixed {
        // Build the target if it's a class name
        if (is_string($target)) {
            $target = $this->container->make($target);
        }

        // Get reflection of the method
        try {
            $reflectionMethod = new \ReflectionMethod($target, $method);
        } catch (\ReflectionException $e) {
            throw new ContainerException(
                sprintf(
                    'Method [%s::%s] does not exist',
                    $target::class,
                    $method
                ),
                0,
                $e
            );
        }

        // Make method accessible if needed
        if (!$reflectionMethod->isPublic()) {
            $reflectionMethod->setAccessible(true);
        }

        // Resolve method dependencies
        $dependencies = $this->autoWiring->resolveMethodDependencies(
            $reflectionMethod,
            $parameters
        );

        // Call the method
        return $reflectionMethod->invokeArgs($target, $dependencies);
    }

    /**
     * Call a callable with dependency injection
     *
     * @param callable $callable The callable to invoke
     * @param array<string, mixed> $parameters Parameters to override auto-resolution
     * @return mixed The callable's return value
     */
    public function callCallable(callable $callable, array $parameters = []): mixed
    {
        // Get reflection for the callable
        try {
            $reflection = $this->getCallableReflection($callable);
        } catch (\ReflectionException $e) {
            throw new ContainerException(
                'Cannot reflect on callable',
                0,
                $e
            );
        }

        // Resolve dependencies (only for ReflectionMethod, not ReflectionFunction)
        if ($reflection instanceof \ReflectionMethod) {
            $dependencies = $this->autoWiring->resolveMethodDependencies(
                $reflection,
                $parameters
            );
        } else {
            // For ReflectionFunction, we can't use the AutoWiring interface
            // We'll need to handle this differently or skip auto-wiring for functions
            $dependencies = [];
        }

        // Call it
        return $callable(...$dependencies);
    }

    /**
     * Get reflection for a callable
     *
     * @throws \ReflectionException
     */
    private function getCallableReflection(callable $callable): \ReflectionFunction|\ReflectionMethod
    {
        // Closure or function
        if ($callable instanceof \Closure || is_string($callable)) {
            return new \ReflectionFunction($callable);
        }

        // Array callable [object, method] or [class, method]
        if (is_array($callable) && count($callable) === 2) {
            /** @var array{0: object|string, 1: string} $callable */
            [$class, $method] = $callable;
            return new \ReflectionMethod($class, $method);
        }

        // Object with __invoke
        if (is_object($callable)) {
            return new \ReflectionMethod($callable, '__invoke');
        }

        throw new \ReflectionException('Unsupported callable type');
    }
}
