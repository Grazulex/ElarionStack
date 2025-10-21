<?php

declare(strict_types=1);

namespace Elarion\OpenAPI\Schema;

use JsonSerializable;

/**
 * OpenAPI Media Type Object
 *
 * Provides schema and examples for the media type identified by its key.
 * @see https://spec.openapis.org/oas/v3.1.0#media-type-object
 */
final class MediaType implements JsonSerializable
{
    /**
     * @param Schema|null $schema Schema defining the content
     * @param mixed $example Example of the media type
     * @param array<string, mixed> $examples Examples of the media type
     */
    public function __construct(
        private ?Schema $schema = null,
        private mixed $example = null,
        private array $examples = []
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        $data = [];

        if ($this->schema !== null) {
            $data['schema'] = $this->schema;
        }

        if ($this->example !== null) {
            $data['example'] = $this->example;
        }

        if (! empty($this->examples)) {
            $data['examples'] = $this->examples;
        }

        return $data;
    }
}
