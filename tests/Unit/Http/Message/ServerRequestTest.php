<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Message;

use Elarion\Http\Message\ServerRequest;
use Elarion\Http\Message\Uri;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ServerRequestTest extends TestCase
{
    #[Test]
    public function stores_server_params(): void
    {
        $serverParams = ['REQUEST_METHOD' => 'POST'];
        $request = new ServerRequest('POST', new Uri(), [], null, '1.1', $serverParams);

        $this->assertSame($serverParams, $request->getServerParams());
    }

    #[Test]
    public function with_cookie_params_returns_new_instance(): void
    {
        $request = new ServerRequest('GET', new Uri());
        $cookies = ['session' => 'abc123'];
        $new = $request->withCookieParams($cookies);

        $this->assertNotSame($request, $new);
        $this->assertSame($cookies, $new->getCookieParams());
    }

    #[Test]
    public function with_query_params_returns_new_instance(): void
    {
        $request = new ServerRequest('GET', new Uri());
        $query = ['page' => '1'];
        $new = $request->withQueryParams($query);

        $this->assertNotSame($request, $new);
        $this->assertSame($query, $new->getQueryParams());
    }

    #[Test]
    public function with_parsed_body_stores_data(): void
    {
        $request = new ServerRequest('POST', new Uri());
        $body = ['name' => 'John'];
        $new = $request->withParsedBody($body);

        $this->assertSame($body, $new->getParsedBody());
    }

    #[Test]
    public function attributes_can_be_set_and_retrieved(): void
    {
        $request = new ServerRequest('GET', new Uri());
        $request = $request->withAttribute('userId', 123);

        $this->assertSame(123, $request->getAttribute('userId'));
        $this->assertNull($request->getAttribute('nonexistent'));
        $this->assertSame('default', $request->getAttribute('nonexistent', 'default'));
    }

    #[Test]
    public function with_attribute_returns_new_instance(): void
    {
        $request = new ServerRequest('GET', new Uri());
        $new = $request->withAttribute('key', 'value');

        $this->assertNotSame($request, $new);
    }

    #[Test]
    public function without_attribute_removes_attribute(): void
    {
        $request = (new ServerRequest('GET', new Uri()))
            ->withAttribute('key', 'value')
            ->withoutAttribute('key');

        $this->assertNull($request->getAttribute('key'));
    }

    #[Test]
    public function get_attributes_returns_all_attributes(): void
    {
        $request = (new ServerRequest('GET', new Uri()))
            ->withAttribute('key1', 'value1')
            ->withAttribute('key2', 'value2');

        $this->assertSame(['key1' => 'value1', 'key2' => 'value2'], $request->getAttributes());
    }
}
