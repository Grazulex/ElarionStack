<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Message;

use Elarion\Http\Message\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    #[Test]
    public function default_response_is_200_ok(): void
    {
        $response = new Response();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getReasonPhrase());
    }

    #[Test]
    public function with_status_changes_code_and_phrase(): void
    {
        $response = new Response();
        $new = $response->withStatus(404, 'Not Found');

        $this->assertNotSame($response, $new);
        $this->assertSame(404, $new->getStatusCode());
        $this->assertSame('Not Found', $new->getReasonPhrase());
    }

    #[Test]
    public function with_status_uses_standard_phrase_when_empty(): void
    {
        $response = (new Response())->withStatus(500);

        $this->assertSame('Internal Server Error', $response->getReasonPhrase());
    }

    #[Test]
    public function with_status_validates_code_range(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new Response())->withStatus(99);
    }

    #[Test]
    public function json_creates_json_response(): void
    {
        $data = ['message' => 'Hello'];
        $response = Response::json($data);

        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame('{"message":"Hello"}', (string) $response->getBody());
    }

    #[Test]
    public function html_creates_html_response(): void
    {
        $response = Response::html('<h1>Hello</h1>');

        $this->assertSame('text/html; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $this->assertSame('<h1>Hello</h1>', (string) $response->getBody());
    }

    #[Test]
    public function redirect_creates_redirect_response(): void
    {
        $response = Response::redirect('/login', 302);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/login', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function with_header_replaces_header(): void
    {
        $response = (new Response())
            ->withHeader('X-Custom', 'Value1')
            ->withHeader('X-Custom', 'Value2');

        $this->assertSame(['Value2'], $response->getHeader('X-Custom'));
    }

    #[Test]
    public function with_added_header_appends_value(): void
    {
        $response = (new Response())
            ->withHeader('X-Custom', 'Value1')
            ->withAddedHeader('X-Custom', 'Value2');

        $this->assertSame(['Value1', 'Value2'], $response->getHeader('X-Custom'));
    }
}
