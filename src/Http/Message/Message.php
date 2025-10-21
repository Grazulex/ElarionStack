<?php

declare(strict_types=1);

namespace Elarion\Http\Message;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Base HTTP message implementation
 *
 * Abstract base class for Request and Response.
 * Following Template Method pattern for common functionality.
 */
abstract class Message implements MessageInterface
{
    /**
     * HTTP protocol version
     */
    protected string $protocolVersion = '1.1';

    /**
     * Message headers
     */
    protected HeaderBag $headers;

    /**
     * Message body
     */
    protected StreamInterface $body;

    /**
     * Create message
     *
     * @param array<string, string|array<int, string>> $headers Message headers
     * @param StreamInterface|null $body Message body
     * @param string $protocolVersion HTTP protocol version
     */
    public function __construct(
        array $headers = [],
        ?StreamInterface $body = null,
        string $protocolVersion = '1.1'
    ) {
        $this->headers = new HeaderBag($headers);
        $this->body = $body ?? Stream::fromString('');
        $this->protocolVersion = $protocolVersion;
    }

    /**
     * {@inheritdoc}
     */
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * {@inheritdoc}
     */
    public function withProtocolVersion(string $version): MessageInterface
    {
        if ($version === $this->protocolVersion) {
            return $this;
        }

        $clone = clone $this;
        $clone->protocolVersion = $version;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders(): array
    {
        return $this->headers->all();
    }

    /**
     * {@inheritdoc}
     */
    public function hasHeader(string $name): bool
    {
        return $this->headers->has($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader(string $name): array
    {
        return $this->headers->get($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaderLine(string $name): string
    {
        return $this->headers->getLine($name);
    }

    /**
     * {@inheritdoc}
     */
    public function withHeader(string $name, $value): MessageInterface
    {
        $clone = clone $this;
        $clone->headers = clone $this->headers;
        $clone->headers->set($name, $value);

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withAddedHeader(string $name, $value): MessageInterface
    {
        $clone = clone $this;
        $clone->headers = clone $this->headers;
        $clone->headers->add($name, $value);

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withoutHeader(string $name): MessageInterface
    {
        if (! $this->hasHeader($name)) {
            return $this;
        }

        $clone = clone $this;
        $clone->headers = clone $this->headers;
        $clone->headers->remove($name);

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    /**
     * {@inheritdoc}
     */
    public function withBody(StreamInterface $body): MessageInterface
    {
        if ($body === $this->body) {
            return $this;
        }

        $clone = clone $this;
        $clone->body = $body;

        return $clone;
    }
}
