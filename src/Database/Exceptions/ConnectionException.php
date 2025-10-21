<?php

declare(strict_types=1);

namespace Elarion\Database\Exceptions;

/**
 * Connection Exception
 *
 * Thrown when database connection fails.
 * Provides context about connection attempt.
 */
class ConnectionException extends DatabaseException
{
    /**
     * Create exception for failed connection
     *
     * @param string $driver Database driver
     * @param string $host Database host
     * @param string $database Database name
     * @param \Throwable|null $previous Previous exception
     * @return self Exception instance
     */
    public static function failed(
        string $driver,
        string $host,
        string $database,
        ?\Throwable $previous = null
    ): self {
        $message = sprintf(
            'Failed to connect to %s database [%s] on host [%s]',
            $driver,
            $database,
            $host
        );

        if ($previous !== null) {
            $message .= sprintf(': %s', $previous->getMessage());
        }

        return new self($message, 0, $previous);
    }

    /**
     * Create exception for SQLite connection failure
     *
     * @param string $path SQLite database path
     * @param \Throwable|null $previous Previous exception
     * @return self Exception instance
     */
    public static function failedSqlite(string $path, ?\Throwable $previous = null): self
    {
        $message = sprintf('Failed to connect to SQLite database at [%s]', $path);

        if ($previous !== null) {
            $message .= sprintf(': %s', $previous->getMessage());
        }

        return new self($message, 0, $previous);
    }
}
