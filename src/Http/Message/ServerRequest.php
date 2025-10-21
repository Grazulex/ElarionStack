<?php

declare(strict_types=1);

namespace Elarion\Http\Message;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

/**
 * PSR-7 Server request implementation
 *
 * Represents an incoming HTTP request to a server.
 * Following SRP - handles server-side request data.
 */
final class ServerRequest extends Request implements ServerRequestInterface
{
    /**
     * Server parameters ($_SERVER)
     *
     * @var array<string, mixed>
     */
    private array $serverParams;

    /**
     * Cookie parameters ($_COOKIE)
     *
     * @var array<string, string>
     */
    private array $cookieParams;

    /**
     * Query parameters ($_GET)
     *
     * @var array<string, mixed>
     */
    private array $queryParams;

    /**
     * Uploaded files ($_FILES)
     *
     * @var array<string, UploadedFileInterface>
     */
    private array $uploadedFiles;

    /**
     * Parsed body ($_POST or parsed JSON/XML)
     *
     * @var array<string, mixed>|object|null
     */
    private array|object|null $parsedBody = null;

    /**
     * Request attributes (route parameters, etc.)
     *
     * @var array<string, mixed>
     */
    private array $attributes = [];

    /**
     * Create server request
     *
     * @param string $method HTTP method
     * @param UriInterface|string $uri Request URI
     * @param array<string, string|array<int, string>> $headers Request headers
     * @param StreamInterface|string|null $body Request body
     * @param string $protocolVersion HTTP protocol version
     * @param array<string, mixed> $serverParams Server parameters
     */
    public function __construct(
        string $method,
        UriInterface|string $uri,
        array $headers = [],
        StreamInterface|string|null $body = null,
        string $protocolVersion = '1.1',
        array $serverParams = []
    ) {
        $this->serverParams = $serverParams;
        $this->cookieParams = [];
        $this->queryParams = [];
        $this->uploadedFiles = [];

        parent::__construct($method, $uri, $headers, $body, $protocolVersion);
    }

    /**
     * {@inheritdoc}
     */
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    /**
     * {@inheritdoc}
     */
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    /**
     * {@inheritdoc}
     */
    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->cookieParams = $cookies;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * {@inheritdoc}
     */
    public function withQueryParams(array $query): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->queryParams = $query;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /**
     * {@inheritdoc}
     */
    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->uploadedFiles = $uploadedFiles;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    /**
     * {@inheritdoc}
     */
    public function withParsedBody($data): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->parsedBody = $data;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute(string $name, $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * {@inheritdoc}
     */
    public function withAttribute(string $name, $value): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withoutAttribute(string $name): ServerRequestInterface
    {
        if (! isset($this->attributes[$name])) {
            return $this;
        }

        $clone = clone $this;
        unset($clone->attributes[$name]);

        return $clone;
    }
}
