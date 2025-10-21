<?php

declare(strict_types=1);

namespace Tests\Unit\Routing;

use Elarion\Routing\Contracts\RouteMatchInterface;
use Elarion\Routing\Router;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
    }

    #[Test]
    public function registers_and_dispatches_get_route(): void
    {
        $this->router->get('/users', fn () => 'users list');

        $match = $this->router->dispatch('GET', '/users');

        $this->assertTrue($match->isFound());
        $handler = $match->getHandler();
        $this->assertIsCallable($handler);
        $this->assertSame('users list', $handler());
    }

    #[Test]
    public function registers_and_dispatches_post_route(): void
    {
        $this->router->post('/users', fn () => 'created');

        $match = $this->router->dispatch('POST', '/users');

        $this->assertTrue($match->isFound());
    }

    #[Test]
    public function extracts_route_parameters(): void
    {
        $this->router->get('/users/{id}', fn ($id) => "user $id");

        $match = $this->router->dispatch('GET', '/users/123');

        $this->assertTrue($match->isFound());
        $this->assertSame(['id' => '123'], $match->getParams());
    }

    #[Test]
    public function returns_not_found_for_unmatched_route(): void
    {
        $match = $this->router->dispatch('GET', '/nonexistent');

        $this->assertFalse($match->isFound());
        $this->assertSame(RouteMatchInterface::NOT_FOUND, $match->getStatus());
    }

    #[Test]
    public function returns_method_not_allowed_for_wrong_method(): void
    {
        $this->router->get('/users', fn () => 'response');

        $match = $this->router->dispatch('POST', '/users');

        $this->assertTrue($match->isMethodNotAllowed());
        $this->assertSame(RouteMatchInterface::METHOD_NOT_ALLOWED, $match->getStatus());
        $this->assertContains('GET', $match->getAllowedMethods());
    }

    #[Test]
    public function groups_apply_prefix_to_routes(): void
    {
        $this->router->group(['prefix' => 'api'], function ($r) {
            $r->get('/users', fn () => 'users');
        });

        $match = $this->router->dispatch('GET', '/api/users');

        $this->assertTrue($match->isFound());
    }

    #[Test]
    public function groups_apply_middleware_to_routes(): void
    {
        $this->router->group(['middleware' => ['auth']], function ($r) {
            $r->get('/users', fn () => 'users');
        });

        $match = $this->router->dispatch('GET', '/users');

        $this->assertTrue($match->isFound());
        $this->assertSame(['auth'], $match->getMiddleware());
    }

    #[Test]
    public function nested_groups_merge_attributes(): void
    {
        $this->router->group(['prefix' => 'api', 'middleware' => ['auth']], function ($r) {
            $r->group(['prefix' => 'v1', 'middleware' => ['throttle']], function ($r) {
                $r->get('/users', fn () => 'users');
            });
        });

        $match = $this->router->dispatch('GET', '/api/v1/users');

        $this->assertTrue($match->isFound());
        $this->assertSame(['auth', 'throttle'], $match->getMiddleware());
    }

    #[Test]
    public function named_routes_can_generate_urls(): void
    {
        $this->router->get('/users/{id}', fn () => 'user')
            ->name('users.show');

        $url = $this->router->url('users.show', ['id' => 123]);

        $this->assertSame('/users/123', $url);
    }

    #[Test]
    public function url_throws_for_nonexistent_route_name(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Route [nonexistent] not found');

        $this->router->url('nonexistent');
    }

    #[Test]
    public function match_registers_multiple_methods(): void
    {
        $this->router->match(['GET', 'POST'], '/users', fn () => 'response');

        $getMatch = $this->router->dispatch('GET', '/users');
        $postMatch = $this->router->dispatch('POST', '/users');

        $this->assertTrue($getMatch->isFound());
        $this->assertTrue($postMatch->isFound());
    }

    #[Test]
    public function any_accepts_all_methods(): void
    {
        $this->router->any('/users', fn () => 'response');

        $this->assertTrue($this->router->dispatch('GET', '/users')->isFound());
        $this->assertTrue($this->router->dispatch('POST', '/users')->isFound());
        $this->assertTrue($this->router->dispatch('PUT', '/users')->isFound());
        $this->assertTrue($this->router->dispatch('DELETE', '/users')->isFound());
    }

    #[Test]
    public function multiple_parameters_in_route(): void
    {
        $this->router->get('/users/{userId}/posts/{postId}', fn () => 'response');

        $match = $this->router->dispatch('GET', '/users/123/posts/456');

        $this->assertTrue($match->isFound());
        $this->assertSame(['userId' => '123', 'postId' => '456'], $match->getParams());
    }
}
