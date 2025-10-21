<?php

declare(strict_types=1);

namespace Elarion\Routing\Contracts;

/**
 * Route match result interface
 *
 * Represents the result of route matching operation.
 */
interface RouteMatchInterface
{
    /**
     * Match status constants
     */
    public const int FOUND = 1;
    public const int NOT_FOUND = 0;
    public const int METHOD_NOT_ALLOWED = 2;

    /**
     * Get match status
     *
     * @return int One of FOUND, NOT_FOUND, METHOD_NOT_ALLOWED
     */
    public function getStatus(): int;

    /**
     * Check if route was found
     *
     * @return bool True if route matched
     */
    public function isFound(): bool;

    /**
     * Check if method is not allowed
     *
     * @return bool True if URI matched but method didn't
     */
    public function isMethodNotAllowed(): bool;

    /**
     * Get matched route handler
     *
     * @return callable|array{0: class-string, 1: string}|null Handler if found, null otherwise
     */
    public function getHandler(): callable|array|null;

    /**
     * Get route parameters
     *
     * @return array<string, string> Extracted parameters (e.g., ['id' => '123'])
     */
    public function getParams(): array;

    /**
     * Get middleware stack for matched route
     *
     * @return array<int, string|callable> Middleware to apply
     */
    public function getMiddleware(): array;

    /**
     * Get allowed HTTP methods (for 405 responses)
     *
     * @return array<int, string> Allowed methods if METHOD_NOT_ALLOWED
     */
    public function getAllowedMethods(): array;

    /**
     * Get matched route (if available)
     *
     * @return RouteInterface|null Matched route instance
     */
    public function getRoute(): ?RouteInterface;
}
