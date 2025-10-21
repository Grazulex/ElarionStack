<?php

declare(strict_types=1);

namespace Elarion\Routing\Adapters;

use Elarion\Routing\Contracts\RouteCollectorInterface;
use Elarion\Routing\Contracts\RouteInterface;
use Elarion\Routing\HttpMethod;
use Elarion\Routing\Route;
use Elarion\Routing\RouteAttributeStack;
use Elarion\Routing\RouteGroup;

/**
 * FastRoute collector adapter
 *
 * Adapts RouteCollectorInterface to nikic/fast-route.
 * Following Adapter pattern and SRP.
 */
final class FastRouteCollector implements RouteCollectorInterface
{
    /**
     * All registered routes
     *
     * @var array<int, RouteInterface>
     */
    private array $routes = [];

    /**
     * Named routes map
     *
     * @var array<string, RouteInterface>
     */
    private array $namedRoutes = [];

    /**
     * Route group manager
     */
    private readonly RouteGroup $routeGroup;

    /**
     * Attribute stack
     */
    private readonly RouteAttributeStack $attributeStack;

    public function __construct()
    {
        $this->attributeStack = new RouteAttributeStack();
        $this->routeGroup = new RouteGroup($this, $this->attributeStack);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $uri, callable|array $handler): RouteInterface
    {
        return $this->addRoute(HttpMethod::GET->value, $uri, $handler);
    }

    /**
     * {@inheritdoc}
     */
    public function post(string $uri, callable|array $handler): RouteInterface
    {
        return $this->addRoute(HttpMethod::POST->value, $uri, $handler);
    }

    /**
     * {@inheritdoc}
     */
    public function put(string $uri, callable|array $handler): RouteInterface
    {
        return $this->addRoute(HttpMethod::PUT->value, $uri, $handler);
    }

    /**
     * {@inheritdoc}
     */
    public function patch(string $uri, callable|array $handler): RouteInterface
    {
        return $this->addRoute(HttpMethod::PATCH->value, $uri, $handler);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $uri, callable|array $handler): RouteInterface
    {
        return $this->addRoute(HttpMethod::DELETE->value, $uri, $handler);
    }

    /**
     * {@inheritdoc}
     */
    public function options(string $uri, callable|array $handler): RouteInterface
    {
        return $this->addRoute(HttpMethod::OPTIONS->value, $uri, $handler);
    }

    /**
     * {@inheritdoc}
     */
    public function match(array $methods, string $uri, callable|array $handler): RouteInterface
    {
        // Register route for each method
        $route = null;

        foreach ($methods as $method) {
            $route = $this->addRoute(strtoupper($method), $uri, $handler);
        }

        // Return last route (all share same handler)
        return $route ?? throw new \RuntimeException('At least one method must be specified');
    }

    /**
     * {@inheritdoc}
     */
    public function any(string $uri, callable|array $handler): RouteInterface
    {
        return $this->match(HttpMethod::values(), $uri, $handler);
    }

    /**
     * {@inheritdoc}
     *
     * @param callable|array{0: class-string, 1: string} $handler
     */
    public function addRoute(string $method, string $uri, callable|array $handler): RouteInterface
    {
        // Apply group attributes
        $uri = $this->routeGroup->applyPrefix($uri);
        /** @var callable|array{0: class-string, 1: string} $handler */
        $handler = $this->routeGroup->applyNamespace($handler);
        $middleware = $this->routeGroup->applyMiddleware([]);

        // Create route
        $route = new Route(
            method: $method,
            uri: $uri,
            handler: $handler,
            middleware: $middleware
        );

        // Store route
        $this->routes[] = $route;

        return $route;
    }

    /**
     * {@inheritdoc}
     */
    public function group(array $attributes, callable $callback): void
    {
        $this->routeGroup->execute($attributes, $callback);
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteByName(string $name): ?RouteInterface
    {
        return $this->namedRoutes[$name] ?? null;
    }

    /**
     * Register named route
     *
     * @param string $name Route name
     * @param RouteInterface $route Route instance
     * @return void
     */
    public function registerNamedRoute(string $name, RouteInterface $route): void
    {
        $this->namedRoutes[$name] = $route;
    }

    /**
     * Build FastRoute data structure
     *
     * @return array<int, array<string, mixed>> Route data for FastRoute
     */
    public function buildFastRouteData(): array
    {
        $data = [];

        foreach ($this->routes as $index => $route) {
            $data[] = [
                'method' => $route->getMethod(),
                'uri' => $route->getUri(),
                'routeIndex' => $index,
            ];
        }

        return $data;
    }
}
