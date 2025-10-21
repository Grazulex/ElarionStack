<?php

declare(strict_types=1);

namespace Elarion\Http\Message;

use Psr\Http\Message\UriInterface;

/**
 * PSR-7 URI implementation
 *
 * Immutable value object representing a URI.
 * Following SRP - only handles URI parsing and building.
 */
final class Uri implements UriInterface
{
    private const SCHEMES = ['http' => 80, 'https' => 443];

    /**
     * Create URI from components
     *
     * @param string $scheme URI scheme
     * @param string $host URI host
     * @param int|null $port URI port
     * @param string $path URI path
     * @param string $query URI query string
     * @param string $fragment URI fragment
     * @param string $userInfo URI user info (user:password)
     */
    public function __construct(
        private string $scheme = '',
        private string $host = '',
        private ?int $port = null,
        private string $path = '',
        private string $query = '',
        private string $fragment = '',
        private string $userInfo = ''
    ) {
        $this->scheme = strtolower($scheme);
        $this->host = strtolower($host);
    }

    /**
     * Create URI from string
     *
     * @param string $uri URI string
     * @return self Parsed URI
     */
    public static function fromString(string $uri): self
    {
        $parts = parse_url($uri);

        if ($parts === false) {
            throw new \InvalidArgumentException(
                sprintf('Unable to parse URI: %s', $uri)
            );
        }

        return new self(
            scheme: $parts['scheme'] ?? '',
            host: $parts['host'] ?? '',
            port: $parts['port'] ?? null,
            path: $parts['path'] ?? '',
            query: $parts['query'] ?? '',
            fragment: $parts['fragment'] ?? '',
            userInfo: isset($parts['user'])
                ? $parts['user'] . (isset($parts['pass']) ? ':' . $parts['pass'] : '')
                : ''
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthority(): string
    {
        if ($this->host === '') {
            return '';
        }

        $authority = $this->host;

        if ($this->userInfo !== '') {
            $authority = $this->userInfo . '@' . $authority;
        }

        if ($this->port !== null && ! $this->isStandardPort()) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    /**
     * {@inheritdoc}
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * {@inheritdoc}
     */
    public function getPort(): ?int
    {
        return $this->isStandardPort() ? null : $this->port;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * {@inheritdoc}
     */
    public function getFragment(): string
    {
        return $this->fragment;
    }

    /**
     * {@inheritdoc}
     */
    public function withScheme(string $scheme): UriInterface
    {
        $scheme = strtolower($scheme);

        if ($scheme === $this->scheme) {
            return $this;
        }

        $clone = clone $this;
        $clone->scheme = $scheme;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withUserInfo(string $user, ?string $password = null): UriInterface
    {
        $userInfo = $user;

        if ($password !== null && $password !== '') {
            $userInfo .= ':' . $password;
        }

        if ($userInfo === $this->userInfo) {
            return $this;
        }

        $clone = clone $this;
        $clone->userInfo = $userInfo;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withHost(string $host): UriInterface
    {
        $host = strtolower($host);

        if ($host === $this->host) {
            return $this;
        }

        $clone = clone $this;
        $clone->host = $host;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withPort(?int $port): UriInterface
    {
        if ($port !== null && ($port < 1 || $port > 65535)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid port: %d. Must be between 1 and 65535', $port)
            );
        }

        if ($port === $this->port) {
            return $this;
        }

        $clone = clone $this;
        $clone->port = $port;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withPath(string $path): UriInterface
    {
        if ($path === $this->path) {
            return $this;
        }

        $clone = clone $this;
        $clone->path = $this->filterPath($path);

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withQuery(string $query): UriInterface
    {
        if (str_starts_with($query, '?')) {
            $query = substr($query, 1);
        }

        if ($query === $this->query) {
            return $this;
        }

        $clone = clone $this;
        $clone->query = $query;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withFragment(string $fragment): UriInterface
    {
        if (str_starts_with($fragment, '#')) {
            $fragment = substr($fragment, 1);
        }

        if ($fragment === $this->fragment) {
            return $this;
        }

        $clone = clone $this;
        $clone->fragment = $fragment;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        $uri = '';

        if ($this->scheme !== '') {
            $uri .= $this->scheme . ':';
        }

        $authority = $this->getAuthority();

        if ($authority !== '') {
            $uri .= '//' . $authority;
        }

        $path = $this->path;

        if ($path !== '') {
            if ($path[0] !== '/') {
                if ($authority !== '') {
                    $path = '/' . $path;
                }
            }

            $uri .= $path;
        }

        if ($this->query !== '') {
            $uri .= '?' . $this->query;
        }

        if ($this->fragment !== '') {
            $uri .= '#' . $this->fragment;
        }

        return $uri;
    }

    /**
     * Check if port is standard for the scheme
     *
     * @return bool True if standard port
     */
    private function isStandardPort(): bool
    {
        if ($this->port === null) {
            return true;
        }

        return isset(self::SCHEMES[$this->scheme])
            && self::SCHEMES[$this->scheme] === $this->port;
    }

    /**
     * Filter path to ensure it's properly encoded
     *
     * @param string $path Path to filter
     * @return string Filtered path
     */
    private function filterPath(string $path): string
    {
        // Remove double slashes except at the start for protocol-relative URIs
        return preg_replace('#/+#', '/', $path) ?? $path;
    }
}
