<?php

declare(strict_types=1);

namespace Elarion\Routing\Contracts;

/**
 * Route interface
 *
 * Following ISP - defines contract for route representation.
 * Routes are immutable once created for thread-safety.
 */
interface RouteInterface
{
    /**
     * Get HTTP method
     *
     * @return string HTTP method (GET, POST, PUT, PATCH, DELETE, etc.)
     */
    public function getMethod(): string;

    /**
     * Get route URI pattern
     *
     * @return string URI pattern (e.g., '/users/{id}')
     */
    public function getUri(): string;

    /**
     * Get route handler
     *
     * @return callable|array{0: class-string, 1: string} Handler (callable or [Controller::class, 'method'])
     */
    public function getHandler(): callable|array;

    /**
     * Get route middleware stack
     *
     * @return array<int, string|callable> Middleware to apply to this route
     */
    public function getMiddleware(): array;

    /**
     * Get route name (if assigned)
     *
     * @return string|null Named route identifier
     */
    public function getName(): ?string;

    /**
     * Assign middleware to route
     *
     * @param string|callable|array<int, string|callable> $middleware
     * @return self New instance with middleware
     */
    public function middleware(string|callable|array $middleware): self;

    /**
     * Assign name to route
     *
     * @param string $name Route name for URL generation
     * @return self New instance with name
     */
    public function name(string $name): self;

    /**
     * Get route where constraints
     *
     * @return array<string, string> Parameter regex constraints
     */
    public function getWhereConstraints(): array;

    /**
     * Add parameter constraints
     *
     * @param string|array<string, string> $parameter Parameter name or array of constraints
     * @param string|null $pattern Regex pattern if single parameter
     * @return self New instance with constraints
     */
    public function where(string|array $parameter, ?string $pattern = null): self;
}
