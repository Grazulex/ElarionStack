<?php

declare(strict_types=1);

namespace Elarion\Database;

use Elarion\Database\Exceptions\ConfigurationException;

/**
 * Connection Manager
 *
 * Manages multiple named database connections with lazy-loading.
 * Following Facade pattern for unified database access.
 */
final class ConnectionManager
{
    /**
     * Connection factory
     */
    private readonly ConnectionFactory $factory;

    /**
     * Connection configurations
     *
     * @var array<string, DatabaseConfig>
     */
    private array $configs = [];

    /**
     * Active connections
     *
     * @var array<string, \PDO>
     */
    private array $connections = [];

    /**
     * Default connection name
     */
    private string $defaultConnection = 'default';

    /**
     * Create connection manager
     *
     * @param array<string, array<string, mixed>> $configs Connection configurations
     * @param ConnectionFactory|null $factory Connection factory instance
     */
    public function __construct(array $configs = [], ?ConnectionFactory $factory = null)
    {
        $this->factory = $factory ?? new ConnectionFactory();

        foreach ($configs as $name => $config) {
            $this->addConnection($name, DatabaseConfig::fromArray($config));
        }
    }

    /**
     * Add named connection configuration
     *
     * @param string $name Connection name
     * @param DatabaseConfig $config Database configuration
     * @return self Fluent interface
     */
    public function addConnection(string $name, DatabaseConfig $config): self
    {
        $this->configs[$name] = $config;

        // Remove existing connection if reconnecting
        unset($this->connections[$name]);

        return $this;
    }

    /**
     * Set default connection name
     *
     * @param string $name Connection name
     * @return self Fluent interface
     * @throws ConfigurationException If connection not configured
     */
    public function setDefaultConnection(string $name): self
    {
        if (! isset($this->configs[$name])) {
            throw ConfigurationException::missingConnection($name);
        }

        $this->defaultConnection = $name;

        return $this;
    }

    /**
     * Get connection by name
     *
     * Lazy-loads connection on first access.
     *
     * @param string|null $name Connection name (null = default)
     * @return \PDO PDO instance
     * @throws ConfigurationException If connection not configured
     */
    public function connection(?string $name = null): \PDO
    {
        $name = $name ?? $this->defaultConnection;

        // Return cached connection if exists
        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        // Check configuration exists
        if (! isset($this->configs[$name])) {
            throw ConfigurationException::missingConnection($name);
        }

        // Create and cache connection
        $this->connections[$name] = $this->factory->create($this->configs[$name]);

        return $this->connections[$name];
    }

    /**
     * Disconnect named connection
     *
     * @param string|null $name Connection name (null = all)
     * @return self Fluent interface
     */
    public function disconnect(?string $name = null): self
    {
        if ($name === null) {
            // Disconnect all
            $this->connections = [];
        } else {
            // Disconnect specific
            unset($this->connections[$name]);
        }

        return $this;
    }

    /**
     * Check if connection is configured
     *
     * @param string $name Connection name
     * @return bool True if configured
     */
    public function hasConnection(string $name): bool
    {
        return isset($this->configs[$name]);
    }

    /**
     * Check if connection is active (already created)
     *
     * @param string $name Connection name
     * @return bool True if connection exists
     */
    public function isConnected(string $name): bool
    {
        return isset($this->connections[$name]);
    }

    /**
     * Get all configured connection names
     *
     * @return array<int, string> Connection names
     */
    public function getConnectionNames(): array
    {
        return array_keys($this->configs);
    }

    /**
     * Get default connection name
     *
     * @return string Connection name
     */
    public function getDefaultConnection(): string
    {
        return $this->defaultConnection;
    }

    /**
     * Reconnect named connection
     *
     * Forces new connection even if already connected.
     *
     * @param string|null $name Connection name (null = default)
     * @return \PDO New PDO instance
     */
    public function reconnect(?string $name = null): \PDO
    {
        $name = $name ?? $this->defaultConnection;

        // Disconnect first
        $this->disconnect($name);

        // Return new connection
        return $this->connection($name);
    }
}
