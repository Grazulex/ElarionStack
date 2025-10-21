<?php

declare(strict_types=1);

namespace Elarion\OpenAPI\Schema;

use JsonSerializable;

/**
 * OpenAPI Schema Object
 *
 * Represents a schema definition in OpenAPI 3.1 (compatible with JSON Schema Draft 2020-12).
 * @see https://spec.openapis.org/oas/v3.1.0#schema-object
 */
final class Schema implements JsonSerializable
{
    /**
     * @param string|null $type Schema type (string, number, integer, boolean, array, object)
     * @param string|null $format Format modifier (e.g., date-time, email, uuid)
     * @param string|null $description Human-readable description
     * @param mixed $default Default value
     * @param array<string> $required Required properties (for object type)
     * @param array<string, Schema> $properties Object properties
     * @param Schema|null $items Array items schema
     * @param array<mixed> $enum Enumeration of values
     * @param string|null $ref Reference to another schema ($ref)
     * @param int|float|null $minimum Minimum value (inclusive)
     * @param int|float|null $maximum Maximum value (inclusive)
     * @param int|null $minLength Minimum string length
     * @param int|null $maxLength Maximum string length
     * @param int|null $minItems Minimum array items
     * @param int|null $maxItems Maximum array items
     * @param string|null $pattern Regex pattern for strings
     * @param bool|null $nullable Whether the value can be null
     * @param array<string, mixed> $additionalProperties Additional properties for extensibility
     */
    public function __construct(
        private ?string $type = null,
        private ?string $format = null,
        private ?string $description = null,
        private mixed $default = null,
        private array $required = [],
        private array $properties = [],
        private ?Schema $items = null,
        private array $enum = [],
        private ?string $ref = null,
        private int|float|null $minimum = null,
        private int|float|null $maximum = null,
        private ?int $minLength = null,
        private ?int $maxLength = null,
        private ?int $minItems = null,
        private ?int $maxItems = null,
        private ?string $pattern = null,
        private ?bool $nullable = null,
        private array $additionalProperties = []
    ) {
    }

    /**
     * Create a string schema
     */
    public static function string(
        ?string $format = null,
        ?int $minLength = null,
        ?int $maxLength = null,
        ?string $pattern = null
    ): self {
        return new self(
            type: 'string',
            format: $format,
            minLength: $minLength,
            maxLength: $maxLength,
            pattern: $pattern
        );
    }

    /**
     * Create an integer schema
     */
    public static function integer(?int $minimum = null, ?int $maximum = null): self
    {
        return new self(type: 'integer', minimum: $minimum, maximum: $maximum);
    }

    /**
     * Create a number schema
     */
    public static function number(int|float|null $minimum = null, int|float|null $maximum = null): self
    {
        return new self(type: 'number', minimum: $minimum, maximum: $maximum);
    }

    /**
     * Create a boolean schema
     */
    public static function boolean(): self
    {
        return new self(type: 'boolean');
    }

    /**
     * Create an array schema
     */
    public static function array(Schema $items, ?int $minItems = null, ?int $maxItems = null): self
    {
        return new self(type: 'array', items: $items, minItems: $minItems, maxItems: $maxItems);
    }

    /**
     * Create an object schema
     *
     * @param array<string, Schema> $properties
     * @param array<string> $required
     */
    public static function object(array $properties = [], array $required = []): self
    {
        return new self(type: 'object', properties: $properties, required: $required);
    }

    /**
     * Create a reference to another schema
     */
    public static function ref(string $ref): self
    {
        return new self(ref: $ref);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        $data = [];

        if ($this->ref !== null) {
            return ['$ref' => $this->ref];
        }

        if ($this->type !== null) {
            $data['type'] = $this->type;
        }

        if ($this->format !== null) {
            $data['format'] = $this->format;
        }

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->default !== null) {
            $data['default'] = $this->default;
        }

        if (! empty($this->required)) {
            $data['required'] = $this->required;
        }

        if (! empty($this->properties)) {
            $data['properties'] = $this->properties;
        }

        if ($this->items !== null) {
            $data['items'] = $this->items;
        }

        if (! empty($this->enum)) {
            $data['enum'] = $this->enum;
        }

        if ($this->minimum !== null) {
            $data['minimum'] = $this->minimum;
        }

        if ($this->maximum !== null) {
            $data['maximum'] = $this->maximum;
        }

        if ($this->minLength !== null) {
            $data['minLength'] = $this->minLength;
        }

        if ($this->maxLength !== null) {
            $data['maxLength'] = $this->maxLength;
        }

        if ($this->minItems !== null) {
            $data['minItems'] = $this->minItems;
        }

        if ($this->maxItems !== null) {
            $data['maxItems'] = $this->maxItems;
        }

        if ($this->pattern !== null) {
            $data['pattern'] = $this->pattern;
        }

        if ($this->nullable !== null) {
            $data['nullable'] = $this->nullable;
        }

        foreach ($this->additionalProperties as $key => $value) {
            $data[$key] = $value;
        }

        return $data;
    }
}
