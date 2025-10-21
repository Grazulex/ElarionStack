<?php

declare(strict_types=1);

namespace Elarion\OpenAPI\Schema;

use JsonSerializable;

/**
 * OpenAPI Document (Root Object)
 *
 * The root object of the OpenAPI document.
 * @see https://spec.openapis.org/oas/v3.1.0#openapi-object
 */
final class OpenApiDocument implements JsonSerializable
{
    /**
     * @param string $openapi OpenAPI version (e.g., "3.1.0")
     * @param Info $info API information (REQUIRED)
     * @param array<Server> $servers API servers
     * @param array<string, PathItem> $paths API paths (REQUIRED)
     * @param Components|null $components Reusable components
     * @param array<Tag> $tags Tags for API documentation organization
     */
    public function __construct(
        private string $openapi = '3.1.0',
        private ?Info $info = null,
        private array $servers = [],
        private array $paths = [],
        private ?Components $components = null,
        private array $tags = []
    ) {
        if ($this->info === null) {
            $this->info = new Info('API', '1.0.0');
        }
    }

    /**
     * Set API info
     */
    public function setInfo(Info $info): self
    {
        $this->info = $info;

        return $this;
    }

    /**
     * Add a server
     */
    public function addServer(Server $server): self
    {
        $this->servers[] = $server;

        return $this;
    }

    /**
     * Add a path
     */
    public function addPath(string $path, PathItem $pathItem): self
    {
        $this->paths[$path] = $pathItem;

        return $this;
    }

    /**
     * Set components
     */
    public function setComponents(Components $components): self
    {
        $this->components = $components;

        return $this;
    }

    /**
     * Add a tag
     */
    public function addTag(Tag $tag): self
    {
        $this->tags[] = $tag;

        return $this;
    }

    /**
     * Convert to JSON string
     */
    public function toJson(int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES): string
    {
        return json_encode($this, $flags);
    }

    /**
     * Convert to YAML string
     */
    public function toYaml(): string
    {
        // Simple YAML conversion for basic structure
        // For production, consider using symfony/yaml
        return $this->arrayToYaml($this->jsonSerialize());
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        $data = [
            'openapi' => $this->openapi,
            'info' => $this->info,
        ];

        if (! empty($this->servers)) {
            $data['servers'] = $this->servers;
        }

        if (! empty($this->paths)) {
            $data['paths'] = $this->paths;
        }

        if ($this->components !== null && ! empty($this->components->jsonSerialize())) {
            $data['components'] = $this->components;
        }

        if (! empty($this->tags)) {
            $data['tags'] = $this->tags;
        }

        return $data;
    }

    /**
     * Simple array to YAML converter
     *
     * @param array<mixed> $array
     * @param int $indent
     * @return string
     */
    private function arrayToYaml(array $array, int $indent = 0): string
    {
        $yaml = '';
        $indentStr = str_repeat('  ', $indent);

        foreach ($array as $key => $value) {
            // Convert JsonSerializable objects to arrays
            if ($value instanceof \JsonSerializable) {
                $value = $value->jsonSerialize();
            }

            if (is_array($value) && ! empty($value)) {
                // Check if it's a sequential array
                if (array_keys($value) === range(0, count($value) - 1)) {
                    $yaml .= "{$indentStr}{$key}:\n";
                    foreach ($value as $item) {
                        if ($item instanceof \JsonSerializable) {
                            $item = $item->jsonSerialize();
                        }
                        if (is_array($item)) {
                            $yaml .= "{$indentStr}- \n";
                            $yaml .= $this->arrayToYaml($item, $indent + 1);
                        } else {
                            $yaml .= "{$indentStr}- " . $this->formatYamlValue($item) . "\n";
                        }
                    }
                } else {
                    $yaml .= "{$indentStr}{$key}:\n";
                    $yaml .= $this->arrayToYaml($value, $indent + 1);
                }
            } else {
                $yaml .= "{$indentStr}{$key}: " . $this->formatYamlValue($value) . "\n";
            }
        }

        return $yaml;
    }

    /**
     * Format a value for YAML output
     */
    private function formatYamlValue(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_string($value) && (str_contains($value, ':') || str_contains($value, '#'))) {
            return "'" . str_replace("'", "''", $value) . "'";
        }

        return (string) $value;
    }
}

/**
 * Tag Object
 */
final class Tag implements JsonSerializable
{
    public function __construct(
        private string $name,
        private ?string $description = null
    ) {
    }

    public function jsonSerialize(): array
    {
        $data = ['name' => $this->name];

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        return $data;
    }
}
