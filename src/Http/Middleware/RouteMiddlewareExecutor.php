<?php

declare(strict_types=1);

namespace Elarion\Http\Middleware;

use Elarion\Routing\Contracts\RouteInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Route Middleware Executor
 *
 * Executes route handler through its middleware pipeline.
 * Provides integration point between Router and MiddlewarePipeline.
 */
final class RouteMiddlewareExecutor
{
    /**
     * Create executor instance
     *
     * @param ContainerInterface|null $container DI container for resolving middlewares
     */
    public function __construct(
        private readonly ?ContainerInterface $container = null
    ) {
    }

    /**
     * Execute route with its middlewares
     *
     * @param RouteInterface $route Route to execute
     * @param ServerRequestInterface $request Incoming request
     * @param array<string, mixed> $routeParams Route parameters from URL
     * @return ResponseInterface Response from route handler
     */
    public function execute(
        RouteInterface $route,
        ServerRequestInterface $request,
        array $routeParams = []
    ): ResponseInterface {
        // Add route params to request as attributes
        foreach ($routeParams as $key => $value) {
            $request = $request->withAttribute($key, $value);
        }

        // Create pipeline with route middlewares
        $pipeline = new MiddlewarePipeline();

        foreach ($route->getMiddleware() as $middleware) {
            $pipeline->pipe($this->resolveMiddleware($middleware));
        }

        // Set route handler as fallback
        $handler = $this->createRouteHandler($route);
        $pipeline->setFallbackHandler($handler);

        return $pipeline->handle($request);
    }

    /**
     * Resolve middleware instance
     *
     * @param string|callable|MiddlewareInterface $middleware Middleware to resolve
     * @return MiddlewareInterface Resolved middleware
     */
    private function resolveMiddleware(string|callable|MiddlewareInterface $middleware): MiddlewareInterface
    {
        // Already a middleware instance
        if ($middleware instanceof MiddlewareInterface) {
            return $middleware;
        }

        // Callable middleware - wrap in adapter
        if (is_callable($middleware)) {
            return new CallableMiddleware($middleware);
        }

        // String - resolve from container or instantiate
        if ($this->container !== null && $this->container->has($middleware)) {
            $resolved = $this->container->get($middleware);

            if (! $resolved instanceof MiddlewareInterface) {
                throw new \RuntimeException(
                    sprintf(
                        'Middleware [%s] resolved from container must implement %s',
                        $middleware,
                        MiddlewareInterface::class
                    )
                );
            }

            return $resolved;
        }

        // Try to instantiate directly
        if (class_exists($middleware)) {
            try {
                $instance = new $middleware();

                if (! $instance instanceof MiddlewareInterface) {
                    throw new \RuntimeException(
                        sprintf(
                            'Middleware class [%s] must implement %s',
                            $middleware,
                            MiddlewareInterface::class
                        )
                    );
                }

                return $instance;
            } catch (\Throwable $e) {
                throw new \RuntimeException(
                    sprintf('Failed to instantiate middleware [%s]: %s', $middleware, $e->getMessage()),
                    0,
                    $e
                );
            }
        }

        throw new \RuntimeException(
            sprintf('Unable to resolve middleware: %s', $middleware)
        );
    }

    /**
     * Create request handler from route handler
     *
     * @param RouteInterface $route Route with handler
     * @return RequestHandlerInterface Handler that executes route
     */
    private function createRouteHandler(RouteInterface $route): RequestHandlerInterface
    {
        $handler = $route->getHandler();
        $container = $this->container;

        return new class ($handler, $container) implements RequestHandlerInterface {
            /**
             * @param callable|array{0: class-string|object, 1: string} $handler
             */
            public function __construct(
                private readonly mixed $handler,
                private readonly ?ContainerInterface $container
            ) {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                // Resolve controller from container if needed
                if (is_array($this->handler)) {
                    [$class, $method] = $this->handler;

                    if (is_string($class)) {
                        $controller = $this->resolveController($class);
                        /** @var callable $callable */
                        $callable = [$controller, $method];
                        $result = $callable($request);
                    } else {
                        // Already an instance
                        /** @var callable $callable */
                        $callable = [$class, $method];
                        $result = $callable($request);
                    }
                } else {
                    // Callable handler
                    /** @var callable $callable */
                    $callable = $this->handler;
                    $result = $callable($request);
                }

                // Ensure we return a Response
                if (! $result instanceof ResponseInterface) {
                    throw new \RuntimeException(
                        sprintf(
                            'Route handler must return instance of %s, got %s',
                            ResponseInterface::class,
                            get_debug_type($result)
                        )
                    );
                }

                return $result;
            }

            /**
             * Resolve controller from container or instantiate
             *
             * @param string $class Controller class
             * @return object Controller instance
             */
            private function resolveController(string $class): object
            {
                if ($this->container !== null && $this->container->has($class)) {
                    $resolved = $this->container->get($class);
                    assert(is_object($resolved));

                    return $resolved;
                }

                if (! class_exists($class)) {
                    throw new \RuntimeException(
                        sprintf('Controller class [%s] does not exist', $class)
                    );
                }

                try {
                    /** @var object $instance */
                    $instance = new $class();

                    return $instance;
                } catch (\Throwable $e) {
                    throw new \RuntimeException(
                        sprintf('Failed to instantiate controller [%s]: %s', $class, $e->getMessage()),
                        0,
                        $e
                    );
                }
            }
        };
    }
}
