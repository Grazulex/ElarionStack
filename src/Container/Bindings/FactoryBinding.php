<?php

declare(strict_types=1);

namespace Elarion\Container\Bindings;

use Elarion\Container\Contracts\ResolverInterface;

/**
 * Factory binding - uses a callback to create instances
 *
 * Provides maximum flexibility for complex instantiation logic.
 * The factory receives the container and parameters.
 */
final class FactoryBinding extends AbstractBinding
{
    /**
     * Create a new factory binding
     *
     * @param string $abstract The abstract identifier
     * @param callable(ResolverInterface, array<string, mixed>): mixed $factory The factory callable
     * @param ResolverInterface $resolver The resolver (passed to factory)
     * @param bool $singleton Whether to cache the result
     */
    public function __construct(
        string $abstract,
        private mixed $factory,
        ResolverInterface $resolver,
        bool $singleton = false
    ) {
        $type = $singleton ? BindingType::Singleton : BindingType::Factory;
        parent::__construct($abstract, $type, $resolver);
    }

    /**
     * Get the factory callable
     *
     * @return callable(ResolverInterface, array<string, mixed>): mixed
     */
    public function getFactory(): callable
    {
        return $this->factory;
    }

    /**
     * {@inheritdoc}
     */
    protected function doResolve(array $parameters = []): mixed
    {
        // Call the factory with the resolver and parameters
        return ($this->factory)($this->resolver, $parameters);
    }
}
