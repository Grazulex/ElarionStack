<?php

declare(strict_types=1);

namespace Tests\Unit\Routing\Adapters;

use Elarion\Routing\Adapters\FastRouteCollector;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FastRouteCollectorTest extends TestCase
{
    #[Test]
    public function registers_get_route(): void
    {
        $collector = new FastRouteCollector();
        $route = $collector->get('/users', fn () => 'response');

        $this->assertSame('GET', $route->getMethod());
        $this->assertSame('/users', $route->getUri());
    }

    #[Test]
    public function registers_post_route(): void
    {
        $collector = new FastRouteCollector();
        $route = $collector->post('/users', fn () => 'response');

        $this->assertSame('POST', $route->getMethod());
    }

    #[Test]
    public function registers_put_route(): void
    {
        $collector = new FastRouteCollector();
        $route = $collector->put('/users/1', fn () => 'response');

        $this->assertSame('PUT', $route->getMethod());
    }

    #[Test]
    public function registers_patch_route(): void
    {
        $collector = new FastRouteCollector();
        $route = $collector->patch('/users/1', fn () => 'response');

        $this->assertSame('PATCH', $route->getMethod());
    }

    #[Test]
    public function registers_delete_route(): void
    {
        $collector = new FastRouteCollector();
        $route = $collector->delete('/users/1', fn () => 'response');

        $this->assertSame('DELETE', $route->getMethod());
    }

    #[Test]
    public function registers_options_route(): void
    {
        $collector = new FastRouteCollector();
        $route = $collector->options('/users', fn () => 'response');

        $this->assertSame('OPTIONS', $route->getMethod());
    }

    #[Test]
    public function match_registers_multiple_methods(): void
    {
        $collector = new FastRouteCollector();
        $collector->match(['GET', 'POST'], '/users', fn () => 'response');

        $routes = $collector->getRoutes();
        $this->assertCount(2, $routes);
    }

    #[Test]
    public function any_registers_all_methods(): void
    {
        $collector = new FastRouteCollector();
        $collector->any('/users', fn () => 'response');

        $routes = $collector->getRoutes();
        $this->assertGreaterThanOrEqual(7, count($routes)); // All HTTP methods
    }

    #[Test]
    public function group_applies_prefix(): void
    {
        $collector = new FastRouteCollector();

        $collector->group(['prefix' => 'api'], function ($r) {
            $r->get('/users', fn () => 'response');
        });

        $routes = $collector->getRoutes();
        $this->assertSame('/api/users', $routes[0]->getUri());
    }

    #[Test]
    public function group_applies_middleware(): void
    {
        $collector = new FastRouteCollector();

        $collector->group(['middleware' => ['auth']], function ($r) {
            $r->get('/users', fn () => 'response');
        });

        $routes = $collector->getRoutes();
        $this->assertSame(['auth'], $routes[0]->getMiddleware());
    }

    #[Test]
    public function nested_groups_merge_attributes(): void
    {
        $collector = new FastRouteCollector();

        $collector->group(['prefix' => 'api', 'middleware' => ['auth']], function ($r) {
            $r->group(['prefix' => 'v1', 'middleware' => ['throttle']], function ($r) {
                $r->get('/users', fn () => 'response');
            });
        });

        $routes = $collector->getRoutes();
        $this->assertSame('/api/v1/users', $routes[0]->getUri());
        $this->assertSame(['auth', 'throttle'], $routes[0]->getMiddleware());
    }

    #[Test]
    public function build_fast_route_data_returns_correct_structure(): void
    {
        $collector = new FastRouteCollector();
        $collector->get('/users', fn () => 'response');
        $collector->post('/users', fn () => 'response');

        $data = $collector->buildFastRouteData();

        $this->assertCount(2, $data);
        $this->assertArrayHasKey('method', $data[0]);
        $this->assertArrayHasKey('uri', $data[0]);
        $this->assertArrayHasKey('routeIndex', $data[0]);
    }

    #[Test]
    public function get_routes_returns_all_registered_routes(): void
    {
        $collector = new FastRouteCollector();
        $collector->get('/users', fn () => 'response');
        $collector->post('/users', fn () => 'response');
        $collector->put('/users/{id}', fn () => 'response');

        $this->assertCount(3, $collector->getRoutes());
    }

    #[Test]
    public function register_named_route_stores_route_by_name(): void
    {
        $collector = new FastRouteCollector();
        $route = $collector->get('/users', fn () => 'response');

        $collector->registerNamedRoute('users.index', $route);

        $this->assertSame($route, $collector->getRouteByName('users.index'));
    }

    #[Test]
    public function get_route_by_name_returns_null_when_not_found(): void
    {
        $collector = new FastRouteCollector();

        $this->assertNull($collector->getRouteByName('nonexistent'));
    }
}
