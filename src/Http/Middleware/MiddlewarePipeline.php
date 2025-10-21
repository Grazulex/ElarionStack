<?php

declare(strict_types=1);

namespace Elarion\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 Middleware Pipeline
 *
 * Executes middlewares in FIFO order around request handling.
 * Supports short-circuit when middleware returns response early.
 */
final class MiddlewarePipeline implements RequestHandlerInterface
{
    /** @var array<MiddlewareInterface> */
    private array $middlewares = [];

    private ?RequestHandlerInterface $fallbackHandler = null;

    /**
     * Add middleware to the pipeline
     *
     * Middlewares are executed in the order they are added (FIFO).
     *
     * @param MiddlewareInterface $middleware Middleware to add
     * @return self Fluent interface
     */
    public function pipe(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * Set fallback handler for final request processing
     *
     * @param RequestHandlerInterface $handler Handler to use when no middleware returns response
     * @return self Fluent interface
     */
    public function setFallbackHandler(RequestHandlerInterface $handler): self
    {
        $this->fallbackHandler = $handler;

        return $this;
    }

    /**
     * Handle request through middleware pipeline
     *
     * Executes middlewares in FIFO order. Each middleware can:
     * - Modify the request and pass to next handler
     * - Short-circuit by returning response immediately
     * - Modify the response from next handler
     *
     * @param ServerRequestInterface $request Incoming request
     * @return ResponseInterface Response from middleware chain or fallback handler
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // If no middlewares, use fallback handler
        if (empty($this->middlewares)) {
            if ($this->fallbackHandler === null) {
                throw new \RuntimeException(
                    'Pipeline has no middlewares and no fallback handler set'
                );
            }

            return $this->fallbackHandler->handle($request);
        }

        // Create handler chain from middlewares
        $handler = $this->createHandler(0);

        return $handler->handle($request);
    }

    /**
     * Create request handler for middleware at given index
     *
     * Recursively creates chain of handlers where each handler:
     * 1. Processes current middleware
     * 2. Passes to next handler in chain
     *
     * @param int $index Current middleware index
     * @return RequestHandlerInterface Handler for this middleware
     */
    private function createHandler(int $index): RequestHandlerInterface
    {
        // If we've processed all middlewares, use fallback handler
        if (! isset($this->middlewares[$index])) {
            if ($this->fallbackHandler === null) {
                throw new \RuntimeException('No fallback handler set for pipeline');
            }

            return $this->fallbackHandler;
        }

        return new class ($this->middlewares[$index], $this->createHandler($index + 1)) implements RequestHandlerInterface {
            public function __construct(
                private MiddlewareInterface $middleware,
                private RequestHandlerInterface $next
            ) {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                // Execute middleware with next handler
                // Middleware can short-circuit by returning response without calling $next
                return $this->middleware->process($request, $this->next);
            }
        };
    }
}
