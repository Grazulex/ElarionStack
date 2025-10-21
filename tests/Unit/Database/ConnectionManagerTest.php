<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use Elarion\Database\ConnectionManager;
use Elarion\Database\DatabaseConfig;
use Elarion\Database\Exceptions\ConfigurationException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConnectionManagerTest extends TestCase
{
    #[Test]
    public function creates_manager_with_configurations(): void
    {
        $manager = new ConnectionManager([
            'default' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
        ]);

        $this->assertTrue($manager->hasConnection('default'));
    }

    #[Test]
    public function gets_default_connection(): void
    {
        $manager = new ConnectionManager([
            'default' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
        ]);

        $pdo = $manager->connection();

        $this->assertInstanceOf(\PDO::class, $pdo);
    }

    #[Test]
    public function gets_named_connection(): void
    {
        $manager = new ConnectionManager([
            'default' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
            'analytics' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
        ]);

        $pdo = $manager->connection('analytics');

        $this->assertInstanceOf(\PDO::class, $pdo);
    }

    #[Test]
    public function lazy_loads_connections(): void
    {
        $manager = new ConnectionManager([
            'default' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
        ]);

        // Connection not created yet
        $this->assertFalse($manager->isConnected('default'));

        // Access connection
        $manager->connection('default');

        // Now connected
        $this->assertTrue($manager->isConnected('default'));
    }

    #[Test]
    public function returns_cached_connection_on_subsequent_calls(): void
    {
        $manager = new ConnectionManager([
            'default' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
        ]);

        $pdo1 = $manager->connection('default');
        $pdo2 = $manager->connection('default');

        $this->assertSame($pdo1, $pdo2);
    }

    #[Test]
    public function adds_connection_at_runtime(): void
    {
        $manager = new ConnectionManager();

        $config = new DatabaseConfig(
            driver: 'sqlite',
            database: ':memory:'
        );

        $manager->addConnection('test', $config);

        $this->assertTrue($manager->hasConnection('test'));
        $this->assertInstanceOf(\PDO::class, $manager->connection('test'));
    }

    #[Test]
    public function sets_default_connection(): void
    {
        $manager = new ConnectionManager([
            'primary' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
            'secondary' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
        ]);

        $manager->setDefaultConnection('secondary');

        $this->assertSame('secondary', $manager->getDefaultConnection());
    }

    #[Test]
    public function throws_exception_for_missing_connection(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Database connection [nonexistent] is not configured');

        $manager = new ConnectionManager();
        $manager->connection('nonexistent');
    }

    #[Test]
    public function throws_exception_setting_nonexistent_default(): void
    {
        $this->expectException(ConfigurationException::class);

        $manager = new ConnectionManager();
        $manager->setDefaultConnection('nonexistent');
    }

    #[Test]
    public function disconnects_specific_connection(): void
    {
        $manager = new ConnectionManager([
            'default' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
        ]);

        $manager->connection('default');
        $this->assertTrue($manager->isConnected('default'));

        $manager->disconnect('default');
        $this->assertFalse($manager->isConnected('default'));
    }

    #[Test]
    public function disconnects_all_connections(): void
    {
        $manager = new ConnectionManager([
            'conn1' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
            'conn2' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
        ]);

        $manager->connection('conn1');
        $manager->connection('conn2');

        $manager->disconnect();

        $this->assertFalse($manager->isConnected('conn1'));
        $this->assertFalse($manager->isConnected('conn2'));
    }

    #[Test]
    public function reconnects_connection(): void
    {
        $manager = new ConnectionManager([
            'default' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
        ]);

        $pdo1 = $manager->connection('default');
        $pdo2 = $manager->reconnect('default');

        // Should be different instances
        $this->assertNotSame($pdo1, $pdo2);
    }

    #[Test]
    public function lists_configured_connections(): void
    {
        $manager = new ConnectionManager([
            'conn1' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
            'conn2' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
        ]);

        $names = $manager->getConnectionNames();

        $this->assertCount(2, $names);
        $this->assertContains('conn1', $names);
        $this->assertContains('conn2', $names);
    }

    #[Test]
    public function multiple_connections_are_independent(): void
    {
        $manager = new ConnectionManager([
            'conn1' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
            'conn2' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
        ]);

        $pdo1 = $manager->connection('conn1');
        $pdo2 = $manager->connection('conn2');

        // Create table in conn1
        $pdo1->exec('CREATE TABLE users (id INTEGER PRIMARY KEY)');

        // Should not exist in conn2
        $this->expectException(\PDOException::class);
        $pdo2->query('SELECT * FROM users');
    }

    #[Test]
    public function fluent_interface_works(): void
    {
        $config = new DatabaseConfig(
            driver: 'sqlite',
            database: ':memory:'
        );

        $manager = new ConnectionManager();
        $result = $manager
            ->addConnection('test', $config)
            ->setDefaultConnection('test')
            ->disconnect('test');

        $this->assertInstanceOf(ConnectionManager::class, $result);
    }
}
