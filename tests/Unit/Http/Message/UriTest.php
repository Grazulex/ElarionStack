<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Message;

use Elarion\Http\Message\Uri;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UriTest extends TestCase
{
    #[Test]
    public function from_string_parses_full_uri(): void
    {
        $uri = Uri::fromString('https://user:pass@example.com:8080/path?query=1#fragment');

        $this->assertSame('https', $uri->getScheme());
        $this->assertSame('user:pass', $uri->getUserInfo());
        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame(8080, $uri->getPort());
        $this->assertSame('/path', $uri->getPath());
        $this->assertSame('query=1', $uri->getQuery());
        $this->assertSame('fragment', $uri->getFragment());
    }

    #[Test]
    public function get_authority_returns_correct_format(): void
    {
        $uri = new Uri('https', 'example.com', 8080, '/', '', '', 'user:pass');

        $this->assertSame('user:pass@example.com:8080', $uri->getAuthority());
    }

    #[Test]
    public function get_port_returns_null_for_standard_ports(): void
    {
        $http = new Uri('http', 'example.com', 80);
        $https = new Uri('https', 'example.com', 443);

        $this->assertNull($http->getPort());
        $this->assertNull($https->getPort());
    }

    #[Test]
    public function with_scheme_returns_new_instance(): void
    {
        $uri1 = new Uri('http', 'example.com');
        $uri2 = $uri1->withScheme('https');

        $this->assertNotSame($uri1, $uri2);
        $this->assertSame('http', $uri1->getScheme());
        $this->assertSame('https', $uri2->getScheme());
    }

    #[Test]
    public function with_port_validates_range(): void
    {
        $uri = new Uri();

        $this->expectException(\InvalidArgumentException::class);
        $uri->withPort(99999);
    }

    #[Test]
    public function to_string_builds_full_uri(): void
    {
        $uri = new Uri('https', 'example.com', 8080, '/path', 'query=1', 'fragment');

        $this->assertSame('https://example.com:8080/path?query=1#fragment', (string) $uri);
    }

    #[Test]
    public function immutability_with_methods_return_same_instance_when_no_change(): void
    {
        $uri = new Uri('http', 'example.com');

        $this->assertSame($uri, $uri->withScheme('http'));
        $this->assertSame($uri, $uri->withHost('example.com'));
    }
}
