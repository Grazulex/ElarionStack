<?php

declare(strict_types=1);

namespace Elarion\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Callable Middleware Adapter
 *
 * Wraps callable as PSR-15 MiddlewareInterface.
 * Allows using closures and invokable classes as middlewares.
 */
final class CallableMiddleware implements MiddlewareInterface
{
    /**
     * Create adapter for callable
     *
     * @param callable $callable Callable that should return ResponseInterface
     */
    public function __construct(
        private readonly mixed $callable
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $result = ($this->callable)($request, $handler);

        if (! $result instanceof ResponseInterface) {
            throw new \RuntimeException(
                sprintf(
                    'Callable middleware must return instance of %s, got %s',
                    ResponseInterface::class,
                    get_debug_type($result)
                )
            );
        }

        return $result;
    }
}
