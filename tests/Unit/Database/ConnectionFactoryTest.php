<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use Elarion\Database\ConnectionFactory;
use Elarion\Database\DatabaseConfig;
use Elarion\Database\Exceptions\ConnectionException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConnectionFactoryTest extends TestCase
{
    #[Test]
    public function creates_sqlite_connection(): void
    {
        $config = new DatabaseConfig(
            driver: 'sqlite',
            database: ':memory:'
        );

        $factory = new ConnectionFactory();
        $pdo = $factory->create($config);

        $this->assertInstanceOf(\PDO::class, $pdo);
        $this->assertSame('sqlite', $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME));
    }

    #[Test]
    public function applies_default_pdo_options(): void
    {
        $config = new DatabaseConfig(
            driver: 'sqlite',
            database: ':memory:'
        );

        $factory = new ConnectionFactory();
        $pdo = $factory->create($config);

        $this->assertSame(
            \PDO::ERRMODE_EXCEPTION,
            $pdo->getAttribute(\PDO::ATTR_ERRMODE)
        );
        $this->assertSame(
            \PDO::FETCH_ASSOC,
            $pdo->getAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE)
        );
    }

    #[Test]
    public function applies_custom_options(): void
    {
        $config = new DatabaseConfig(
            driver: 'sqlite',
            database: ':memory:',
            options: [\PDO::ATTR_CASE => \PDO::CASE_UPPER]
        );

        $factory = new ConnectionFactory();
        $pdo = $factory->create($config);

        $this->assertSame(\PDO::CASE_UPPER, $pdo->getAttribute(\PDO::ATTR_CASE));
    }

    #[Test]
    public function throws_exception_for_invalid_sqlite_path(): void
    {
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Failed to connect to SQLite database');

        $config = new DatabaseConfig(
            driver: 'sqlite',
            database: '/nonexistent/path/database.sqlite'
        );

        $factory = new ConnectionFactory();
        $factory->create($config);
    }

    #[Test]
    public function throws_exception_for_invalid_mysql_credentials(): void
    {
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Failed to connect to mysql database');

        $config = new DatabaseConfig(
            driver: 'mysql',
            database: 'nonexistent_db',
            host: 'localhost',
            username: 'invalid_user',
            password: 'wrong_password'
        );

        $factory = new ConnectionFactory();
        $factory->create($config);
    }

    #[Test]
    public function can_execute_queries_on_created_connection(): void
    {
        $config = new DatabaseConfig(
            driver: 'sqlite',
            database: ':memory:'
        );

        $factory = new ConnectionFactory();
        $pdo = $factory->create($config);

        // Create table
        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)');

        // Insert data
        $stmt = $pdo->prepare('INSERT INTO users (name) VALUES (:name)');
        $stmt->execute(['name' => 'John']);

        // Query data
        $stmt = $pdo->query('SELECT * FROM users');
        $users = $stmt->fetchAll();

        $this->assertCount(1, $users);
        $this->assertSame('John', $users[0]['name']);
    }
}
