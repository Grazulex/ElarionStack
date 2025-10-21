<?php

declare(strict_types=1);

namespace Elarion\Container\Bindings;

use Elarion\Container\Contracts\ResolverInterface;

/**
 * Singleton binding - returns the same instance every time
 *
 * Perfect for:
 * - Database connections
 * - Configuration objects
 * - Loggers
 * - Any service that should be shared across the application
 */
final class SingletonBinding extends AbstractBinding
{
    /**
     * Create a new singleton binding
     *
     * @param string $abstract The abstract identifier
     * @param string $concrete The concrete class to instantiate
     * @param ResolverInterface $resolver The resolver for instantiation
     */
    public function __construct(
        string $abstract,
        private string $concrete,
        ResolverInterface $resolver
    ) {
        parent::__construct($abstract, BindingType::Singleton, $resolver);
    }

    /**
     * Get the concrete class name
     */
    public function getConcrete(): string
    {
        return $this->concrete;
    }

    /**
     * {@inheritdoc}
     *
     * The AbstractBinding handles caching automatically based on isSingleton()
     */
    protected function doResolve(array $parameters = []): mixed
    {
        // Use the resolver to build the concrete class
        // This will only be called once, then cached by AbstractBinding
        return $this->resolver->build($this->concrete, $parameters);
    }
}
