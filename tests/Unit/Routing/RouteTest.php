<?php

declare(strict_types=1);

namespace Tests\Unit\Routing;

use Elarion\Routing\Route;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RouteTest extends TestCase
{
    #[Test]
    public function route_stores_basic_data(): void
    {
        $handler = fn () => 'response';
        $route = new Route('GET', '/users', $handler);

        $this->assertSame('GET', $route->getMethod());
        $this->assertSame('/users', $route->getUri());
        $this->assertSame($handler, $route->getHandler());
        $this->assertEmpty($route->getMiddleware());
        $this->assertNull($route->getName());
    }

    #[Test]
    public function middleware_adds_middleware_immutably(): void
    {
        $route1 = new Route('GET', '/users', fn () => 'test');
        $route2 = $route1->middleware('auth');

        $this->assertEmpty($route1->getMiddleware());
        $this->assertSame(['auth'], $route2->getMiddleware());
        $this->assertNotSame($route1, $route2);
    }

    #[Test]
    public function middleware_accepts_array(): void
    {
        $route = new Route('GET', '/users', fn () => 'test');
        $route = $route->middleware(['auth', 'throttle']);

        $this->assertSame(['auth', 'throttle'], $route->getMiddleware());
    }

    #[Test]
    public function name_assigns_name_immutably(): void
    {
        $route1 = new Route('GET', '/users', fn () => 'test');
        $route2 = $route1->name('users.index');

        $this->assertNull($route1->getName());
        $this->assertSame('users.index', $route2->getName());
        $this->assertNotSame($route1, $route2);
    }

    #[Test]
    public function where_adds_constraints_immutably(): void
    {
        $route1 = new Route('GET', '/users/{id}', fn () => 'test');
        $route2 = $route1->where('id', '[0-9]+');

        $this->assertEmpty($route1->getWhereConstraints());
        $this->assertSame(['id' => '[0-9]+'], $route2->getWhereConstraints());
    }

    #[Test]
    public function where_accepts_array(): void
    {
        $route = new Route('GET', '/users/{id}', fn () => 'test');
        $route = $route->where(['id' => '[0-9]+', 'slug' => '[a-z-]+']);

        $this->assertSame(['id' => '[0-9]+', 'slug' => '[a-z-]+'], $route->getWhereConstraints());
    }

    #[Test]
    public function fluent_api_chains_methods(): void
    {
        $route = new Route('GET', '/users/{id}', fn () => 'test');
        $configured = $route
            ->middleware('auth')
            ->where('id', '[0-9]+')
            ->name('users.show');

        $this->assertSame(['auth'], $configured->getMiddleware());
        $this->assertSame(['id' => '[0-9]+'], $configured->getWhereConstraints());
        $this->assertSame('users.show', $configured->getName());
    }
}
