<?php

declare(strict_types=1);

namespace Elarion\OpenAPI\Generator;

use Elarion\Routing\Route;
use Elarion\Routing\Router;

/**
 * Route Scanner
 *
 * Scans registered routes to extract API endpoint information.
 */
final class RouteScanner
{
    public function __construct(
        private Router $router
    ) {
    }

    /**
     * Scan all registered routes
     *
     * @return array<array{method: string, path: string, handler: callable|array<mixed>}>
     */
    public function scan(): array
    {
        $routes = [];

        // Get all routes from the router
        $allRoutes = $this->router->getRoutes();

        foreach ($allRoutes as $route) {
            $routes[] = [
                'method' => $this->extractMethod($route),
                'path' => $route->getUri(),
                'handler' => $route->getHandler(),
                'name' => $route->getName(),
            ];
        }

        return $routes;
    }

    /**
     * Extract HTTP method from route
     */
    private function extractMethod(Route $route): string
    {
        return strtolower($route->getMethod());
    }

    /**
     * Extract path parameters from route path
     *
     * @return array<string>
     */
    public function extractPathParameters(string $path): array
    {
        preg_match_all('/\{([^}]+)\}/', $path, $matches);

        return $matches[1] ?? [];
    }
}
