<?php

declare(strict_types=1);

namespace Elarion\Database;

use Elarion\Database\Exceptions\ConnectionException;

/**
 * Connection Factory
 *
 * Creates PDO instances from database configuration.
 * Following Factory pattern for object creation.
 */
final class ConnectionFactory
{
    /**
     * Create PDO connection from configuration
     *
     * @param DatabaseConfig $config Database configuration
     * @return \PDO PDO instance
     * @throws ConnectionException If connection fails
     */
    public function create(DatabaseConfig $config): \PDO
    {
        try {
            $pdo = new \PDO(
                $config->getDsn(),
                $config->username,
                $config->password,
                $config->getOptions()
            );

            // Additional configuration for specific drivers
            $this->configureConnection($pdo, $config);

            return $pdo;
        } catch (\PDOException $e) {
            throw $this->createConnectionException($config, $e);
        }
    }

    /**
     * Configure connection after creation
     *
     * Apply driver-specific configuration.
     *
     * @param \PDO $pdo PDO instance
     * @param DatabaseConfig $config Database configuration
     */
    private function configureConnection(\PDO $pdo, DatabaseConfig $config): void
    {
        // Set charset for PostgreSQL
        if ($config->driver === 'pgsql' && $config->charset !== '') {
            $pdo->exec(sprintf("SET NAMES '%s'", $config->charset));
        }

        // MySQL timezone configuration (optional, can be extended)
        if ($config->driver === 'mysql') {
            // Strict mode for MySQL
            $pdo->exec("SET sql_mode='STRICT_ALL_TABLES'");
        }
    }

    /**
     * Create appropriate connection exception
     *
     * @param DatabaseConfig $config Database configuration
     * @param \PDOException $e Original PDO exception
     * @return ConnectionException Wrapped exception
     */
    private function createConnectionException(
        DatabaseConfig $config,
        \PDOException $e
    ): ConnectionException {
        if ($config->driver === 'sqlite') {
            return ConnectionException::failedSqlite($config->database, $e);
        }

        return ConnectionException::failed(
            $config->driver,
            $config->host,
            $config->database,
            $e
        );
    }
}
