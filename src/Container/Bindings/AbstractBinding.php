<?php

declare(strict_types=1);

namespace Elarion\Container\Bindings;

use Elarion\Container\Contracts\BindingInterface;
use Elarion\Container\Contracts\ResolverInterface;

/**
 * Abstract base class for all bindings
 *
 * Following the Template Method pattern and OCP,
 * this class provides common functionality while allowing
 * subclasses to customize the resolution behavior.
 *
 * Uses PHP 8.5 features:
 * - Constructor property promotion
 * - Protected properties for encapsulation
 */
abstract class AbstractBinding implements BindingInterface
{
    /**
     * The cached resolved instance (for singletons)
     */
    protected mixed $resolvedInstance = null;

    /**
     * Create a new binding
     *
     * @param string $abstract The abstract identifier (class or interface name)
     * @param BindingType $type The binding type
     * @param ResolverInterface $resolver The resolver to use for instantiation
     */
    public function __construct(
        protected string $abstract,
        protected BindingType $type,
        protected ResolverInterface $resolver
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getAbstract(): string
    {
        return $this->abstract;
    }

    /**
     * {@inheritdoc}
     */
    public function isSingleton(): bool
    {
        return $this->type->shouldCache();
    }

    /**
     * {@inheritdoc}
     */
    public function isResolved(): bool
    {
        return $this->resolvedInstance !== null;
    }

    /**
     * Get the binding type
     */
    public function getType(): BindingType
    {
        return $this->type;
    }

    /**
     * Template method for resolving the binding
     *
     * Subclasses override this to provide specific resolution logic
     *
     * @param array<string, mixed> $parameters
     * @return mixed
     */
    abstract protected function doResolve(array $parameters = []): mixed;

    /**
     * {@inheritdoc}
     */
    public function resolve(array $parameters = []): mixed
    {
        // If singleton and already resolved, return cached instance
        if ($this->isSingleton() && $this->isResolved()) {
            return $this->resolvedInstance;
        }

        // Perform the actual resolution
        $instance = $this->doResolve($parameters);

        // Cache if singleton
        if ($this->isSingleton()) {
            $this->resolvedInstance = $instance;
        }

        return $instance;
    }
}
