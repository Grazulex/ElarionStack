<?php

declare(strict_types=1);

namespace Elarion\Routing\Contracts;

/**
 * Route collector interface
 *
 * Following ISP - defines contract for route registration.
 * Responsible only for collecting routes, not dispatching them.
 */
interface RouteCollectorInterface
{
    /**
     * Register GET route
     *
     * @param string $uri URI pattern
     * @param callable|array{0: class-string, 1: string} $handler Route handler
     * @return RouteInterface Registered route
     */
    public function get(string $uri, callable|array $handler): RouteInterface;

    /**
     * Register POST route
     *
     * @param string $uri URI pattern
     * @param callable|array{0: class-string, 1: string} $handler Route handler
     * @return RouteInterface Registered route
     */
    public function post(string $uri, callable|array $handler): RouteInterface;

    /**
     * Register PUT route
     *
     * @param string $uri URI pattern
     * @param callable|array{0: class-string, 1: string} $handler Route handler
     * @return RouteInterface Registered route
     */
    public function put(string $uri, callable|array $handler): RouteInterface;

    /**
     * Register PATCH route
     *
     * @param string $uri URI pattern
     * @param callable|array{0: class-string, 1: string} $handler Route handler
     * @return RouteInterface Registered route
     */
    public function patch(string $uri, callable|array $handler): RouteInterface;

    /**
     * Register DELETE route
     *
     * @param string $uri URI pattern
     * @param callable|array{0: class-string, 1: string} $handler Route handler
     * @return RouteInterface Registered route
     */
    public function delete(string $uri, callable|array $handler): RouteInterface;

    /**
     * Register OPTIONS route
     *
     * @param string $uri URI pattern
     * @param callable|array{0: class-string, 1: string} $handler Route handler
     * @return RouteInterface Registered route
     */
    public function options(string $uri, callable|array $handler): RouteInterface;

    /**
     * Register route for any HTTP method
     *
     * @param array<int, string> $methods HTTP methods
     * @param string $uri URI pattern
     * @param callable|array{0: class-string, 1: string} $handler Route handler
     * @return RouteInterface Registered route
     */
    public function match(array $methods, string $uri, callable|array $handler): RouteInterface;

    /**
     * Register route for all HTTP methods
     *
     * @param string $uri URI pattern
     * @param callable|array{0: class-string, 1: string} $handler Route handler
     * @return RouteInterface Registered route
     */
    public function any(string $uri, callable|array $handler): RouteInterface;

    /**
     * Register route with specific method
     *
     * @param string $method HTTP method
     * @param string $uri URI pattern
     * @param callable|array{0: class-string, 1: string} $handler Route handler
     * @return RouteInterface Registered route
     */
    public function addRoute(string $method, string $uri, callable|array $handler): RouteInterface;

    /**
     * Create route group with shared attributes
     *
     * @param array{prefix?: string, middleware?: array<int, string|callable>, namespace?: string} $attributes Group attributes
     * @param callable $callback Callback to register routes in group
     * @return void
     */
    public function group(array $attributes, callable $callback): void;

    /**
     * Get all registered routes
     *
     * @return array<int, RouteInterface> All routes
     */
    public function getRoutes(): array;

    /**
     * Find route by name
     *
     * @param string $name Route name
     * @return RouteInterface|null Found route or null
     */
    public function getRouteByName(string $name): ?RouteInterface;
}
