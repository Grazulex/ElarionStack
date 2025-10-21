<?php

declare(strict_types=1);

namespace Elarion\OpenAPI\Generator;

use Elarion\Http\Resources\JsonApi\JsonApiResource;
use Elarion\OpenAPI\Schema\Schema;

/**
 * JSON:API Scanner
 *
 * Generates OpenAPI schemas conforming to JSON:API specification v1.1.
 * @see https://jsonapi.org/format/
 */
final class JsonApiScanner
{
    /**
     * Generate JSON:API document schema
     *
     * Creates an OpenAPI schema for a JSON:API document with data, included, meta, etc.
     *
     * @param Schema|null $resourceSchema Schema for the resource object
     * @param bool $isCollection Whether this is a collection response
     * @return Schema JSON:API document schema
     */
    public function generateDocumentSchema(?Schema $resourceSchema = null, bool $isCollection = false): Schema
    {
        $properties = [];

        // Data field - required
        if ($resourceSchema !== null) {
            $properties['data'] = $isCollection
                ? Schema::array($resourceSchema)
                : $resourceSchema;
        } else {
            // Generic resource object
            $properties['data'] = $isCollection
                ? Schema::array($this->generateResourceObjectSchema())
                : $this->generateResourceObjectSchema();
        }

        // JSON:API version object
        $properties['jsonapi'] = Schema::object([
            'version' => Schema::string(),
            'meta' => Schema::object(),
        ]);

        // Meta - optional top-level metadata
        $properties['meta'] = Schema::object();

        // Links - optional top-level links
        $properties['links'] = Schema::object([
            'self' => Schema::string('uri'),
            'related' => Schema::string('uri'),
            'first' => Schema::string('uri'),
            'last' => Schema::string('uri'),
            'prev' => Schema::string('uri'),
            'next' => Schema::string('uri'),
        ]);

        // Included - optional array of resource objects
        $properties['included'] = Schema::array($this->generateResourceObjectSchema());

        // Errors - for error responses (mutually exclusive with data)
        $properties['errors'] = Schema::array($this->generateErrorObjectSchema());

        return Schema::object($properties, ['data']); // Only data is required
    }

    /**
     * Generate JSON:API resource object schema
     *
     * Creates the schema for a resource object with type, id, attributes, relationships, etc.
     *
     * @param array<string, Schema>|null $attributes Custom attributes schema
     * @param array<string, Schema>|null $relationships Custom relationships schema
     * @return Schema Resource object schema
     */
    public function generateResourceObjectSchema(
        ?array $attributes = null,
        ?array $relationships = null
    ): Schema {
        $properties = [
            'type' => Schema::string(),
            'id' => Schema::string(),
        ];

        // Attributes
        if ($attributes !== null) {
            $properties['attributes'] = Schema::object($attributes);
        } else {
            $properties['attributes'] = Schema::object();
        }

        // Relationships
        if ($relationships !== null) {
            $relationshipSchemas = [];
            foreach ($relationships as $name => $schema) {
                $relationshipSchemas[$name] = $this->generateRelationshipObjectSchema();
            }
            $properties['relationships'] = Schema::object($relationshipSchemas);
        } else {
            $properties['relationships'] = Schema::object();
        }

        // Links
        $properties['links'] = Schema::object([
            'self' => Schema::string('uri'),
            'related' => Schema::string('uri'),
        ]);

        // Meta
        $properties['meta'] = Schema::object();

        return Schema::object($properties, ['type', 'id']); // type and id are required
    }

    /**
     * Generate JSON:API relationship object schema
     *
     * @return Schema Relationship object schema
     */
    public function generateRelationshipObjectSchema(): Schema
    {
        $resourceIdentifier = Schema::object([
            'type' => Schema::string(),
            'id' => Schema::string(),
        ], ['type', 'id']);

        return Schema::object([
            'links' => Schema::object([
                'self' => Schema::string('uri'),
                'related' => Schema::string('uri'),
            ]),
            'data' => Schema::oneOf([
                $resourceIdentifier,
                Schema::array($resourceIdentifier),
                Schema::null(),
            ]),
            'meta' => Schema::object(),
        ]);
    }

    /**
     * Generate JSON:API error object schema
     *
     * @return Schema Error object schema
     */
    public function generateErrorObjectSchema(): Schema
    {
        return Schema::object([
            'id' => Schema::string(),
            'status' => Schema::string(),
            'code' => Schema::string(),
            'title' => Schema::string(),
            'detail' => Schema::string(),
            'source' => Schema::object([
                'pointer' => Schema::string(),
                'parameter' => Schema::string(),
                'header' => Schema::string(),
            ]),
            'meta' => Schema::object(),
        ]);
    }

    /**
     * Generate JSON:API links object schema
     *
     * @return Schema Links object schema
     */
    public function generateLinksObjectSchema(): Schema
    {
        return Schema::object([
            'self' => Schema::string('uri'),
            'related' => Schema::string('uri'),
            'first' => Schema::string('uri'),
            'last' => Schema::string('uri'),
            'prev' => Schema::string('uri'),
            'next' => Schema::string('uri'),
        ]);
    }

    /**
     * Scan a JsonApiResource class and generate schema
     *
     * @param class-string<JsonApiResource>|JsonApiResource $resource Resource class or instance
     * @return Schema Generated schema
     */
    public function scanResource(string|JsonApiResource $resource): Schema
    {
        // For now, return a generic JSON:API document schema
        // In the future, this could analyze the specific resource class
        return $this->generateDocumentSchema();
    }

    /**
     * Generate collection response schema
     *
     * @param Schema|null $itemSchema Schema for collection items
     * @return Schema Collection response schema
     */
    public function generateCollectionSchema(?Schema $itemSchema = null): Schema
    {
        return $this->generateDocumentSchema($itemSchema, true);
    }

    /**
     * Generate error response schema
     *
     * @return Schema Error response schema
     */
    public function generateErrorResponseSchema(): Schema
    {
        return Schema::object([
            'errors' => Schema::array($this->generateErrorObjectSchema()),
            'jsonapi' => Schema::object([
                'version' => Schema::string(),
            ]),
            'meta' => Schema::object(),
        ], ['errors']);
    }

    /**
     * Generate pagination links schema
     *
     * @return Schema Pagination links schema
     */
    public function generatePaginationLinksSchema(): Schema
    {
        return Schema::object([
            'self' => Schema::string('uri'),
            'first' => Schema::string('uri'),
            'last' => Schema::string('uri'),
            'prev' => Schema::string('uri'),
            'next' => Schema::string('uri'),
        ]);
    }

    /**
     * Generate pagination meta schema
     *
     * @return Schema Pagination meta schema
     */
    public function generatePaginationMetaSchema(): Schema
    {
        return Schema::object([
            'total' => Schema::integer(),
            'count' => Schema::integer(),
            'per_page' => Schema::integer(),
            'current_page' => Schema::integer(),
            'total_pages' => Schema::integer(),
        ]);
    }
}
