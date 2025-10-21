<?php

declare(strict_types=1);

namespace Elarion\Routing;

use Elarion\Container\ServiceProvider;
use Elarion\Routing\Adapters\FastRouteCollector;
use Elarion\Routing\Adapters\FastRouteDispatcher;
use Elarion\Routing\Contracts\RouteCollectorInterface;
use Elarion\Routing\Contracts\RouteDispatcherInterface;

/**
 * Routing service provider
 *
 * Registers routing services in the container.
 */
final class RoutingServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register(): void
    {
        // Register route collector as singleton
        $this->container->singleton(RouteCollectorInterface::class, FastRouteCollector::class);

        // Register route dispatcher - factory binding
        $this->container->factory(RouteDispatcherInterface::class, function ($resolver) {
            /** @var FastRouteCollector $collector */
            $collector = $this->container->get(RouteCollectorInterface::class);

            return new FastRouteDispatcher($collector);
        }, true); // singleton

        // Register router as singleton - factory binding
        $this->container->factory(Router::class, function ($resolver) {
            /** @var RouteCollectorInterface $collector */
            $collector = $this->container->get(RouteCollectorInterface::class);

            /** @var RouteDispatcherInterface $dispatcher */
            $dispatcher = $this->container->get(RouteDispatcherInterface::class);

            return new Router($collector, $dispatcher);
        }, true); // singleton

        // Alias for easier access
        $this->container->alias('router', Router::class);
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void
    {
        // Routes will be loaded by the application
        // after all providers are booted
    }
}
