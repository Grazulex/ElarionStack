<?php

declare(strict_types=1);

namespace Elarion\Http\Message;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * PSR-7 Response implementation
 *
 * Represents an outgoing HTTP response.
 * Following SRP - handles response-specific data.
 */
final class Response extends Message implements ResponseInterface
{
    /**
     * HTTP status codes and reason phrases
     *
     * @var array<int, string>
     */
    private const REASON_PHRASES = [
        // 1xx Informational
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',

        // 2xx Success
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',

        // 3xx Redirection
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',

        // 4xx Client Error
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Content Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Content',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',

        // 5xx Server Error
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /**
     * HTTP status code
     */
    private int $statusCode;

    /**
     * HTTP reason phrase
     */
    private string $reasonPhrase;

    /**
     * Create response
     *
     * @param int $statusCode HTTP status code
     * @param array<string, string|array<int, string>> $headers Response headers
     * @param StreamInterface|string|null $body Response body
     * @param string $protocolVersion HTTP protocol version
     * @param string|null $reasonPhrase Custom reason phrase
     */
    public function __construct(
        int $statusCode = 200,
        array $headers = [],
        StreamInterface|string|null $body = null,
        string $protocolVersion = '1.1',
        ?string $reasonPhrase = null
    ) {
        $this->statusCode = $statusCode;
        $this->reasonPhrase = $reasonPhrase ?? self::REASON_PHRASES[$statusCode] ?? '';

        // Convert string body to Stream
        if (is_string($body)) {
            $body = Stream::fromString($body);
        }

        parent::__construct($headers, $body, $protocolVersion);
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * {@inheritdoc}
     */
    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        if ($code < 100 || $code > 599) {
            throw new \InvalidArgumentException(
                sprintf('Invalid status code: %d. Must be between 100 and 599', $code)
            );
        }

        if ($code === $this->statusCode && $reasonPhrase === $this->reasonPhrase) {
            return $this;
        }

        $clone = clone $this;
        $clone->statusCode = $code;
        $clone->reasonPhrase = $reasonPhrase !== ''
            ? $reasonPhrase
            : (self::REASON_PHRASES[$code] ?? '');

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    /**
     * Create JSON response
     *
     * @param mixed $data Data to encode as JSON
     * @param int $statusCode HTTP status code
     * @param array<string, string|array<int, string>> $headers Additional headers
     * @param int $flags JSON encode flags
     * @return self JSON response
     */
    public static function json(
        mixed $data,
        int $statusCode = 200,
        array $headers = [],
        int $flags = JSON_THROW_ON_ERROR
    ): self {
        $json = json_encode($data, $flags);

        $headers['Content-Type'] = 'application/json';

        return new self($statusCode, $headers, $json);
    }

    /**
     * Create HTML response
     *
     * @param string $html HTML content
     * @param int $statusCode HTTP status code
     * @param array<string, string|array<int, string>> $headers Additional headers
     * @return self HTML response
     */
    public static function html(
        string $html,
        int $statusCode = 200,
        array $headers = []
    ): self {
        $headers['Content-Type'] = 'text/html; charset=utf-8';

        return new self($statusCode, $headers, $html);
    }

    /**
     * Create redirect response
     *
     * @param string $uri Redirect URI
     * @param int $statusCode HTTP status code (301, 302, 303, 307, 308)
     * @param array<string, string|array<int, string>> $headers Additional headers
     * @return self Redirect response
     */
    public static function redirect(
        string $uri,
        int $statusCode = 302,
        array $headers = []
    ): self {
        $headers['Location'] = $uri;

        return new self($statusCode, $headers);
    }
}
