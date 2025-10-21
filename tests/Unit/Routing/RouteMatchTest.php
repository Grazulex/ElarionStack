<?php

declare(strict_types=1);

namespace Tests\Unit\Routing;

use Elarion\Routing\Contracts\RouteMatchInterface;
use Elarion\Routing\RouteMatch;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RouteMatchTest extends TestCase
{
    #[Test]
    public function found_creates_successful_match(): void
    {
        $handler = fn () => 'response';
        $params = ['id' => '123'];
        $middleware = ['auth'];

        $match = RouteMatch::found($handler, $params, $middleware);

        $this->assertTrue($match->isFound());
        $this->assertFalse($match->isMethodNotAllowed());
        $this->assertSame(RouteMatchInterface::FOUND, $match->getStatus());
        $this->assertSame($handler, $match->getHandler());
        $this->assertSame($params, $match->getParams());
        $this->assertSame($middleware, $match->getMiddleware());
    }

    #[Test]
    public function not_found_creates_not_found_match(): void
    {
        $match = RouteMatch::notFound();

        $this->assertFalse($match->isFound());
        $this->assertFalse($match->isMethodNotAllowed());
        $this->assertSame(RouteMatchInterface::NOT_FOUND, $match->getStatus());
        $this->assertNull($match->getHandler());
        $this->assertEmpty($match->getParams());
        $this->assertEmpty($match->getMiddleware());
    }

    #[Test]
    public function method_not_allowed_creates_correct_match(): void
    {
        $allowedMethods = ['GET', 'POST'];
        $match = RouteMatch::methodNotAllowed($allowedMethods);

        $this->assertFalse($match->isFound());
        $this->assertTrue($match->isMethodNotAllowed());
        $this->assertSame(RouteMatchInterface::METHOD_NOT_ALLOWED, $match->getStatus());
        $this->assertNull($match->getHandler());
        $this->assertSame($allowedMethods, $match->getAllowedMethods());
    }

    #[Test]
    public function match_is_readonly(): void
    {
        $match = RouteMatch::found(fn () => 'test');

        $this->expectException(\Error::class);
        /** @phpstan-ignore-next-line */
        $match->status = RouteMatchInterface::NOT_FOUND;
    }
}
