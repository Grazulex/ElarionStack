<?php

declare(strict_types=1);

namespace Elarion\Container\Bindings;

use Elarion\Container\Contracts\ResolverInterface;

/**
 * Alias binding - points to another binding
 *
 * Useful for:
 * - Binding interfaces to implementations
 * - Creating shortcuts to long class names
 * - Providing multiple names for the same service
 *
 * Example:
 *   LoggerInterface::class => Logger::class
 *   'log' => LoggerInterface::class
 */
final class AliasBinding extends AbstractBinding
{
    /**
     * Create a new alias binding
     *
     * @param string $abstract The abstract identifier (alias)
     * @param string $target The target abstract to resolve
     * @param ResolverInterface $resolver The resolver for resolution
     */
    public function __construct(
        string $abstract,
        private string $target,
        ResolverInterface $resolver
    ) {
        parent::__construct($abstract, BindingType::Alias, $resolver);
    }

    /**
     * Get the target abstract this alias points to
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * {@inheritdoc}
     */
    protected function doResolve(array $parameters = []): mixed
    {
        // Resolve the target instead of this abstract
        return $this->resolver->resolve($this->target, $parameters);
    }

    /**
     * Aliases are never singletons themselves
     * They inherit the singleton behavior from their target
     */
    public function isSingleton(): bool
    {
        return false;
    }
}
