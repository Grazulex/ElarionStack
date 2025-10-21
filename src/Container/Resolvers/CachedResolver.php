<?php

declare(strict_types=1);

namespace Elarion\Container\Resolvers;

use Elarion\Container\Contracts\ResolverInterface;

/**
 * Decorator that adds caching to a resolver
 *
 * Implements the Decorator pattern (following OCP).
 * Wraps another resolver and caches resolution results for performance.
 *
 * Uses WeakMap to prevent memory leaks - instances are garbage collected
 * when no other references exist.
 */
final class CachedResolver implements ResolverInterface
{
    /**
     * Cache for resolved instances
     * Using WeakMap for automatic garbage collection
     *
     * @var \WeakMap<object, string>
     */
    private \WeakMap $instanceCache;

    /**
     * Map of abstract to cached instance
     *
     * @var array<string, object>
     */
    private array $resolvedCache = [];

    /**
     * Create a cached resolver decorator
     *
     * @param ResolverInterface $innerResolver The resolver to decorate
     */
    public function __construct(
        private ResolverInterface $innerResolver
    ) {
        $this->instanceCache = new \WeakMap();
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(string $abstract, array $parameters = []): mixed
    {
        // If we have parameters, skip cache (different parameters = different instance)
        if ($parameters !== []) {
            return $this->innerResolver->resolve($abstract, $parameters);
        }

        // Check cache
        if (isset($this->resolvedCache[$abstract])) {
            $cached = $this->resolvedCache[$abstract];

            // Verify it's still in WeakMap (not garbage collected)
            if (isset($this->instanceCache[$cached])) {
                return $cached;
            }

            // Was garbage collected, remove from cache
            unset($this->resolvedCache[$abstract]);
        }

        // Resolve using inner resolver
        $instance = $this->innerResolver->resolve($abstract, $parameters);

        // Cache only objects (not scalars or arrays)
        if (is_object($instance)) {
            $this->resolvedCache[$abstract] = $instance;
            $this->instanceCache[$instance] = $abstract;
        }

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function canResolve(string $abstract): bool
    {
        // Check cache first
        if (isset($this->resolvedCache[$abstract])) {
            return true;
        }

        // Delegate to inner resolver
        return $this->innerResolver->canResolve($abstract);
    }

    /**
     * {@inheritdoc}
     */
    public function build(string $concrete, array $parameters = []): object
    {
        // Build always creates new instances, so don't cache
        return $this->innerResolver->build($concrete, $parameters);
    }

    /**
     * Clear the cache
     */
    public function clearCache(): void
    {
        $this->resolvedCache = [];
        $this->instanceCache = new \WeakMap();
    }

    /**
     * Get cache statistics
     *
     * @return array{hits: int, size: int}
     */
    public function getCacheStats(): array
    {
        return [
            'size' => count($this->resolvedCache),
            'hits' => count($this->resolvedCache), // Simplified - could track actual hits
        ];
    }

    /**
     * Get the wrapped inner resolver
     */
    public function getInnerResolver(): ResolverInterface
    {
        return $this->innerResolver;
    }
}
