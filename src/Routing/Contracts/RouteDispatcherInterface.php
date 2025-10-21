<?php

declare(strict_types=1);

namespace Elarion\Routing\Contracts;

/**
 * Route dispatcher interface
 *
 * Following ISP - defines contract for route matching/dispatching.
 * Responsible only for matching requests to routes.
 */
interface RouteDispatcherInterface
{
    /**
     * Dispatch HTTP request to matching route
     *
     * @param string $method HTTP method
     * @param string $uri Request URI
     * @return RouteMatchInterface Match result
     */
    public function dispatch(string $method, string $uri): RouteMatchInterface;
}
