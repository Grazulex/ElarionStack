<?php

declare(strict_types=1);

namespace Elarion\Config\Contracts;

/**
 * Configuration file loader interface
 *
 * Following OCP (Open/Closed Principle),
 * new loaders can be added without modifying existing code.
 */
interface ConfigLoaderInterface
{
    /**
     * Load configuration from a file
     *
     * @param string $path Absolute path to configuration file
     * @return array<string, mixed> The loaded configuration data
     * @throws \RuntimeException If file cannot be loaded
     */
    public function load(string $path): array;

    /**
     * Check if this loader supports the given file path
     *
     * @param string $path File path to check
     */
    public function supports(string $path): bool;
}
