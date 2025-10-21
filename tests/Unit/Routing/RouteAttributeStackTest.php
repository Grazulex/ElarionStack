<?php

declare(strict_types=1);

namespace Tests\Unit\Routing;

use Elarion\Routing\RouteAttributeStack;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RouteAttributeStackTest extends TestCase
{
    #[Test]
    public function current_returns_empty_attributes_when_stack_empty(): void
    {
        $stack = new RouteAttributeStack();
        $attributes = $stack->current();

        $this->assertSame('', $attributes['prefix']);
        $this->assertEmpty($attributes['middleware']);
        $this->assertSame('', $attributes['namespace']);
    }

    #[Test]
    public function push_adds_attributes_to_stack(): void
    {
        $stack = new RouteAttributeStack();
        $stack->push(['prefix' => 'api']);

        $this->assertSame(1, $stack->depth());
        $this->assertSame('api', $stack->current()['prefix']);
    }

    #[Test]
    public function pop_removes_attributes_from_stack(): void
    {
        $stack = new RouteAttributeStack();
        $stack->push(['prefix' => 'api']);
        $stack->pop();

        $this->assertTrue($stack->isEmpty());
        $this->assertSame(0, $stack->depth());
    }

    #[Test]
    public function current_merges_prefixes_correctly(): void
    {
        $stack = new RouteAttributeStack();
        $stack->push(['prefix' => 'api']);
        $stack->push(['prefix' => 'v1']);
        $stack->push(['prefix' => 'users']);

        $this->assertSame('api/v1/users', $stack->current()['prefix']);
    }

    #[Test]
    public function current_merges_middleware_arrays(): void
    {
        $stack = new RouteAttributeStack();
        $stack->push(['middleware' => ['auth']]);
        $stack->push(['middleware' => ['throttle']]);

        $expected = ['auth', 'throttle'];
        $this->assertSame($expected, $stack->current()['middleware']);
    }

    #[Test]
    public function current_merges_namespaces_correctly(): void
    {
        $stack = new RouteAttributeStack();
        $stack->push(['namespace' => 'App\\Controllers']);
        $stack->push(['namespace' => 'Api']);
        $stack->push(['namespace' => 'V1']);

        $this->assertSame('App\\Controllers\\Api\\V1', $stack->current()['namespace']);
    }

    #[Test]
    public function nested_groups_merge_all_attributes(): void
    {
        $stack = new RouteAttributeStack();

        $stack->push([
            'prefix' => 'api',
            'middleware' => ['auth'],
            'namespace' => 'App\\Controllers',
        ]);

        $stack->push([
            'prefix' => 'v1',
            'middleware' => ['throttle'],
            'namespace' => 'Api',
        ]);

        $current = $stack->current();

        $this->assertSame('api/v1', $current['prefix']);
        $this->assertSame(['auth', 'throttle'], $current['middleware']);
        $this->assertSame('App\\Controllers\\Api', $current['namespace']);
    }
}
