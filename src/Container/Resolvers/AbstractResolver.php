<?php

declare(strict_types=1);

namespace Elarion\Container\Resolvers;

use Elarion\Container\Contracts\BindingRegistryInterface;
use Elarion\Container\Contracts\ResolverInterface;
use Elarion\Container\Exceptions\CircularDependencyException;

/**
 * Abstract base resolver with common resolution logic
 *
 * Implements the Template Method pattern for the resolution process.
 * Provides circular dependency detection.
 *
 * PHP 8.5: Uses constructor property promotion
 */
abstract class AbstractResolver implements ResolverInterface
{
    /**
     * Track resolution path to detect circular dependencies
     *
     * @var array<int, string>
     */
    private array $resolutionStack = [];

    /**
     * Create a new resolver
     *
     * @param BindingRegistryInterface $registry The binding registry
     * @param ResolutionStrategy $strategy The resolution strategy
     */
    public function __construct(
        protected BindingRegistryInterface $registry,
        protected ResolutionStrategy $strategy = ResolutionStrategy::AutoWire
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(string $abstract, array $parameters = []): mixed
    {
        // Check for circular dependency
        if ($this->isResolving($abstract)) {
            throw CircularDependencyException::whileResolving($abstract, $this->resolutionStack);
        }

        // Add to resolution stack
        $this->pushResolution($abstract);

        try {
            // Perform the actual resolution
            $instance = $this->doResolve($abstract, $parameters);

            // Remove from resolution stack
            $this->popResolution();

            return $instance;
        } catch (\Throwable $e) {
            // Clean up resolution stack on error
            $this->popResolution();
            throw $e;
        }
    }

    /**
     * Template method for actual resolution
     * Subclasses implement their specific resolution logic here
     *
     * @param string $abstract
     * @param array<string, mixed> $parameters
     * @return mixed
     */
    abstract protected function doResolve(string $abstract, array $parameters): mixed;

    /**
     * Check if we're currently resolving the given abstract
     */
    protected function isResolving(string $abstract): bool
    {
        return in_array($abstract, $this->resolutionStack, true);
    }

    /**
     * Add an abstract to the resolution stack
     */
    protected function pushResolution(string $abstract): void
    {
        $this->resolutionStack[] = $abstract;
    }

    /**
     * Remove the last abstract from the resolution stack
     */
    protected function popResolution(): void
    {
        array_pop($this->resolutionStack);
    }

    /**
     * Get the current resolution stack
     *
     * @return array<int, string>
     */
    protected function getResolutionStack(): array
    {
        return $this->resolutionStack;
    }

    /**
     * Get the resolution strategy
     */
    public function getStrategy(): ResolutionStrategy
    {
        return $this->strategy;
    }
}
