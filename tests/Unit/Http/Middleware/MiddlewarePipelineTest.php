<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use Elarion\Http\Message\Response;
use Elarion\Http\Message\ServerRequest;
use Elarion\Http\Message\Uri;
use Elarion\Http\Middleware\MiddlewarePipeline;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MiddlewarePipelineTest extends TestCase
{
    #[Test]
    public function executes_middlewares_in_fifo_order(): void
    {
        $order = [];

        $middleware1 = $this->createMiddleware(function ($request, $next) use (&$order) {
            $order[] = 'middleware1-before';
            $response = $next->handle($request);
            $order[] = 'middleware1-after';

            return $response;
        });

        $middleware2 = $this->createMiddleware(function ($request, $next) use (&$order) {
            $order[] = 'middleware2-before';
            $response = $next->handle($request);
            $order[] = 'middleware2-after';

            return $response;
        });

        $fallbackHandler = $this->createHandler(function () use (&$order) {
            $order[] = 'handler';

            return new Response();
        });

        $pipeline = (new MiddlewarePipeline())
            ->pipe($middleware1)
            ->pipe($middleware2)
            ->setFallbackHandler($fallbackHandler);

        $request = new ServerRequest('GET', new Uri());
        $pipeline->handle($request);

        $this->assertSame([
            'middleware1-before',
            'middleware2-before',
            'handler',
            'middleware2-after',
            'middleware1-after',
        ], $order);
    }

    #[Test]
    public function middleware_can_short_circuit_by_returning_response(): void
    {
        $executed = [];

        $middleware1 = $this->createMiddleware(function ($request, $next) use (&$executed) {
            $executed[] = 'middleware1';

            return $next->handle($request);
        });

        $middleware2 = $this->createMiddleware(function ($request, $next) use (&$executed) {
            $executed[] = 'middleware2';
            // Short-circuit: return response without calling next handler
            return Response::json(['short-circuit' => true]);
        });

        $middleware3 = $this->createMiddleware(function ($request, $next) use (&$executed) {
            $executed[] = 'middleware3'; // Should NOT execute

            return $next->handle($request);
        });

        $fallbackHandler = $this->createHandler(function () use (&$executed) {
            $executed[] = 'handler'; // Should NOT execute

            return new Response();
        });

        $pipeline = (new MiddlewarePipeline())
            ->pipe($middleware1)
            ->pipe($middleware2)
            ->pipe($middleware3)
            ->setFallbackHandler($fallbackHandler);

        $request = new ServerRequest('GET', new Uri());
        $response = $pipeline->handle($request);

        $this->assertSame(['middleware1', 'middleware2'], $executed);
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
    }

    #[Test]
    public function middleware_can_modify_request(): void
    {
        $middleware = $this->createMiddleware(function ($request, $next) {
            // Add attribute to request
            $request = $request->withAttribute('user_id', 123);

            return $next->handle($request);
        });

        $receivedRequest = null;
        $fallbackHandler = $this->createHandler(function ($request) use (&$receivedRequest) {
            $receivedRequest = $request;

            return new Response();
        });

        $pipeline = (new MiddlewarePipeline())
            ->pipe($middleware)
            ->setFallbackHandler($fallbackHandler);

        $request = new ServerRequest('GET', new Uri());
        $pipeline->handle($request);

        $this->assertSame(123, $receivedRequest?->getAttribute('user_id'));
    }

    #[Test]
    public function middleware_can_modify_response(): void
    {
        $middleware = $this->createMiddleware(function ($request, $next) {
            $response = $next->handle($request);
            // Add header to response
            return $response->withHeader('X-Custom', 'Modified');
        });

        $fallbackHandler = $this->createHandler(function () {
            return new Response(200, [], 'Original content');
        });

        $pipeline = (new MiddlewarePipeline())
            ->pipe($middleware)
            ->setFallbackHandler($fallbackHandler);

        $request = new ServerRequest('GET', new Uri());
        $response = $pipeline->handle($request);

        $this->assertSame('Modified', $response->getHeaderLine('X-Custom'));
        $this->assertSame('Original content', (string) $response->getBody());
    }

    #[Test]
    public function uses_fallback_handler_when_no_middlewares(): void
    {
        $fallbackHandler = $this->createHandler(function () {
            return Response::json(['fallback' => true]);
        });

        $pipeline = (new MiddlewarePipeline())
            ->setFallbackHandler($fallbackHandler);

        $request = new ServerRequest('GET', new Uri());
        $response = $pipeline->handle($request);

        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame('{"fallback":true}', (string) $response->getBody());
    }

    #[Test]
    public function throws_exception_when_no_fallback_handler_and_no_middlewares(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Pipeline has no middlewares and no fallback handler set');

        $pipeline = new MiddlewarePipeline();
        $request = new ServerRequest('GET', new Uri());
        $pipeline->handle($request);
    }

    #[Test]
    public function throws_exception_when_no_fallback_handler_with_middlewares(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No fallback handler set for pipeline');

        $middleware = $this->createMiddleware(function ($request, $next) {
            return $next->handle($request);
        });

        $pipeline = (new MiddlewarePipeline())->pipe($middleware);
        $request = new ServerRequest('GET', new Uri());
        $pipeline->handle($request);
    }

    #[Test]
    public function pipe_returns_self_for_fluent_interface(): void
    {
        $middleware = $this->createMiddleware(function ($request, $next) {
            return $next->handle($request);
        });

        $pipeline = new MiddlewarePipeline();
        $result = $pipeline->pipe($middleware);

        $this->assertSame($pipeline, $result);
    }

    #[Test]
    public function set_fallback_handler_returns_self_for_fluent_interface(): void
    {
        $handler = $this->createHandler(function () {
            return new Response();
        });

        $pipeline = new MiddlewarePipeline();
        $result = $pipeline->setFallbackHandler($handler);

        $this->assertSame($pipeline, $result);
    }

    #[Test]
    public function multiple_middlewares_can_each_modify_request_and_response(): void
    {
        $middleware1 = $this->createMiddleware(function ($request, $next) {
            $request = $request->withAttribute('step1', 'added');
            $response = $next->handle($request);

            return $response->withAddedHeader('X-Step', '1');
        });

        $middleware2 = $this->createMiddleware(function ($request, $next) {
            $request = $request->withAttribute('step2', 'added');
            $response = $next->handle($request);

            return $response->withAddedHeader('X-Step', '2');
        });

        $receivedRequest = null;
        $fallbackHandler = $this->createHandler(function ($request) use (&$receivedRequest) {
            $receivedRequest = $request;

            return new Response();
        });

        $pipeline = (new MiddlewarePipeline())
            ->pipe($middleware1)
            ->pipe($middleware2)
            ->setFallbackHandler($fallbackHandler);

        $request = new ServerRequest('GET', new Uri());
        $response = $pipeline->handle($request);

        // Request modifications accumulated
        $this->assertSame('added', $receivedRequest?->getAttribute('step1'));
        $this->assertSame('added', $receivedRequest?->getAttribute('step2'));

        // Response modifications accumulated (in reverse order)
        $this->assertSame(['2', '1'], $response->getHeader('X-Step'));
    }

    /**
     * Create test middleware from callable
     *
     * @param callable $callback Callback: fn(ServerRequestInterface, RequestHandlerInterface): ResponseInterface
     * @return MiddlewareInterface Test middleware
     */
    private function createMiddleware(callable $callback): MiddlewareInterface
    {
        return new class($callback) implements MiddlewareInterface {
            public function __construct(private $callback) {}

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return ($this->callback)($request, $handler);
            }
        };
    }

    /**
     * Create test request handler from callable
     *
     * @param callable $callback Callback: fn(ServerRequestInterface): ResponseInterface
     * @return RequestHandlerInterface Test handler
     */
    private function createHandler(callable $callback): RequestHandlerInterface
    {
        return new class($callback) implements RequestHandlerInterface {
            public function __construct(private $callback) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return ($this->callback)($request);
            }
        };
    }
}
