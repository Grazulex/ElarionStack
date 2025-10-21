<?php

declare(strict_types=1);

namespace Elarion\Config\Contracts;

/**
 * Configuration repository interface
 *
 * Following ISP (Interface Segregation Principle),
 * this interface provides only essential configuration access methods.
 */
interface ConfigRepositoryInterface
{
    /**
     * Get a configuration value using dot notation
     *
     * @param string $key Configuration key (e.g., 'app.name', 'database.connections.mysql.host')
     * @param mixed $default Default value if key doesn't exist
     * @return mixed The configuration value or default
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Check if a configuration key exists
     *
     * @param string $key Configuration key with dot notation
     */
    public function has(string $key): bool;

    /**
     * Set a configuration value
     *
     * @param string $key Configuration key with dot notation
     * @param mixed $value The value to set
     */
    public function set(string $key, mixed $value): void;

    /**
     * Get all configuration values
     *
     * @return array<string, mixed> All configuration data
     */
    public function all(): array;

    /**
     * Load configuration from an array
     *
     * @param string $name Configuration name (file name without extension)
     * @param array<string, mixed> $config Configuration data
     */
    public function load(string $name, array $config): void;

    /**
     * Load multiple configuration arrays at once
     *
     * @param array<string, array<string, mixed>> $configs
     */
    public function loadMany(array $configs): void;
}
