<?php

declare(strict_types=1);

namespace Elarion\Http\Resources\JsonApi;

use Elarion\Http\Message\Response;
use Elarion\Http\Resources\Resource;
use Psr\Http\Message\ServerRequestInterface;

/**
 * JSON:API Resource
 *
 * Implements JSON:API specification v1.1 for resource formatting.
 * @see https://jsonapi.org/format/
 */
abstract class JsonApiResource extends Resource
{
    /**
     * Included resources to add to the compound document
     *
     * @var array<JsonApiResource>
     */
    protected array $included = [];

    /**
     * JSON:API version
     *
     * @var string
     */
    protected string $jsonApiVersion = '1.1';

    /**
     * Get the resource type
     *
     * @return string Resource type (e.g., 'articles', 'users')
     */
    abstract public function type(): string;

    /**
     * Get the resource ID
     *
     * @return string|int Resource identifier
     */
    abstract public function id(): string|int;

    /**
     * Get resource attributes
     *
     * Override this to customize attributes.
     * By default, returns toArray() result.
     *
     * @param ServerRequestInterface $request Request instance
     * @return array<string, mixed> Resource attributes
     */
    public function attributes(ServerRequestInterface $request): array
    {
        return $this->toArray($request);
    }

    /**
     * Get resource relationships
     *
     * Override this to define relationships.
     *
     * @param ServerRequestInterface $request Request instance
     * @return array<string, array<string, mixed>> Relationships
     */
    public function relationships(ServerRequestInterface $request): array
    {
        return [];
    }

    /**
     * Get resource links
     *
     * Override this to add resource-level links.
     *
     * @param ServerRequestInterface $request Request instance
     * @return array<string, string> Links
     */
    public function links(ServerRequestInterface $request): array
    {
        return [];
    }

    /**
     * Get resource meta
     *
     * Override this to add resource-level meta data.
     *
     * @param ServerRequestInterface $request Request instance
     * @return array<string, mixed> Meta data
     */
    public function meta(ServerRequestInterface $request): array
    {
        return [];
    }

    /**
     * Convert resource to JSON:API format
     *
     * @param ServerRequestInterface $request Request instance
     * @return array<string, mixed> JSON:API formatted data
     */
    public function toJsonApi(ServerRequestInterface $request): array
    {
        $data = [
            'type' => $this->type(),
            'id' => (string) $this->id(),
        ];

        // Add attributes
        $attributes = $this->attributes($request);
        if (! empty($attributes)) {
            $data['attributes'] = $attributes;
        }

        // Add relationships
        $relationships = $this->relationships($request);
        if (! empty($relationships)) {
            $data['relationships'] = $relationships;
        }

        // Add links
        $links = $this->links($request);
        if (! empty($links)) {
            $data['links'] = $links;
        }

        // Add meta
        $meta = $this->meta($request);
        if (! empty($meta)) {
            $data['meta'] = $meta;
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ServerRequestInterface $request): array
    {
        $document = [
            'data' => $this->toJsonApi($request),
            'jsonapi' => [
                'version' => $this->jsonApiVersion,
            ],
        ];

        // Add included resources
        if (! empty($this->included)) {
            $document['included'] = $this->resolveIncluded($request);
        }

        // Add top-level meta
        if (! empty($this->additional)) {
            foreach ($this->additional as $key => $value) {
                $document[$key] = $value;
            }
        }

        return $document;
    }

    /**
     * {@inheritdoc}
     */
    public function toResponse(ServerRequestInterface $request, int $status = 200): Response
    {
        $data = $this->resolve($request);

        return Response::json($data, $status, [
            'Content-Type' => 'application/vnd.api+json',
        ]);
    }

    /**
     * Get included resources
     *
     * @return array<JsonApiResource> Included resources
     */
    public function getIncluded(): array
    {
        return $this->included;
    }

    /**
     * Include related resources
     *
     * @param JsonApiResource|array<JsonApiResource> $resources Resources to include
     * @return $this
     */
    public function include(JsonApiResource|array $resources): self
    {
        if (! is_array($resources)) {
            $resources = [$resources];
        }

        foreach ($resources as $resource) {
            $this->included[] = $resource;
        }

        return $this;
    }

    /**
     * Resolve included resources
     *
     * @param ServerRequestInterface $request Request instance
     * @return array<array<string, mixed>> Included resources
     */
    protected function resolveIncluded(ServerRequestInterface $request): array
    {
        $included = [];
        $seen = [];

        foreach ($this->included as $resource) {
            $key = $resource->type() . ':' . $resource->id();

            if (! isset($seen[$key])) {
                $included[] = $resource->toJsonApi($request);
                $seen[$key] = true;

                // Recursively add nested includes
                if (! empty($resource->included)) {
                    foreach ($resource->resolveIncluded($request) as $nested) {
                        $nestedKey = $nested['type'] . ':' . $nested['id'];
                        if (! isset($seen[$nestedKey])) {
                            $included[] = $nested;
                            $seen[$nestedKey] = true;
                        }
                    }
                }
            }
        }

        return $included;
    }

    /**
     * Create a relationship
     *
     * @param string $type Relationship type
     * @param JsonApiResource|array<JsonApiResource>|null $data Related resource(s)
     * @param array<string, string> $links Relationship links
     * @param array<string, mixed> $meta Relationship meta
     * @return array<string, mixed> Relationship object
     */
    protected function relationship(
        string $type,
        JsonApiResource|array|null $data = null,
        array $links = [],
        array $meta = []
    ): array {
        $relationship = [];

        // Add links
        if (! empty($links)) {
            $relationship['links'] = $links;
        }

        // Add data
        if ($data !== null) {
            if (is_array($data)) {
                $relationship['data'] = array_map(
                    fn (JsonApiResource $r) => $this->resourceIdentifier($r),
                    $data
                );
            } else {
                $relationship['data'] = $this->resourceIdentifier($data);
            }
        }

        // Add meta
        if (! empty($meta)) {
            $relationship['meta'] = $meta;
        }

        return $relationship;
    }

    /**
     * Create a resource identifier
     *
     * @param JsonApiResource $resource Resource
     * @return array{type: string, id: string} Resource identifier
     */
    protected function resourceIdentifier(JsonApiResource $resource): array
    {
        return [
            'type' => $resource->type(),
            'id' => (string) $resource->id(),
        ];
    }
}
