<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use Elarion\Database\DatabaseConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DatabaseConfigTest extends TestCase
{
    #[Test]
    public function creates_mysql_config(): void
    {
        $config = new DatabaseConfig(
            driver: 'mysql',
            database: 'test_db',
            host: 'localhost',
            port: 3306,
            username: 'root',
            password: 'secret'
        );

        $this->assertSame('mysql', $config->driver);
        $this->assertSame('test_db', $config->database);
        $this->assertSame('localhost', $config->host);
        $this->assertSame(3306, $config->port);
    }

    #[Test]
    public function creates_postgresql_config(): void
    {
        $config = new DatabaseConfig(
            driver: 'pgsql',
            database: 'test_db',
            host: 'localhost',
            port: 5432
        );

        $this->assertSame('pgsql', $config->driver);
        $this->assertSame(5432, $config->port);
    }

    #[Test]
    public function creates_sqlite_config(): void
    {
        $config = new DatabaseConfig(
            driver: 'sqlite',
            database: ':memory:'
        );

        $this->assertSame('sqlite', $config->driver);
        $this->assertSame(':memory:', $config->database);
    }

    #[Test]
    public function creates_from_array(): void
    {
        $config = DatabaseConfig::fromArray([
            'driver' => 'mysql',
            'database' => 'test_db',
            'host' => 'localhost',
            'port' => 3306,
            'username' => 'root',
            'password' => 'secret',
            'charset' => 'utf8mb4',
        ]);

        $this->assertSame('mysql', $config->driver);
        $this->assertSame('test_db', $config->database);
    }

    #[Test]
    public function generates_mysql_dsn(): void
    {
        $config = new DatabaseConfig(
            driver: 'mysql',
            database: 'test_db',
            host: 'localhost',
            port: 3306
        );

        $this->assertSame(
            'mysql:host=localhost;port=3306;dbname=test_db;charset=utf8mb4',
            $config->getDsn()
        );
    }

    #[Test]
    public function generates_postgresql_dsn(): void
    {
        $config = new DatabaseConfig(
            driver: 'pgsql',
            database: 'test_db',
            host: 'localhost',
            port: 5432
        );

        $this->assertSame(
            'pgsql:host=localhost;port=5432;dbname=test_db',
            $config->getDsn()
        );
    }

    #[Test]
    public function generates_sqlite_dsn(): void
    {
        $config = new DatabaseConfig(
            driver: 'sqlite',
            database: ':memory:'
        );

        $this->assertSame('sqlite::memory:', $config->getDsn());
    }

    #[Test]
    public function provides_default_pdo_options(): void
    {
        $config = new DatabaseConfig(
            driver: 'sqlite',
            database: ':memory:'
        );

        $options = $config->getDefaultOptions();

        $this->assertSame(\PDO::ERRMODE_EXCEPTION, $options[\PDO::ATTR_ERRMODE]);
        $this->assertSame(\PDO::FETCH_ASSOC, $options[\PDO::ATTR_DEFAULT_FETCH_MODE]);
        $this->assertFalse($options[\PDO::ATTR_EMULATE_PREPARES]);
    }

    #[Test]
    public function merges_custom_options_with_defaults(): void
    {
        $config = new DatabaseConfig(
            driver: 'sqlite',
            database: ':memory:',
            options: [\PDO::ATTR_TIMEOUT => 10]
        );

        $options = $config->getOptions();

        $this->assertSame(10, $options[\PDO::ATTR_TIMEOUT]);
        $this->assertSame(\PDO::ERRMODE_EXCEPTION, $options[\PDO::ATTR_ERRMODE]);
    }

    #[Test]
    public function throws_exception_for_unsupported_driver(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported database driver: oracle');

        new DatabaseConfig(
            driver: 'oracle',
            database: 'test'
        );
    }

    #[Test]
    public function throws_exception_for_empty_database(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Database name or path is required');

        new DatabaseConfig(
            driver: 'mysql',
            database: ''
        );
    }

    #[Test]
    public function throws_exception_for_invalid_port(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Database port must be between 1 and 65535');

        new DatabaseConfig(
            driver: 'mysql',
            database: 'test',
            port: 99999
        );
    }

    #[Test]
    public function is_readonly(): void
    {
        $config = new DatabaseConfig(
            driver: 'sqlite',
            database: ':memory:'
        );

        $this->expectException(\Error::class);
        /** @phpstan-ignore-next-line - Testing readonly behavior */
        $config->driver = 'mysql';
    }
}
