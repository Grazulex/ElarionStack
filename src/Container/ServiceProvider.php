<?php

declare(strict_types=1);

namespace Elarion\Container;

/**
 * Abstract Service Provider base class
 *
 * Service Providers are the central place for configuring container bindings.
 * They allow you to group related bindings together.
 *
 * Following the Template Method pattern:
 * - register() is called first to register bindings
 * - boot() is called after all providers are registered
 *
 * Example:
 * ```
 * class DatabaseServiceProvider extends ServiceProvider
 * {
 *     public function register(): void
 *     {
 *         $this->container->singleton(Connection::class);
 *     }
 *
 *     public function boot(): void
 *     {
 *         // Connect to database, run migrations, etc.
 *     }
 * }
 * ```
 */
abstract class ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred
     */
    protected bool $defer = false;

    /**
     * Create a new service provider instance
     */
    public function __construct(
        protected readonly Container $container
    ) {
    }

    /**
     * Register bindings in the container
     *
     * This method is called first, before any boot() methods.
     * Use this to register your bindings.
     */
    abstract public function register(): void;

    /**
     * Bootstrap any application services
     *
     * This method is called after all providers have been registered.
     * Use this for any initialization that depends on other services.
     */
    public function boot(): void
    {
        // Optional - override if needed
    }

    /**
     * Get the services provided by this provider
     *
     * If provider is deferred, these are the abstracts it provides.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Determine if the provider is deferred
     */
    public function isDeferred(): bool
    {
        return $this->defer;
    }

    /**
     * Get the container instance
     */
    protected function app(): Container
    {
        return $this->container;
    }
}
