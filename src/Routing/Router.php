<?php

declare(strict_types=1);

namespace Elarion\Routing;

use Elarion\Routing\Adapters\FastRouteCollector;
use Elarion\Routing\Adapters\FastRouteDispatcher;
use Elarion\Routing\Contracts\RouteCollectorInterface;
use Elarion\Routing\Contracts\RouteDispatcherInterface;
use Elarion\Routing\Contracts\RouteInterface;
use Elarion\Routing\Contracts\RouteMatchInterface;

/**
 * Router - Main routing facade
 *
 * Combines route collection and dispatching into single API.
 * Following Facade pattern to provide unified interface.
 */
final class Router implements RouteCollectorInterface, RouteDispatcherInterface
{
    /**
     * Route collector
     */
    private readonly RouteCollectorInterface $collector;

    /**
     * Route dispatcher
     */
    private readonly RouteDispatcherInterface $dispatcher;

    /**
     * Create router instance
     *
     * @param RouteCollectorInterface|null $collector Custom collector
     * @param RouteDispatcherInterface|null $dispatcher Custom dispatcher
     */
    public function __construct(
        ?RouteCollectorInterface $collector = null,
        ?RouteDispatcherInterface $dispatcher = null
    ) {
        // Use FastRoute by default
        if ($collector === null) {
            if (! class_exists(FastRouteCollector::class)) {
                throw new \RuntimeException(
                    'FastRoute collector not available. Install nikic/fast-route or provide custom collector.'
                );
            }

            $collector = new FastRouteCollector();
        }

        $this->collector = $collector;

        // Build dispatcher
        if ($dispatcher === null) {
            if ($collector instanceof FastRouteCollector) {
                $dispatcher = new FastRouteDispatcher($collector);
            } else {
                throw new \RuntimeException(
                    'Cannot auto-create dispatcher for custom collector. Please provide dispatcher.'
                );
            }
        }

        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $uri, callable|array $handler): RouteInterface
    {
        return $this->handleRouteRegistration(
            $this->collector->get($uri, $handler)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function post(string $uri, callable|array $handler): RouteInterface
    {
        return $this->handleRouteRegistration(
            $this->collector->post($uri, $handler)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function put(string $uri, callable|array $handler): RouteInterface
    {
        return $this->handleRouteRegistration(
            $this->collector->put($uri, $handler)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function patch(string $uri, callable|array $handler): RouteInterface
    {
        return $this->handleRouteRegistration(
            $this->collector->patch($uri, $handler)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $uri, callable|array $handler): RouteInterface
    {
        return $this->handleRouteRegistration(
            $this->collector->delete($uri, $handler)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function options(string $uri, callable|array $handler): RouteInterface
    {
        return $this->handleRouteRegistration(
            $this->collector->options($uri, $handler)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function match(array $methods, string $uri, callable|array $handler): RouteInterface
    {
        return $this->handleRouteRegistration(
            $this->collector->match($methods, $uri, $handler)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function any(string $uri, callable|array $handler): RouteInterface
    {
        return $this->handleRouteRegistration(
            $this->collector->any($uri, $handler)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function addRoute(string $method, string $uri, callable|array $handler): RouteInterface
    {
        return $this->handleRouteRegistration(
            $this->collector->addRoute($method, $uri, $handler)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function group(array $attributes, callable $callback): void
    {
        $this->collector->group($attributes, $callback);
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes(): array
    {
        return $this->collector->getRoutes();
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteByName(string $name): ?RouteInterface
    {
        return $this->collector->getRouteByName($name);
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(string $method, string $uri): RouteMatchInterface
    {
        return $this->dispatcher->dispatch($method, $uri);
    }

    /**
     * Generate URL for named route
     *
     * @param string $name Route name
     * @param array<string, string|int> $params Route parameters
     * @return string Generated URL
     * @throws \RuntimeException If route not found
     */
    public function url(string $name, array $params = []): string
    {
        $route = $this->getRouteByName($name);

        if ($route === null) {
            throw new \RuntimeException(
                sprintf('Route [%s] not found', $name)
            );
        }

        $uri = $route->getUri();

        // Replace parameters in URI
        foreach ($params as $key => $value) {
            $uri = str_replace('{' . $key . '}', (string) $value, $uri);
        }

        return $uri;
    }

    /**
     * Handle route registration and name tracking
     *
     * @param RouteInterface $route Registered route
     * @return RouteInterface Route with name tracking
     */
    private function handleRouteRegistration(RouteInterface $route): RouteInterface
    {
        // Return a wrapper that tracks named routes
        $collector = $this->collector;

        return new class ($route, $collector) implements RouteInterface {
            public function __construct(
                private readonly RouteInterface $route,
                private readonly RouteCollectorInterface $collector
            ) {
            }

            public function getMethod(): string
            {
                return $this->route->getMethod();
            }

            public function getUri(): string
            {
                return $this->route->getUri();
            }

            public function getHandler(): callable|array
            {
                return $this->route->getHandler();
            }

            public function getMiddleware(): array
            {
                return $this->route->getMiddleware();
            }

            public function getName(): ?string
            {
                return $this->route->getName();
            }

            public function getWhereConstraints(): array
            {
                return $this->route->getWhereConstraints();
            }

            public function name(string $name): RouteInterface
            {
                $named = $this->route->name($name);

                // Register named route if collector supports it
                if ($this->collector instanceof FastRouteCollector) {
                    $this->collector->registerNamedRoute($name, $named);
                }

                return new self($named, $this->collector);
            }

            public function middleware(string|callable|array $middleware): RouteInterface
            {
                return new self($this->route->middleware($middleware), $this->collector);
            }

            public function where(string|array $parameter, ?string $pattern = null): RouteInterface
            {
                return new self($this->route->where($parameter, $pattern), $this->collector);
            }
        };
    }
}
