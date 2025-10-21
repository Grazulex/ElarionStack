<?php

declare(strict_types=1);

namespace Elarion\Routing;

/**
 * HTTP method enum
 *
 * Type-safe HTTP method representation using PHP 8.5 enums.
 * Provides case-insensitive matching and validation.
 */
enum HttpMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
    case OPTIONS = 'OPTIONS';
    case HEAD = 'HEAD';

    /**
     * Create from string with case-insensitive matching
     *
     * @param string $method HTTP method string
     * @return self HttpMethod enum
     * @throws \ValueError If method is invalid
     */
    public static function fromString(string $method): self
    {
        $normalized = strtoupper($method);

        return self::tryFrom($normalized) ?? throw new \ValueError(
            sprintf('Invalid HTTP method: %s', $method)
        );
    }

    /**
     * Try to create from string with case-insensitive matching
     *
     * @param string $method HTTP method string
     * @return self|null HttpMethod enum or null
     */
    public static function tryFromString(string $method): ?self
    {
        return self::tryFrom(strtoupper($method));
    }

    /**
     * Check if method is safe (idempotent and cacheable)
     *
     * @return bool True for GET, HEAD, OPTIONS
     */
    public function isSafe(): bool
    {
        return match ($this) {
            self::GET, self::HEAD, self::OPTIONS => true,
            default => false,
        };
    }

    /**
     * Check if method is idempotent
     *
     * @return bool True for GET, PUT, DELETE, HEAD, OPTIONS
     */
    public function isIdempotent(): bool
    {
        return match ($this) {
            self::GET, self::PUT, self::DELETE, self::HEAD, self::OPTIONS => true,
            self::POST, self::PATCH => false,
        };
    }

    /**
     * Get all HTTP method values
     *
     * @return array<int, string> All method strings
     */
    public static function values(): array
    {
        return array_map(fn (self $method) => $method->value, self::cases());
    }
}
