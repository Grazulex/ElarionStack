<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use Elarion\Http\Message\Response;
use Elarion\Http\Message\ServerRequest;
use Elarion\Http\Message\Uri;
use Elarion\Http\Middleware\RouteMiddlewareExecutor;
use Elarion\Routing\Route;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RouteMiddlewareExecutorTest extends TestCase
{
    #[Test]
    public function executes_route_handler_without_middlewares(): void
    {
        $handler = fn (ServerRequestInterface $request) => Response::json(['handler' => 'executed']);

        $route = new Route('GET', '/test', $handler);

        $executor = new RouteMiddlewareExecutor();
        $request = new ServerRequest('GET', new Uri());
        $response = $executor->execute($route, $request);

        $this->assertSame('{"handler":"executed"}', (string) $response->getBody());
    }

    #[Test]
    public function executes_route_with_callable_middleware(): void
    {
        $middleware = function (ServerRequestInterface $request, RequestHandlerInterface $next) {
            $request = $request->withAttribute('middleware', 'executed');

            return $next->handle($request);
        };

        $handler = function (ServerRequestInterface $request) {
            return Response::json([
                'middleware' => $request->getAttribute('middleware'),
            ]);
        };

        $route = (new Route('GET', '/test', $handler))->middleware($middleware);

        $executor = new RouteMiddlewareExecutor();
        $request = new ServerRequest('GET', new Uri());
        $response = $executor->execute($route, $request);

        $this->assertSame('{"middleware":"executed"}', (string) $response->getBody());
    }

    #[Test]
    public function executes_route_with_middleware_class(): void
    {
        // Create a test middleware class
        $middlewareClass = new class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                $request = $request->withAttribute('test', 'value');

                return $handler->handle($request);
            }
        };

        $handler = fn (ServerRequestInterface $request) => Response::json([
            'test' => $request->getAttribute('test'),
        ]);

        // Note: Route stores middleware as string/callable/array, so we pass the class name
        // The executor will handle resolving it to MiddlewareInterface
        $route = (new Route('GET', '/test', $handler))
            ->middleware(get_class($middlewareClass));

        // Use executor with container that can resolve the middleware
        $container = new class($middlewareClass) implements \Psr\Container\ContainerInterface {
            public function __construct(private object $middleware) {}

            public function has(string $id): bool
            {
                return get_class($this->middleware) === $id;
            }

            public function get(string $id): object
            {
                return $this->middleware;
            }
        };

        $executor = new RouteMiddlewareExecutor($container);
        $request = new ServerRequest('GET', new Uri());
        $response = $executor->execute($route, $request);

        $this->assertSame('{"test":"value"}', (string) $response->getBody());
    }

    #[Test]
    public function adds_route_params_as_request_attributes(): void
    {
        $handler = function (ServerRequestInterface $request) {
            return Response::json([
                'id' => $request->getAttribute('id'),
                'slug' => $request->getAttribute('slug'),
            ]);
        };

        $route = new Route('GET', '/posts/{id}/{slug}', $handler);

        $executor = new RouteMiddlewareExecutor();
        $request = new ServerRequest('GET', new Uri());
        $response = $executor->execute($route, $request, ['id' => '123', 'slug' => 'hello']);

        $this->assertSame('{"id":"123","slug":"hello"}', (string) $response->getBody());
    }

    #[Test]
    public function executes_controller_array_handler(): void
    {
        $controller = new class {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return Response::json(['controller' => 'executed']);
            }
        };

        $route = new Route('GET', '/test', [$controller, 'handle']);

        $executor = new RouteMiddlewareExecutor();
        $request = new ServerRequest('GET', new Uri());
        $response = $executor->execute($route, $request);

        $this->assertSame('{"controller":"executed"}', (string) $response->getBody());
    }

    #[Test]
    public function throws_exception_when_handler_does_not_return_response(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Route handler must return instance of/');

        $handler = fn (ServerRequestInterface $request) => 'invalid'; // Not a Response

        $route = new Route('GET', '/test', $handler);

        $executor = new RouteMiddlewareExecutor();
        $request = new ServerRequest('GET', new Uri());
        $executor->execute($route, $request);
    }

    #[Test]
    public function executes_multiple_middlewares_in_order(): void
    {
        $order = [];

        $middleware1 = function ($request, $next) use (&$order) {
            $order[] = 'middleware1';

            return $next->handle($request);
        };

        $middleware2 = function ($request, $next) use (&$order) {
            $order[] = 'middleware2';

            return $next->handle($request);
        };

        $handler = function ($request) use (&$order) {
            $order[] = 'handler';

            return new Response();
        };

        $route = (new Route('GET', '/test', $handler))
            ->middleware($middleware1)
            ->middleware($middleware2);

        $executor = new RouteMiddlewareExecutor();
        $request = new ServerRequest('GET', new Uri());
        $executor->execute($route, $request);

        $this->assertSame(['middleware1', 'middleware2', 'handler'], $order);
    }

    #[Test]
    public function middleware_can_short_circuit_route_execution(): void
    {
        $middleware = function (ServerRequestInterface $request, RequestHandlerInterface $next) {
            // Short-circuit: return response without calling next
            return Response::json(['short-circuit' => true]);
        };

        $handlerExecuted = false;
        $handler = function (ServerRequestInterface $request) use (&$handlerExecuted) {
            $handlerExecuted = true;

            return new Response();
        };

        $route = (new Route('GET', '/test', $handler))->middleware($middleware);

        $executor = new RouteMiddlewareExecutor();
        $request = new ServerRequest('GET', new Uri());
        $response = $executor->execute($route, $request);

        $this->assertFalse($handlerExecuted);
        $this->assertSame('{"short-circuit":true}', (string) $response->getBody());
    }

    #[Test]
    public function throws_exception_for_invalid_middleware_class(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Unable to resolve middleware/');

        $route = (new Route('GET', '/test', fn () => new Response()))
            ->middleware('NonExistentMiddleware');

        $executor = new RouteMiddlewareExecutor();
        $request = new ServerRequest('GET', new Uri());
        $executor->execute($route, $request);
    }
}
