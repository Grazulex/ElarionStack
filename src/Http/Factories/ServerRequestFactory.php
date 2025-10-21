<?php

declare(strict_types=1);

namespace Elarion\Http\Factories;

use Elarion\Http\Message\ServerRequest;
use Elarion\Http\Message\Stream;
use Elarion\Http\Message\UploadedFile;
use Elarion\Http\Message\Uri;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * PSR-17 Server request factory
 *
 * Creates ServerRequest instances from superglobals.
 * Following Factory pattern and SRP.
 */
final class ServerRequestFactory implements ServerRequestFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        if (is_string($uri)) {
            $uri = Uri::fromString($uri);
        }

        return new ServerRequest($method, $uri, [], null, '1.1', $serverParams);
    }

    /**
     * Create server request from PHP globals
     *
     * @return ServerRequestInterface Server request
     */
    public function createServerRequestFromGlobals(): ServerRequestInterface
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $this->createUriFromGlobals();
        $headers = $this->extractHeaders($_SERVER);
        $body = Stream::fromFile('php://input', 'r');
        $protocol = $this->getProtocolVersion($_SERVER);

        $request = new ServerRequest($method, $uri, $headers, $body, $protocol, $_SERVER);

        return $request
            ->withCookieParams($_COOKIE)
            ->withQueryParams($_GET)
            ->withParsedBody($_POST)
            ->withUploadedFiles($this->normalizeUploadedFiles($_FILES));
    }

    /**
     * Create URI from globals
     *
     * @param array<string, mixed> $server Server parameters
     * @return Uri URI instance
     */
    private function createUriFromGlobals(array $server = []): Uri
    {
        $server = $server ?: $_SERVER;

        $scheme = ! empty($server['HTTPS']) && $server['HTTPS'] !== 'off' ? 'https' : 'http';
        $host = $server['HTTP_HOST'] ?? $server['SERVER_NAME'] ?? 'localhost';
        $port = isset($server['SERVER_PORT']) ? (int) $server['SERVER_PORT'] : null;
        $path = $this->getRequestPath($server);
        $query = $server['QUERY_STRING'] ?? '';

        // Parse host:port if present
        if (str_contains($host, ':')) {
            [$host, $portStr] = explode(':', $host, 2);
            $port = (int) $portStr;
        }

        return new Uri($scheme, $host, $port, $path, $query);
    }

    /**
     * Get request path from server params
     *
     * @param array<string, mixed> $server Server parameters
     * @return string Request path
     */
    private function getRequestPath(array $server): string
    {
        // Try REQUEST_URI first
        if (isset($server['REQUEST_URI'])) {
            $requestUri = $server['REQUEST_URI'];

            // Remove query string
            if (($pos = strpos($requestUri, '?')) !== false) {
                $requestUri = substr($requestUri, 0, $pos);
            }

            return $requestUri;
        }

        // Fallback to SCRIPT_NAME
        return $server['SCRIPT_NAME'] ?? '/';
    }

    /**
     * Extract headers from server parameters
     *
     * @param array<string, mixed> $server Server parameters
     * @return array<string, string> Headers
     */
    private function extractHeaders(array $server): array
    {
        $headers = [];

        foreach ($server as $key => $value) {
            // HTTP_* headers
            if (str_starts_with($key, 'HTTP_')) {
                $name = $this->normalizeHeaderName(substr($key, 5));
                $headers[$name] = $value;

                continue;
            }

            // CONTENT_* headers
            if (str_starts_with($key, 'CONTENT_')) {
                $name = $this->normalizeHeaderName($key);
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    /**
     * Normalize header name
     *
     * @param string $name Header name
     * @return string Normalized name
     */
    private function normalizeHeaderName(string $name): string
    {
        return str_replace('_', '-', ucwords(strtolower($name), '_'));
    }

    /**
     * Get protocol version from server params
     *
     * @param array<string, mixed> $server Server parameters
     * @return string Protocol version
     */
    private function getProtocolVersion(array $server): string
    {
        if (! isset($server['SERVER_PROTOCOL'])) {
            return '1.1';
        }

        if (! preg_match('#^HTTP/(\d\.\d)$#', $server['SERVER_PROTOCOL'], $matches)) {
            return '1.1';
        }

        return $matches[1];
    }

    /**
     * Normalize uploaded files array
     *
     * @param array<string, mixed> $files $_FILES array
     * @return array<string, UploadedFileInterface> Normalized uploaded files
     */
    private function normalizeUploadedFiles(array $files): array
    {
        $normalized = [];

        foreach ($files as $key => $value) {
            if ($value instanceof UploadedFileInterface) {
                $normalized[$key] = $value;

                continue;
            }

            if (is_array($value) && isset($value['tmp_name'])) {
                $normalized[$key] = $this->createUploadedFile($value);

                continue;
            }

            if (is_array($value)) {
                $normalized[$key] = $this->normalizeUploadedFiles($value);
            }
        }

        return $normalized;
    }

    /**
     * Create uploaded file from $_FILES entry
     *
     * @param array{tmp_name: string, size: int, error: int, name: string, type: string} $file File data
     * @return UploadedFileInterface Uploaded file
     */
    private function createUploadedFile(array $file): UploadedFileInterface
    {
        return new UploadedFile(
            $file['tmp_name'],
            $file['size'] ?? null,
            $file['error'] ?? UPLOAD_ERR_OK,
            $file['name'] ?? null,
            $file['type'] ?? null
        );
    }
}
