<?php

declare(strict_types=1);

namespace Elarion\Routing;

use Elarion\Routing\Contracts\RouteInterface;

/**
 * Route value object
 *
 * Immutable route representation with fluent API.
 * Following SRP - stores route data only.
 */
class Route implements RouteInterface
{
    /**
     * Create new route
     *
     * @param string $method HTTP method
     * @param string $uri URI pattern
     * @param callable|array{0: class-string, 1: string} $handler Route handler
     * @param array<int, string|callable> $middleware Middleware stack
     * @param string|null $name Route name
     * @param array<string, string> $whereConstraints Parameter constraints
     */
    public function __construct(
        private readonly string $method,
        private readonly string $uri,
        private readonly mixed $handler, // callable|array not supported as property type
        private array $middleware = [],
        private ?string $name = null,
        private array $whereConstraints = []
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * {@inheritdoc}
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * {@inheritdoc}
     */
    public function getHandler(): callable|array
    {
        return $this->handler;
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
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getWhereConstraints(): array
    {
        return $this->whereConstraints;
    }

    /**
     * {@inheritdoc}
     */
    public function middleware(string|callable|array $middleware): self
    {
        $clone = clone $this;

        if (is_array($middleware)) {
            /** @var array<int, string|callable> $middleware */
            $clone->middleware = array_merge($clone->middleware, $middleware);
        } else {
            $clone->middleware[] = $middleware;
        }

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function name(string $name): self
    {
        $clone = clone $this;
        $clone->name = $name;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function where(string|array $parameter, ?string $pattern = null): self
    {
        $clone = clone $this;

        if (is_array($parameter)) {
            $clone->whereConstraints = array_merge($clone->whereConstraints, $parameter);
        } elseif ($pattern !== null) {
            $clone->whereConstraints[$parameter] = $pattern;
        }

        return $clone;
    }
}
