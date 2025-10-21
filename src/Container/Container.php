<?php

declare(strict_types=1);

namespace Elarion\Container;

use Elarion\Container\Bindings\AliasBinding;
use Elarion\Container\Bindings\ConcreteBinding;
use Elarion\Container\Bindings\FactoryBinding;
use Elarion\Container\Bindings\SingletonBinding;
use Elarion\Container\Contracts\BindingRegistryInterface;
use Elarion\Container\Contracts\ResolverInterface;
use Elarion\Container\Registry\BindingRegistry;
use Elarion\Container\Resolvers\ReflectionResolver;
use Elarion\Container\Resolvers\ResolutionStrategy;
use Psr\Container\ContainerInterface;

/**
 * PSR-11 compliant Dependency Injection Container
 *
 * The main container class that brings together all components:
 * - Bindings (Concrete, Singleton, Factory, Alias)
 * - Registry (for storing bindings)
 * - Resolver (for resolving dependencies)
 *
 * PHP 8.5 Features:
 * - Constructor property promotion
 * - Fluent API with $this return types
 * - Type-safe bindings
 *
 * SOLID Principles:
 * - SRP: Coordinates components, delegates actual work
 * - OCP: Extensible through ResolverInterface
 * - DIP: Depends on abstractions, not concretions
 */
class Container implements ContainerInterface
{
    /**
     * The binding registry
     */
    protected readonly BindingRegistryInterface $registry;

    /**
     * The resolver for dependency resolution
     */
    protected readonly ResolverInterface $resolver;

    /**
     * Instances that have been resolved as singletons
     *
     * @var array<string, object>
     */
    protected array $instances = [];

    /**
     * Aliases for bindings
     *
     * @var array<string, string>
     */
    protected array $aliases = [];

    /**
     * Create a new container instance
     */
    public function __construct(
        ?BindingRegistryInterface $registry = null,
        ?ResolverInterface $resolver = null
    ) {
        $this->registry = $registry ?? new BindingRegistry();
        $this->resolver = $resolver ?? new ReflectionResolver(
            $this->registry,
            ResolutionStrategy::AutoWire
        );

        // Bind the container itself
        $this->instance(ContainerInterface::class, $this);
        $this->instance(Container::class, $this);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $id): mixed
    {
        /** @phpstan-var class-string $id */
        return $this->resolve($id);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $id): bool
    {
        // Check if it's an alias
        if (isset($this->aliases[$id])) {
            $id = $this->aliases[$id];
        }

        // Check instances
        if (isset($this->instances[$id])) {
            return true;
        }

        // Check registry
        if ($this->registry->has($id)) {
            return true;
        }

        // Check if resolver can resolve it
        return $this->resolver->canResolve($id);
    }

    /**
     * Resolve an abstract from the container
     *
     * @template T
     * @param class-string<T> $abstract
     * @param array<string, mixed> $parameters
     * @return T
     */
    public function resolve(string $abstract, array $parameters = []): mixed
    {
        // Resolve aliases
        $abstract = $this->getAlias($abstract);

        // Check for existing instance
        if (isset($this->instances[$abstract]) && $parameters === []) {
            /** @var T */
            $instance = $this->instances[$abstract];
            return $instance;
        }

        // Resolve using the resolver
        /** @var T $resolved */
        $resolved = $this->resolver->resolve($abstract, $parameters);
        return $resolved;
    }

    /**
     * Alias for resolve() - more expressive for on-demand creation
     *
     * @template T
     * @param class-string<T> $abstract
     * @param array<string, mixed> $parameters
     * @return T
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        return $this->resolve($abstract, $parameters);
    }

    /**
     * Build a concrete instance without going through bindings
     *
     * @template T of object
     * @param class-string<T> $concrete
     * @param array<string, mixed> $parameters
     * @return T
     */
    public function build(string $concrete, array $parameters = []): object
    {
        /** @var T */
        return $this->resolver->build($concrete, $parameters);
    }

    /**
     * Bind an abstract to a concrete implementation
     *
     * @param string $abstract The abstract identifier (interface or class)
     * @param string|null $concrete The concrete class (defaults to $abstract)
     * @return $this
     */
    public function bind(string $abstract, ?string $concrete = null): static
    {
        $concrete ??= $abstract;

        $binding = new ConcreteBinding($abstract, $concrete, $this->resolver);
        $this->registry->bind($abstract, $binding);

        return $this;
    }

    /**
     * Bind an abstract as a singleton
     *
     * @param string $abstract The abstract identifier
     * @param string|null $concrete The concrete class (defaults to $abstract)
     * @return $this
     */
    public function singleton(string $abstract, ?string $concrete = null): static
    {
        $concrete ??= $abstract;

        $binding = new SingletonBinding($abstract, $concrete, $this->resolver);
        $this->registry->bind($abstract, $binding);

        return $this;
    }

    /**
     * Bind a factory callback
     *
     * @param string $abstract The abstract identifier
     * @param callable $factory The factory callback
     * @param bool $singleton Whether to cache the result
     * @return $this
     */
    public function factory(string $abstract, callable $factory, bool $singleton = false): static
    {
        $binding = new FactoryBinding($abstract, $factory, $this->resolver, $singleton);
        $this->registry->bind($abstract, $binding);

        return $this;
    }

    /**
     * Register an existing instance as a singleton
     *
     * @template T of object
     * @param string $abstract
     * @param T $instance
     * @return $this
     */
    public function instance(string $abstract, object $instance): static
    {
        $this->instances[$abstract] = $instance;
        return $this;
    }

    /**
     * Create an alias for an abstract
     *
     * @param string $alias The alias name
     * @param string $abstract The target abstract
     * @return $this
     */
    public function alias(string $alias, string $abstract): static
    {
        if ($alias === $abstract) {
            throw new \InvalidArgumentException(
                "Alias [{$alias}] cannot reference itself"
            );
        }

        $this->aliases[$alias] = $abstract;

        // Also register in binding registry for completeness
        $binding = new AliasBinding($alias, $abstract, $this->resolver);
        $this->registry->bind($alias, $binding);

        return $this;
    }

    /**
     * Get the concrete class for an alias
     */
    protected function getAlias(string $abstract): string
    {
        return $this->aliases[$abstract] ?? $abstract;
    }

    /**
     * Remove a binding from the container
     *
     * @param string $abstract
     * @return $this
     */
    public function unbind(string $abstract): static
    {
        $this->registry->remove($abstract);
        unset($this->instances[$abstract]);
        unset($this->aliases[$abstract]);

        return $this;
    }

    /**
     * Check if an abstract is bound
     */
    public function bound(string $abstract): bool
    {
        return $this->registry->has($abstract) || isset($this->instances[$abstract]);
    }

    /**
     * Get all bindings
     *
     * @return array<string, \Elarion\Container\Contracts\BindingInterface>
     */
    public function getBindings(): array
    {
        return $this->registry->all();
    }

    /**
     * Flush the container of all bindings and instances
     */
    public function flush(): void
    {
        foreach ($this->registry->all() as $abstract => $binding) {
            $this->registry->remove($abstract);
        }

        $this->instances = [];
        $this->aliases = [];

        // Re-bind the container itself
        $this->instance(ContainerInterface::class, $this);
        $this->instance(Container::class, $this);
    }
}
