<?php

declare(strict_types=1);

namespace Elarion\Database\Exceptions;

/**
 * Configuration Exception
 *
 * Thrown when database configuration is invalid or missing.
 */
class ConfigurationException extends DatabaseException
{
    /**
     * Create exception for missing connection configuration
     *
     * @param string $name Connection name
     * @return self Exception instance
     */
    public static function missingConnection(string $name): self
    {
        return new self(
            sprintf('Database connection [%s] is not configured', $name)
        );
    }

    /**
     * Create exception for invalid configuration key
     *
     * @param string $key Configuration key
     * @return self Exception instance
     */
    public static function invalidKey(string $key): self
    {
        return new self(
            sprintf('Invalid database configuration key: %s', $key)
        );
    }
}
