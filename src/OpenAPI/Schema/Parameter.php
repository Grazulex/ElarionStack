<?php

declare(strict_types=1);

namespace Elarion\OpenAPI\Schema;

use JsonSerializable;

/**
 * OpenAPI Parameter Object
 *
 * Describes a single operation parameter.
 * @see https://spec.openapis.org/oas/v3.1.0#parameter-object
 */
final class Parameter implements JsonSerializable
{
    /**
     * @param string $name Parameter name
     * @param string $in Location: "query", "header", "path", "cookie"
     * @param bool $required Whether parameter is required
     * @param string|null $description Parameter description
     * @param Schema|null $schema Parameter schema
     * @param mixed $example Example value
     * @param bool $deprecated Whether parameter is deprecated
     */
    public function __construct(
        private string $name,
        private string $in,
        private bool $required = false,
        private ?string $description = null,
        private ?Schema $schema = null,
        private mixed $example = null,
        private bool $deprecated = false
    ) {
    }

    /**
     * Create a path parameter
     */
    public static function path(
        string $name,
        ?Schema $schema = null,
        ?string $description = null
    ): self {
        return new self(
            name: $name,
            in: 'path',
            required: true, // Path parameters are always required
            schema: $schema,
            description: $description
        );
    }

    /**
     * Create a query parameter
     */
    public static function query(
        string $name,
        ?Schema $schema = null,
        bool $required = false,
        ?string $description = null
    ): self {
        return new self(
            name: $name,
            in: 'query',
            required: $required,
            schema: $schema,
            description: $description
        );
    }

    /**
     * Create a header parameter
     */
    public static function header(
        string $name,
        ?Schema $schema = null,
        bool $required = false,
        ?string $description = null
    ): self {
        return new self(
            name: $name,
            in: 'header',
            required: $required,
            schema: $schema,
            description: $description
        );
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        $data = [
            'name' => $this->name,
            'in' => $this->in,
            'required' => $this->required,
        ];

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->schema !== null) {
            $data['schema'] = $this->schema;
        }

        if ($this->example !== null) {
            $data['example'] = $this->example;
        }

        if ($this->deprecated) {
            $data['deprecated'] = true;
        }

        return $data;
    }
}
