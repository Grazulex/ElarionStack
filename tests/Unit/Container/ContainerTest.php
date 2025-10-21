<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Elarion\Container\Container;
use Elarion\Container\Exceptions\NotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Test suite for the PSR-11 Container
 */
final class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function test_container_implements_psr11_interface(): void
    {
        $this->assertInstanceOf(ContainerInterface::class, $this->container);
    }

    public function test_container_binds_itself(): void
    {
        $this->assertTrue($this->container->has(Container::class));
        $this->assertTrue($this->container->has(ContainerInterface::class));

        $this->assertSame($this->container, $this->container->get(Container::class));
        $this->assertSame($this->container, $this->container->get(ContainerInterface::class));
    }

    public function test_can_bind_concrete_class(): void
    {
        $this->container->bind(SimpleClass::class);

        $this->assertTrue($this->container->has(SimpleClass::class));

        $instance = $this->container->get(SimpleClass::class);
        $this->assertInstanceOf(SimpleClass::class, $instance);
    }

    public function test_concrete_binding_creates_new_instance_each_time(): void
    {
        $this->container->bind(SimpleClass::class);

        $instance1 = $this->container->get(SimpleClass::class);
        $instance2 = $this->container->get(SimpleClass::class);

        $this->assertNotSame($instance1, $instance2);
    }

    public function test_singleton_returns_same_instance(): void
    {
        $this->container->singleton(SimpleClass::class);

        $instance1 = $this->container->get(SimpleClass::class);
        $instance2 = $this->container->get(SimpleClass::class);

        $this->assertSame($instance1, $instance2);
    }

    public function test_can_bind_interface_to_implementation(): void
    {
        $this->container->bind(SimpleInterface::class, SimpleImplementation::class);

        $instance = $this->container->get(SimpleInterface::class);
        $this->assertInstanceOf(SimpleImplementation::class, $instance);
    }

    public function test_can_bind_factory(): void
    {
        $this->container->factory(SimpleClass::class, function () {
            return new SimpleClass('from factory');
        });

        $instance = $this->container->get(SimpleClass::class);
        $this->assertInstanceOf(SimpleClass::class, $instance);
        $this->assertSame('from factory', $instance->value);
    }

    public function test_can_register_instance(): void
    {
        $instance = new SimpleClass('existing');
        $this->container->instance(SimpleClass::class, $instance);

        $resolved = $this->container->get(SimpleClass::class);
        $this->assertSame($instance, $resolved);
    }

    public function test_can_create_alias(): void
    {
        $this->container->singleton(SimpleClass::class);
        $this->container->alias('simple', SimpleClass::class);

        $this->assertTrue($this->container->has('simple'));

        $instance1 = $this->container->get('simple');
        $instance2 = $this->container->get(SimpleClass::class);

        $this->assertSame($instance1, $instance2);
    }

    public function test_autowiring_resolves_dependencies(): void
    {
        $instance = $this->container->make(ClassWithDependency::class);

        $this->assertInstanceOf(ClassWithDependency::class, $instance);
        $this->assertInstanceOf(SimpleClass::class, $instance->dependency);
    }

    public function test_autowiring_resolves_recursive_dependencies(): void
    {
        $instance = $this->container->make(ClassWithRecursiveDependency::class);

        $this->assertInstanceOf(ClassWithRecursiveDependency::class, $instance);
        $this->assertInstanceOf(ClassWithDependency::class, $instance->dependency);
        $this->assertInstanceOf(SimpleClass::class, $instance->dependency->dependency);
    }

    public function test_throws_not_found_exception_for_missing_binding(): void
    {
        $this->expectException(NotFoundException::class);
        $this->container->get('NonExistentClass');
    }

    public function test_can_unbind(): void
    {
        $this->container->bind(SimpleClass::class);
        $this->assertTrue($this->container->has(SimpleClass::class));

        $this->container->unbind(SimpleClass::class);
        $this->assertFalse($this->container->bound(SimpleClass::class));
    }

    public function test_flush_clears_all_bindings(): void
    {
        $this->container->bind(SimpleClass::class);
        $this->container->singleton('test', SimpleClass::class);

        $this->container->flush();

        $this->assertFalse($this->container->bound(SimpleClass::class));
        $this->assertFalse($this->container->bound('test'));

        // Container itself should still be bound
        $this->assertTrue($this->container->has(Container::class));
    }
}

// Test fixtures

class SimpleClass
{
    public function __construct(public string $value = 'default')
    {
    }
}

interface SimpleInterface
{
}

class SimpleImplementation implements SimpleInterface
{
}

class ClassWithDependency
{
    public function __construct(public SimpleClass $dependency)
    {
    }
}

class ClassWithRecursiveDependency
{
    public function __construct(public ClassWithDependency $dependency)
    {
    }
}
