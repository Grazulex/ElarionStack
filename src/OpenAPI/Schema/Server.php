<?php

declare(strict_types=1);

namespace Elarion\OpenAPI\Schema;

use JsonSerializable;

/**
 * OpenAPI Server Object
 *
 * Represents a server for the API.
 * @see https://spec.openapis.org/oas/v3.1.0#server-object
 */
final class Server implements JsonSerializable
{
    /**
     * @param string $url Server URL
     * @param string|null $description Server description
     * @param array<string, ServerVariable> $variables Server variables
     */
    public function __construct(
        private string $url,
        private ?string $description = null,
        private array $variables = []
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        $data = ['url' => $this->url];

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if (! empty($this->variables)) {
            $data['variables'] = $this->variables;
        }

        return $data;
    }
}

/**
 * Server Variable
 */
final class ServerVariable implements JsonSerializable
{
    /**
     * @param string $default Default value
     * @param array<string> $enum Enumeration of values
     * @param string|null $description Description
     */
    public function __construct(
        private string $default,
        private array $enum = [],
        private ?string $description = null
    ) {
    }

    public function jsonSerialize(): array
    {
        $data = ['default' => $this->default];

        if (! empty($this->enum)) {
            $data['enum'] = $this->enum;
        }

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        return $data;
    }
}
