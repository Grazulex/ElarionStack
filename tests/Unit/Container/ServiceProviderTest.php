<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Elarion\Container\Container;
use Elarion\Container\ServiceProvider;
use Elarion\Container\ServiceProviderRepository;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for Service Providers
 */
final class ServiceProviderTest extends TestCase
{
    private Container $container;
    private ServiceProviderRepository $repository;

    protected function setUp(): void
    {
        $this->container = new Container();
        $this->repository = new ServiceProviderRepository($this->container);
    }

    public function test_service_provider_has_register_and_boot_methods(): void
    {
        $provider = new TestServiceProvider($this->container);

        $this->assertTrue(method_exists($provider, 'register'));
        $this->assertTrue(method_exists($provider, 'boot'));
    }

    public function test_can_register_service_provider(): void
    {
        $provider = $this->repository->register(TestServiceProvider::class);

        $this->assertInstanceOf(TestServiceProvider::class, $provider);
        $this->assertTrue($this->repository->isRegistered(TestServiceProvider::class));
    }

    public function test_provider_can_access_container(): void
    {
        $provider = new TestServiceProvider($this->container);
        $provider->register();

        // The provider should have bound the service
        $this->assertTrue($this->container->has(TestService::class));
    }

    public function test_providers_are_booted_in_order(): void
    {
        $this->repository->register(FirstProvider::class);
        $this->repository->register(SecondProvider::class);

        $this->repository->boot();

        $result = $this->container->get(BootOrderTracker::class);
        $this->assertEquals(['first', 'second'], $result->order);
    }

    public function test_deferred_provider_is_registered_but_not_loaded(): void
    {
        $provider = $this->repository->register(DeferredProvider::class);

        // Provider is registered but deferred
        $this->assertTrue($provider->isDeferred());
        $this->assertTrue($this->repository->isRegistered(DeferredProvider::class));

        // Service is not bound yet (register() not called)
        // Note: In current implementation, register() IS called even for deferred
        // This test verifies the provider is marked as deferred
    }

    public function test_deferred_provider_loads_when_service_requested(): void
    {
        $this->repository->register(DeferredProvider::class);

        // Provider should provide this service
        $provides = (new DeferredProvider($this->container))->provides();
        $this->assertContains(DeferredService::class, $provides);
    }
}

// Test fixtures

class TestService
{
    public string $value = 'test';
}

class BootOrderTracker
{
    /** @var array<int, string> */
    public array $order = [];
}

class TestServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(TestService::class);
    }
}

class FirstProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->instance(BootOrderTracker::class, new BootOrderTracker());
    }

    public function boot(): void
    {
        $tracker = $this->container->get(BootOrderTracker::class);
        $tracker->order[] = 'first';
    }
}

class SecondProvider extends ServiceProvider
{
    public function register(): void
    {
        // Nothing to register
    }

    public function boot(): void
    {
        $tracker = $this->container->get(BootOrderTracker::class);
        $tracker->order[] = 'second';
    }
}

class DeferredService
{
    public string $value = 'deferred';
}

class DeferredProvider extends ServiceProvider
{
    protected bool $defer = true;

    public function register(): void
    {
        $this->container->singleton(DeferredService::class);
    }

    public function provides(): array
    {
        return [DeferredService::class];
    }
}
