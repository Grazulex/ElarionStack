<?php

declare(strict_types=1);

namespace Elarion\OpenAPI\Schema;

use JsonSerializable;

/**
 * OpenAPI Response Object
 *
 * Describes a single response from an API operation.
 * @see https://spec.openapis.org/oas/v3.1.0#response-object
 */
final class Response implements JsonSerializable
{
    /**
     * @param string $description Response description (REQUIRED)
     * @param array<string, MediaType> $content Response content by media type
     * @param array<string, Header> $headers Response headers
     */
    public function __construct(
        private string $description,
        private array $content = [],
        private array $headers = []
    ) {
    }

    /**
     * Create a JSON response
     */
    public static function json(string $description, Schema $schema): self
    {
        return new self(
            description: $description,
            content: [
                'application/json' => new MediaType($schema),
            ]
        );
    }

    /**
     * Create a JSON:API response
     */
    public static function jsonApi(string $description, Schema $schema): self
    {
        return new self(
            description: $description,
            content: [
                'application/vnd.api+json' => new MediaType($schema),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        $data = ['description' => $this->description];

        if (! empty($this->content)) {
            $data['content'] = $this->content;
        }

        if (! empty($this->headers)) {
            $data['headers'] = $this->headers;
        }

        return $data;
    }
}

/**
 * Header Object
 */
final class Header implements JsonSerializable
{
    public function __construct(
        private ?string $description = null,
        private ?Schema $schema = null
    ) {
    }

    public function jsonSerialize(): array
    {
        $data = [];

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->schema !== null) {
            $data['schema'] = $this->schema;
        }

        return $data;
    }
}
