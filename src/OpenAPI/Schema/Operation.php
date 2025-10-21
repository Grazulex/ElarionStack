<?php

declare(strict_types=1);

namespace Elarion\OpenAPI\Schema;

use JsonSerializable;

/**
 * OpenAPI Operation Object
 *
 * Describes a single API operation on a path.
 * @see https://spec.openapis.org/oas/v3.1.0#operation-object
 */
final class Operation implements JsonSerializable
{
    /**
     * @param array<string> $tags Tags for API documentation control
     * @param string|null $summary Short summary
     * @param string|null $description Long description
     * @param string|null $operationId Unique identifier for the operation
     * @param array<Parameter> $parameters Operation parameters
     * @param RequestBody|null $requestBody Request body
     * @param array<string, Response> $responses Responses (REQUIRED)
     * @param bool $deprecated Whether the operation is deprecated
     */
    public function __construct(
        private array $tags = [],
        private ?string $summary = null,
        private ?string $description = null,
        private ?string $operationId = null,
        private array $parameters = [],
        private ?RequestBody $requestBody = null,
        private array $responses = [],
        private bool $deprecated = false
    ) {
    }

    /**
     * Add a tag
     */
    public function addTag(string $tag): self
    {
        $this->tags[] = $tag;

        return $this;
    }

    /**
     * Add a parameter
     */
    public function addParameter(Parameter $parameter): self
    {
        $this->parameters[] = $parameter;

        return $this;
    }

    /**
     * Add a response
     */
    public function addResponse(string $statusCode, Response $response): self
    {
        $this->responses[$statusCode] = $response;

        return $this;
    }

    /**
     * Set request body
     */
    public function setRequestBody(RequestBody $requestBody): self
    {
        $this->requestBody = $requestBody;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        $data = [];

        if (! empty($this->tags)) {
            $data['tags'] = $this->tags;
        }

        if ($this->summary !== null) {
            $data['summary'] = $this->summary;
        }

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->operationId !== null) {
            $data['operationId'] = $this->operationId;
        }

        if (! empty($this->parameters)) {
            $data['parameters'] = $this->parameters;
        }

        if ($this->requestBody !== null) {
            $data['requestBody'] = $this->requestBody;
        }

        if (! empty($this->responses)) {
            $data['responses'] = $this->responses;
        }

        if ($this->deprecated) {
            $data['deprecated'] = true;
        }

        return $data;
    }
}
