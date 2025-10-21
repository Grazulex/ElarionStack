<?php

declare(strict_types=1);

namespace Elarion\Routing\Adapters;

use Elarion\Routing\Contracts\RouteDispatcherInterface;
use Elarion\Routing\Contracts\RouteMatchInterface;
use Elarion\Routing\RouteMatch;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

/**
 * FastRoute dispatcher adapter
 *
 * Adapts RouteDispatcherInterface to nikic/fast-route dispatcher.
 * Following Adapter pattern and SRP.
 */
final class FastRouteDispatcher implements RouteDispatcherInterface
{
    /**
     * FastRoute dispatcher
     */
    private ?Dispatcher $dispatcher = null;

    /**
     * Create dispatcher
     *
     * @param FastRouteCollector $collector Route collector
     */
    public function __construct(
        private readonly FastRouteCollector $collector
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(string $method, string $uri): RouteMatchInterface
    {
        // Build dispatcher lazily
        if ($this->dispatcher === null) {
            $this->dispatcher = $this->buildDispatcher();
        }

        // Dispatch with FastRoute
        $routeInfo = $this->dispatcher->dispatch($method, $uri);

        return $this->createMatch($routeInfo);
    }

    /**
     * Build FastRoute dispatcher
     *
     * @return Dispatcher FastRoute dispatcher
     */
    private function buildDispatcher(): Dispatcher
    {
        return \FastRoute\simpleDispatcher(function (RouteCollector $r) {
            foreach ($this->collector->buildFastRouteData() as $routeData) {
                /** @var string $method */
                $method = $routeData['method'];
                /** @var string $uri */
                $uri = $routeData['uri'];
                /** @var int $routeIndex */
                $routeIndex = $routeData['routeIndex'];

                $r->addRoute($method, $uri, $routeIndex);
            }
        });
    }

    /**
     * Create RouteMatch from FastRoute result
     *
     * @param array<int, mixed> $routeInfo FastRoute dispatch result
     * @return RouteMatchInterface Match result
     */
    private function createMatch(array $routeInfo): RouteMatchInterface
    {
        /** @var int $status */
        $status = $routeInfo[0];

        if ($status === Dispatcher::FOUND) {
            /** @var array{0: int, 1: int, 2: array<string, string>} $routeInfo */
            return $this->createFoundMatch($routeInfo);
        }

        if ($status === Dispatcher::METHOD_NOT_ALLOWED) {
            /** @var array{0: int, 1: array<int, string>} $routeInfo */
            return $this->createMethodNotAllowedMatch($routeInfo);
        }

        return RouteMatch::notFound();
    }

    /**
     * Create found match
     *
     * @param array{0: int, 1: int, 2: array<string, string>} $routeInfo
     * @return RouteMatchInterface Match result
     */
    private function createFoundMatch(array $routeInfo): RouteMatchInterface
    {
        /** @var int $routeIndex */
        $routeIndex = $routeInfo[1];
        /** @var array<string, string> $params */
        $params = $routeInfo[2];

        $routes = $this->collector->getRoutes();
        $route = $routes[$routeIndex] ?? null;

        if ($route === null) {
            return RouteMatch::notFound();
        }

        return RouteMatch::found(
            handler: $route->getHandler(),
            params: $params,
            middleware: $route->getMiddleware(),
            route: $route
        );
    }

    /**
     * Create method not allowed match
     *
     * @param array{0: int, 1: array<int, string>} $routeInfo
     * @return RouteMatchInterface Match result
     */
    private function createMethodNotAllowedMatch(array $routeInfo): RouteMatchInterface
    {
        /** @var array<int, string> $allowedMethods */
        $allowedMethods = $routeInfo[1];

        return RouteMatch::methodNotAllowed($allowedMethods);
    }
}
