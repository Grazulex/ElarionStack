<?php

declare(strict_types=1);

namespace Elarion\Container;

/**
 * Repository for managing service providers
 *
 * Handles registration and booting of service providers.
 * Supports deferred providers for lazy loading.
 *
 * Following SRP: Only responsible for provider lifecycle management
 */
final class ServiceProviderRepository
{
    /**
     * Registered service providers
     *
     * @var array<string, ServiceProvider>
     */
    private array $providers = [];

    /**
     * Booted service providers
     *
     * @var array<string, true>
     */
    private array $booted = [];

    /**
     * Deferred service providers
     *
     * @var array<string, array<int, string>> Maps service to provider class
     */
    private array $deferredServices = [];

    public function __construct(
        private readonly Container $container
    ) {
    }

    /**
     * Register a service provider
     *
     * @param class-string<ServiceProvider>|ServiceProvider $provider
     */
    public function register(string|ServiceProvider $provider): ServiceProvider
    {
        // Create instance if class name provided
        if (is_string($provider)) {
            $provider = new $provider($this->container);
        }

        $providerClass = $provider::class;

        // Already registered?
        if (isset($this->providers[$providerClass])) {
            return $this->providers[$providerClass];
        }

        // Store the provider
        $this->providers[$providerClass] = $provider;

        // If deferred, register its services
        if ($provider->isDeferred()) {
            $this->registerDeferredProvider($provider);
            return $provider;
        }

        // Register immediately if not deferred
        $provider->register();

        return $provider;
    }

    /**
     * Register a deferred provider
     */
    private function registerDeferredProvider(ServiceProvider $provider): void
    {
        $provides = $provider->provides();

        foreach ($provides as $service) {
            $this->deferredServices[$service] ??= [];
            $this->deferredServices[$service][] = $provider::class;
        }
    }

    /**
     * Load a deferred provider for a service
     */
    public function loadDeferred(string $service): void
    {
        if (!isset($this->deferredServices[$service])) {
            return;
        }

        foreach ($this->deferredServices[$service] as $providerClass) {
            if (!isset($this->providers[$providerClass])) {
                continue;
            }

            $provider = $this->providers[$providerClass];

            // Register the provider
            $provider->register();

            // Boot it immediately if we're already booted
            if ($this->isBooted($providerClass)) {
                $provider->boot();
                $this->markAsBooted($provider);
            }
        }

        // Remove from deferred list
        unset($this->deferredServices[$service]);
    }

    /**
     * Boot all registered service providers
     */
    public function boot(): void
    {
        foreach ($this->providers as $provider) {
            $this->bootProvider($provider);
        }
    }

    /**
     * Boot a specific service provider
     */
    public function bootProvider(ServiceProvider $provider): void
    {
        if ($this->isBooted($provider::class)) {
            return;
        }

        $provider->boot();
        $this->markAsBooted($provider);
    }

    /**
     * Mark a provider as booted
     */
    private function markAsBooted(ServiceProvider $provider): void
    {
        $this->booted[$provider::class] = true;
    }

    /**
     * Check if a provider has been booted
     */
    private function isBooted(string $providerClass): bool
    {
        return isset($this->booted[$providerClass]);
    }

    /**
     * Get all registered providers
     *
     * @return array<string, ServiceProvider>
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * Check if a provider is registered
     *
     * @param class-string<ServiceProvider> $providerClass
     */
    public function isRegistered(string $providerClass): bool
    {
        return isset($this->providers[$providerClass]);
    }
}
