<?php

declare(strict_types=1);

namespace Elarion\Http\Message;

/**
 * Header bag for case-insensitive header management
 *
 * Following SRP - only handles header storage and retrieval.
 */
final class HeaderBag
{
    /**
     * Headers storage (normalized name => [original name, values])
     *
     * @var array<string, array{0: string, 1: array<int, string>}>
     */
    private array $headers = [];

    /**
     * Create header bag
     *
     * @param array<string, string|array<int, string>> $headers Initial headers
     */
    public function __construct(array $headers = [])
    {
        foreach ($headers as $name => $value) {
            $this->set($name, $value);
        }
    }

    /**
     * Set header value
     *
     * @param string $name Header name
     * @param string|array<int, string> $value Header value(s)
     * @return void
     */
    public function set(string $name, string|array $value): void
    {
        $normalized = $this->normalize($name);
        $values = is_array($value) ? $value : [$value];

        $this->headers[$normalized] = [$name, $values];
    }

    /**
     * Add header value(s)
     *
     * @param string $name Header name
     * @param string|array<int, string> $value Header value(s) to add
     * @return void
     */
    public function add(string $name, string|array $value): void
    {
        $normalized = $this->normalize($name);
        $values = is_array($value) ? $value : [$value];

        if (! isset($this->headers[$normalized])) {
            $this->set($name, $values);

            return;
        }

        [, $existing] = $this->headers[$normalized];
        $this->headers[$normalized][1] = array_merge($existing, $values);
    }

    /**
     * Get header value(s)
     *
     * @param string $name Header name
     * @return array<int, string> Header values
     */
    public function get(string $name): array
    {
        $normalized = $this->normalize($name);

        return $this->headers[$normalized][1] ?? [];
    }

    /**
     * Get header line (comma-separated values)
     *
     * @param string $name Header name
     * @return string Header line
     */
    public function getLine(string $name): string
    {
        return implode(', ', $this->get($name));
    }

    /**
     * Check if header exists
     *
     * @param string $name Header name
     * @return bool True if exists
     */
    public function has(string $name): bool
    {
        return isset($this->headers[$this->normalize($name)]);
    }

    /**
     * Remove header
     *
     * @param string $name Header name
     * @return void
     */
    public function remove(string $name): void
    {
        unset($this->headers[$this->normalize($name)]);
    }

    /**
     * Get all headers
     *
     * @return array<string, array<int, string>> All headers with original names
     */
    public function all(): array
    {
        $headers = [];

        foreach ($this->headers as [$originalName, $values]) {
            $headers[$originalName] = $values;
        }

        return $headers;
    }

    /**
     * Get header keys
     *
     * @return array<int, string> Header names (original case)
     */
    public function keys(): array
    {
        return array_map(
            fn ($header) => $header[0],
            array_values($this->headers)
        );
    }

    /**
     * Normalize header name for case-insensitive comparison
     *
     * @param string $name Header name
     * @return string Normalized name
     */
    private function normalize(string $name): string
    {
        return strtolower($name);
    }
}
