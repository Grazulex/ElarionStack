<?php

declare(strict_types=1);

namespace Elarion\Http\Message;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * PSR-7 Request implementation
 *
 * Represents an outgoing HTTP request.
 * Following SRP - handles request-specific data.
 */
class Request extends Message implements RequestInterface
{
    /**
     * HTTP request method
     */
    protected string $method;

    /**
     * Request URI
     */
    protected UriInterface $uri;

    /**
     * Request target
     */
    protected ?string $requestTarget = null;

    /**
     * Create request
     *
     * @param string $method HTTP method
     * @param UriInterface|string $uri Request URI
     * @param array<string, string|array<int, string>> $headers Request headers
     * @param StreamInterface|string|null $body Request body
     * @param string $protocolVersion HTTP protocol version
     */
    public function __construct(
        string $method,
        UriInterface|string $uri,
        array $headers = [],
        StreamInterface|string|null $body = null,
        string $protocolVersion = '1.1'
    ) {
        $this->method = strtoupper($method);
        $this->uri = is_string($uri) ? Uri::fromString($uri) : $uri;

        // Convert string body to Stream
        if (is_string($body)) {
            $body = Stream::fromString($body);
        }

        parent::__construct($headers, $body, $protocolVersion);

        // Add Host header if not present
        if (! $this->hasHeader('Host') && $this->uri->getHost() !== '') {
            $this->headers->set('Host', $this->uri->getHost());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();

        if ($target === '') {
            $target = '/';
        }

        if ($this->uri->getQuery() !== '') {
            $target .= '?' . $this->uri->getQuery();
        }

        return $target;
    }

    /**
     * {@inheritdoc}
     */
    public function withRequestTarget(string $requestTarget): RequestInterface
    {
        if ($requestTarget === $this->getRequestTarget()) {
            return $this;
        }

        $clone = clone $this;
        $clone->requestTarget = $requestTarget;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * {@inheritdoc}
     */
    public function withMethod(string $method): RequestInterface
    {
        $method = strtoupper($method);

        if ($method === $this->method) {
            return $this;
        }

        $clone = clone $this;
        $clone->method = $method;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * {@inheritdoc}
     */
    public function withUri(UriInterface $uri, bool $preserveHost = false): RequestInterface
    {
        if ($uri === $this->uri) {
            return $this;
        }

        $clone = clone $this;
        $clone->uri = $uri;
        $clone->headers = clone $this->headers;

        if (! $preserveHost || ! $this->hasHeader('Host')) {
            if ($uri->getHost() !== '') {
                $clone->headers->set('Host', $uri->getHost());
            }
        }

        return $clone;
    }
}
