<?php

declare(strict_types=1);

namespace Elarion\OpenAPI\Schema;

use JsonSerializable;

/**
 * OpenAPI Request Body Object
 *
 * Describes a single request body.
 * @see https://spec.openapis.org/oas/v3.1.0#request-body-object
 */
final class RequestBody implements JsonSerializable
{
    /**
     * @param array<string, MediaType> $content Content by media type (REQUIRED)
     * @param string|null $description Request body description
     * @param bool $required Whether the request body is required
     */
    public function __construct(
        private array $content,
        private ?string $description = null,
        private bool $required = false
    ) {
    }

    /**
     * Create a JSON request body
     */
    public static function json(Schema $schema, bool $required = true, ?string $description = null): self
    {
        return new self(
            content: [
                'application/json' => new MediaType($schema),
            ],
            description: $description,
            required: $required
        );
    }

    /**
     * Create a JSON:API request body
     */
    public static function jsonApi(Schema $schema, bool $required = true, ?string $description = null): self
    {
        return new self(
            content: [
                'application/vnd.api+json' => new MediaType($schema),
            ],
            description: $description,
            required: $required
        );
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        $data = ['content' => $this->content];

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->required) {
            $data['required'] = true;
        }

        return $data;
    }
}
