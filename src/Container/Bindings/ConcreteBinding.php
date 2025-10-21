<?php

declare(strict_types=1);

namespace Elarion\Container\Bindings;

use Elarion\Container\Contracts\ResolverInterface;

/**
 * Binding for concrete classes
 *
 * Creates a new instance every time using the resolver.
 * Perfect for transient dependencies.
 */
final class ConcreteBinding extends AbstractBinding
{
    /**
     * Create a new concrete binding
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
        parent::__construct($abstract, BindingType::Concrete, $resolver);
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
     */
    protected function doResolve(array $parameters = []): mixed
    {
        // Use the resolver to build the concrete class
        return $this->resolver->build($this->concrete, $parameters);
    }
}
