<?php

declare(strict_types=1);

namespace Elarion\OpenAPI\Schema;

use JsonSerializable;

/**
 * OpenAPI Components Object
 *
 * Holds a set of reusable objects for different aspects of the OAS.
 * @see https://spec.openapis.org/oas/v3.1.0#components-object
 */
final class Components implements JsonSerializable
{
    /**
     * @param array<string, Schema> $schemas Reusable schemas
     * @param array<string, Response> $responses Reusable responses
     * @param array<string, Parameter> $parameters Reusable parameters
     * @param array<string, RequestBody> $requestBodies Reusable request bodies
     */
    public function __construct(
        private array $schemas = [],
        private array $responses = [],
        private array $parameters = [],
        private array $requestBodies = []
    ) {
    }

    /**
     * Add a schema
     */
    public function addSchema(string $name, Schema $schema): self
    {
        $this->schemas[$name] = $schema;

        return $this;
    }

    /**
     * Add a response
     */
    public function addResponse(string $name, Response $response): self
    {
        $this->responses[$name] = $response;

        return $this;
    }

    /**
     * Add a parameter
     */
    public function addParameter(string $name, Parameter $parameter): self
    {
        $this->parameters[$name] = $parameter;

        return $this;
    }

    /**
     * Add a request body
     */
    public function addRequestBody(string $name, RequestBody $requestBody): self
    {
        $this->requestBodies[$name] = $requestBody;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        $data = [];

        if (! empty($this->schemas)) {
            $data['schemas'] = $this->schemas;
        }

        if (! empty($this->responses)) {
            $data['responses'] = $this->responses;
        }

        if (! empty($this->parameters)) {
            $data['parameters'] = $this->parameters;
        }

        if (! empty($this->requestBodies)) {
            $data['requestBodies'] = $this->requestBodies;
        }

        return $data;
    }
}
