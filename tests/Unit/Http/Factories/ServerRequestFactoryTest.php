<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Factories;

use Elarion\Http\Factories\ServerRequestFactory;
use Elarion\Http\Message\Uri;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ServerRequestFactoryTest extends TestCase
{
    #[Test]
    public function create_server_request_with_string_uri(): void
    {
        $factory = new ServerRequestFactory();
        $request = $factory->createServerRequest('GET', 'https://example.com');

        $this->assertSame('GET', $request->getMethod());
        $this->assertInstanceOf(Uri::class, $request->getUri());
    }

    #[Test]
    public function create_server_request_with_uri_object(): void
    {
        $factory = new ServerRequestFactory();
        $uri = new Uri('https', 'example.com');
        $request = $factory->createServerRequest('POST', $uri);

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame($uri, $request->getUri());
    }

    #[Test]
    public function create_server_request_with_server_params(): void
    {
        $factory = new ServerRequestFactory();
        $serverParams = ['REQUEST_METHOD' => 'GET'];
        $request = $factory->createServerRequest('GET', '/', $serverParams);

        $this->assertSame($serverParams, $request->getServerParams());
    }
}
