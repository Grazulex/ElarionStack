<?php

declare(strict_types=1);

namespace Elarion\Database;

/**
 * Database Configuration Value Object
 *
 * Immutable configuration for database connections.
 * Following Value Object pattern for type safety.
 */
final readonly class DatabaseConfig
{
    /**
     * Create database configuration
     *
     * @param string $driver Database driver (mysql, pgsql, sqlite)
     * @param string $database Database name or path (for SQLite)
     * @param string $host Database host (not used for SQLite)
     * @param int $port Database port (not used for SQLite)
     * @param string $username Database username (not used for SQLite)
     * @param string $password Database password (not used for SQLite)
     * @param string $charset Character set (default: utf8mb4)
     * @param array<string, mixed> $options Additional PDO options
     */
    public function __construct(
        public string $driver,
        public string $database,
        public string $host = 'localhost',
        public int $port = 3306,
        public string $username = '',
        public string $password = '',
        public string $charset = 'utf8mb4',
        public array $options = []
    ) {
        $this->validateDriver($driver);
        $this->validateDatabase($database);

        if ($driver !== 'sqlite') {
            $this->validateHost($host);
            $this->validatePort($port);
        }
    }

    /**
     * Create configuration from array
     *
     * @param array<string, mixed> $config Configuration array
     * @return self Configuration instance
     */
    public static function fromArray(array $config): self
    {
        $driver = $config['driver'] ?? 'mysql';
        $database = $config['database'] ?? '';
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? 3306;
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';
        $optionsRaw = $config['options'] ?? [];

        assert(is_string($driver));
        assert(is_string($database));
        assert(is_string($host));
        assert(is_int($port) || is_string($port));
        assert(is_string($username));
        assert(is_string($password));
        assert(is_string($charset));
        assert(is_array($optionsRaw));

        /** @var array<string, mixed> $options */
        $options = $optionsRaw;

        return new self(
            driver: $driver,
            database: $database,
            host: $host,
            port: is_int($port) ? $port : (int) $port,
            username: $username,
            password: $password,
            charset: $charset,
            options: $options
        );
    }

    /**
     * Get DSN (Data Source Name) for PDO
     *
     * @return string PDO DSN string
     */
    public function getDsn(): string
    {
        return match ($this->driver) {
            'mysql' => sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $this->host,
                $this->port,
                $this->database,
                $this->charset
            ),
            'pgsql' => sprintf(
                'pgsql:host=%s;port=%d;dbname=%s',
                $this->host,
                $this->port,
                $this->database
            ),
            'sqlite' => sprintf('sqlite:%s', $this->database),
            default => throw new \InvalidArgumentException(
                sprintf('Unsupported driver: %s', $this->driver)
            ),
        };
    }

    /**
     * Get default PDO options
     *
     * @return array<int, mixed> PDO options
     */
    public function getDefaultOptions(): array
    {
        return [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::ATTR_STRINGIFY_FETCHES => false,
        ];
    }

    /**
     * Get merged options (defaults + custom)
     *
     * @return array<int|string, mixed> Merged PDO options
     */
    public function getOptions(): array
    {
        return $this->options + $this->getDefaultOptions();
    }

    /**
     * Validate driver is supported
     *
     * @param string $driver Driver name
     * @throws \InvalidArgumentException If driver is not supported
     */
    private function validateDriver(string $driver): void
    {
        $supported = ['mysql', 'pgsql', 'sqlite'];

        if (! in_array($driver, $supported, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unsupported database driver: %s. Supported: %s',
                    $driver,
                    implode(', ', $supported)
                )
            );
        }
    }

    /**
     * Validate database name/path is provided
     *
     * @param string $database Database name or path
     * @throws \InvalidArgumentException If database is empty
     */
    private function validateDatabase(string $database): void
    {
        if ($database === '') {
            throw new \InvalidArgumentException('Database name or path is required');
        }
    }

    /**
     * Validate host is provided
     *
     * @param string $host Database host
     * @throws \InvalidArgumentException If host is empty
     */
    private function validateHost(string $host): void
    {
        if ($host === '') {
            throw new \InvalidArgumentException('Database host is required');
        }
    }

    /**
     * Validate port is in valid range
     *
     * @param int $port Database port
     * @throws \InvalidArgumentException If port is invalid
     */
    private function validatePort(int $port): void
    {
        if ($port < 1 || $port > 65535) {
            throw new \InvalidArgumentException(
                sprintf('Database port must be between 1 and 65535, got %d', $port)
            );
        }
    }
}
