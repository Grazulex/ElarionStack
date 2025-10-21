<?php

declare(strict_types=1);

namespace Elarion\Routing;

use Elarion\Routing\Contracts\RouteInterface;
use Elarion\Routing\Contracts\RouteMatchInterface;

/**
 * Route match result value object
 *
 * Immutable representation of route matching result.
 * Following SRP - only stores match data.
 */
final readonly class RouteMatch implements RouteMatchInterface
{
    /**
     * Create route match result
     *
     * @param int $status Match status (FOUND, NOT_FOUND, METHOD_NOT_ALLOWED)
     * @param callable|array{0: class-string, 1: string}|null $handler Route handler if found
     * @param array<string, string> $params Route parameters
     * @param array<int, string|callable> $middleware Middleware stack
     * @param array<int, string> $allowedMethods Allowed methods for 405
     * @param RouteInterface|null $route Matched route instance
     */
    public function __construct(
        private int $status,
        private mixed $handler = null, // callable|array|null not supported as property type
        private array $params = [],
        private array $middleware = [],
        private array $allowedMethods = [],
        private ?RouteInterface $route = null
    ) {
    }

    /**
     * Create successful match
     *
     * @param callable|array{0: class-string, 1: string} $handler Route handler
     * @param array<string, string> $params Route parameters
     * @param array<int, string|callable> $middleware Middleware stack
     * @param RouteInterface|null $route Matched route
     * @return self Match result
     */
    public static function found(
        callable|array $handler,
        array $params = [],
        array $middleware = [],
        ?RouteInterface $route = null
    ): self {
        return new self(
            status: self::FOUND,
            handler: $handler,
            params: $params,
            middleware: $middleware,
            route: $route
        );
    }

    /**
     * Create not found result
     *
     * @return self Match result
     */
    public static function notFound(): self
    {
        return new self(status: self::NOT_FOUND);
    }

    /**
     * Create method not allowed result
     *
     * @param array<int, string> $allowedMethods Allowed HTTP methods
     * @return self Match result
     */
    public static function methodNotAllowed(array $allowedMethods): self
    {
        return new self(
            status: self::METHOD_NOT_ALLOWED,
            allowedMethods: $allowedMethods
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * {@inheritdoc}
     */
    public function isFound(): bool
    {
        return $this->status === self::FOUND;
    }

    /**
     * {@inheritdoc}
     */
    public function isMethodNotAllowed(): bool
    {
        return $this->status === self::METHOD_NOT_ALLOWED;
    }

    /**
     * {@inheritdoc}
     *
     * @return callable|array{0: class-string, 1: string}|null
     */
    public function getHandler(): callable|array|null
    {
        return $this->handler;
    }

    /**
     * {@inheritdoc}
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * {@inheritdoc}
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoute(): ?RouteInterface
    {
        return $this->route;
    }
}
