<?php

declare(strict_types=1);

namespace Tests\Unit\Routing;

use Elarion\Routing\HttpMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class HttpMethodTest extends TestCase
{
    #[Test]
    public function from_string_creates_enum_from_string_case_insensitive(): void
    {
        $this->assertSame(HttpMethod::GET, HttpMethod::fromString('GET'));
        $this->assertSame(HttpMethod::GET, HttpMethod::fromString('get'));
        $this->assertSame(HttpMethod::POST, HttpMethod::fromString('post'));
        $this->assertSame(HttpMethod::PUT, HttpMethod::fromString('PUT'));
    }

    #[Test]
    public function from_string_throws_for_invalid_method(): void
    {
        $this->expectException(\ValueError::class);
        HttpMethod::fromString('INVALID');
    }

    #[Test]
    public function try_from_string_returns_null_for_invalid_method(): void
    {
        $this->assertNull(HttpMethod::tryFromString('INVALID'));
    }

    #[Test]
    public function is_safe_returns_true_for_safe_methods(): void
    {
        $this->assertTrue(HttpMethod::GET->isSafe());
        $this->assertTrue(HttpMethod::HEAD->isSafe());
        $this->assertTrue(HttpMethod::OPTIONS->isSafe());
        $this->assertFalse(HttpMethod::POST->isSafe());
        $this->assertFalse(HttpMethod::DELETE->isSafe());
    }

    #[Test]
    public function is_idempotent_returns_correct_values(): void
    {
        $this->assertTrue(HttpMethod::GET->isIdempotent());
        $this->assertTrue(HttpMethod::PUT->isIdempotent());
        $this->assertTrue(HttpMethod::DELETE->isIdempotent());
        $this->assertFalse(HttpMethod::POST->isIdempotent());
        $this->assertFalse(HttpMethod::PATCH->isIdempotent());
    }

    #[Test]
    public function values_returns_all_method_strings(): void
    {
        $values = HttpMethod::values();

        $this->assertContains('GET', $values);
        $this->assertContains('POST', $values);
        $this->assertContains('PUT', $values);
        $this->assertContains('PATCH', $values);
        $this->assertContains('DELETE', $values);
        $this->assertContains('OPTIONS', $values);
        $this->assertContains('HEAD', $values);
    }
}
