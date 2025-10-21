<?php

declare(strict_types=1);

namespace Elarion\Config;

/**
 * Dot notation parser for accessing nested array values
 *
 * Following SRP - only responsible for dot notation operations.
 * Handles keys like "app.name", "database.connections.mysql.host"
 */
final class DotNotationParser
{
    /**
     * Parse a dot-notated key into array segments
     *
     * @param string $key Dot-notated key
     * @return array<int, string> Array of key segments
     */
    public function parse(string $key): array
    {
        return explode('.', $key);
    }

    /**
     * Get a value from an array using dot notation
     *
     * @param array<string, mixed> $data The data array
     * @param string $key Dot-notated key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed The value or default
     */
    public function get(array $data, string $key, mixed $default = null): mixed
    {
        // Direct access for non-nested keys
        if (isset($data[$key])) {
            return $data[$key];
        }

        // No dots? Return default
        if (! str_contains($key, '.')) {
            return $default;
        }

        // Navigate through nested arrays
        $segments = $this->parse($key);
        $current = $data;

        foreach ($segments as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return $default;
            }

            $current = $current[$segment];
        }

        return $current;
    }

    /**
     * Set a value in an array using dot notation
     *
     * @param array<string, mixed> $data The data array (passed by reference)
     * @param string $key Dot-notated key
     * @param mixed $value Value to set
     */
    public function set(array &$data, string $key, mixed $value): void
    {
        // Direct assignment for non-nested keys
        if (! str_contains($key, '.')) {
            $data[$key] = $value;

            return;
        }

        // Navigate and create nested structure
        $segments = $this->parse($key);
        $current = &$data;

        foreach ($segments as $i => $segment) {
            // Last segment? Set the value
            if ($i === count($segments) - 1) {
                $current[$segment] = $value;

                return;
            }

            // Create intermediate array if doesn't exist
            if (! isset($current[$segment]) || ! is_array($current[$segment])) {
                $current[$segment] = [];
            }

            $current = &$current[$segment];
        }
    }

    /**
     * Check if a key exists in an array using dot notation
     *
     * @param array<string, mixed> $data The data array
     * @param string $key Dot-notated key
     */
    public function has(array $data, string $key): bool
    {
        // Direct check for non-nested keys
        if (array_key_exists($key, $data)) {
            return true;
        }

        // No dots? Doesn't exist
        if (! str_contains($key, '.')) {
            return false;
        }

        // Navigate through nested arrays
        $segments = $this->parse($key);
        $current = $data;

        foreach ($segments as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return false;
            }

            $current = $current[$segment];
        }

        return true;
    }
}
