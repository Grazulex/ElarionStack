<?php

declare(strict_types=1);

namespace Elarion\Config\Contracts;

/**
 * Configuration cache interface
 *
 * Provides caching mechanism for configuration in production.
 * Following SRP - only responsible for cache operations.
 */
interface ConfigCacheInterface
{
    /**
     * Check if cached configuration exists
     */
    public function has(): bool;

    /**
     * Get cached configuration
     *
     * @return array<string, mixed> Cached configuration data
     * @throws \RuntimeException If cache doesn't exist
     */
    public function get(): array;

    /**
     * Store configuration in cache
     *
     * @param array<string, mixed> $config Configuration to cache
     */
    public function put(array $config): void;

    /**
     * Clear the configuration cache
     */
    public function clear(): void;

    /**
     * Get the cache file path
     */
    public function getCachePath(): string;
}
