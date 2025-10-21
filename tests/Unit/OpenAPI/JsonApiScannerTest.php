<?php

declare(strict_types=1);

namespace Elarion\Tests\Unit\OpenAPI;

use Elarion\OpenAPI\Generator\JsonApiScanner;
use Elarion\OpenAPI\Schema\Schema;
use PHPUnit\Framework\TestCase;

/**
 * JSON:API Scanner Tests
 */
final class JsonApiScannerTest extends TestCase
{
    private JsonApiScanner $scanner;

    protected function setUp(): void
    {
        $this->scanner = new JsonApiScanner();
    }

    public function test_generates_document_schema(): void
    {
        $schema = $this->scanner->generateDocumentSchema();

        $this->assertInstanceOf(Schema::class, $schema);
        $data = $schema->jsonSerialize();

        $this->assertSame('object', $data['type']);
        $this->assertArrayHasKey('properties', $data);
        $this->assertArrayHasKey('data', $data['properties']);
        $this->assertArrayHasKey('jsonapi', $data['properties']);
        $this->assertContains('data', $data['required']);
    }

    public function test_generates_resource_object_schema(): void
    {
        $schema = $this->scanner->generateResourceObjectSchema();

        $data = $schema->jsonSerialize();

        $this->assertSame('object', $data['type']);
        $this->assertArrayHasKey('type', $data['properties']);
        $this->assertArrayHasKey('id', $data['properties']);
        $this->assertArrayHasKey('attributes', $data['properties']);
        $this->assertArrayHasKey('relationships', $data['properties']);
        $this->assertContains('type', $data['required']);
        $this->assertContains('id', $data['required']);
    }

    public function test_generates_collection_schema(): void
    {
        $schema = $this->scanner->generateCollectionSchema();

        $data = $schema->jsonSerialize();

        $this->assertSame('object', $data['type']);
        $dataProperty = $data['properties']['data']->jsonSerialize();
        $this->assertSame('array', $dataProperty['type']);
    }

    public function test_generates_error_object_schema(): void
    {
        $schema = $this->scanner->generateErrorObjectSchema();

        $data = $schema->jsonSerialize();

        $this->assertSame('object', $data['type']);
        $this->assertArrayHasKey('status', $data['properties']);
        $this->assertArrayHasKey('title', $data['properties']);
        $this->assertArrayHasKey('detail', $data['properties']);
    }

    public function test_generates_error_response_schema(): void
    {
        $schema = $this->scanner->generateErrorResponseSchema();

        $data = $schema->jsonSerialize();

        $this->assertSame('object', $data['type']);
        $this->assertArrayHasKey('errors', $data['properties']);
        $this->assertContains('errors', $data['required']);
    }

    public function test_generates_relationship_object_schema(): void
    {
        $schema = $this->scanner->generateRelationshipObjectSchema();

        $data = $schema->jsonSerialize();

        $this->assertSame('object', $data['type']);
        $this->assertArrayHasKey('links', $data['properties']);
        $this->assertArrayHasKey('data', $data['properties']);
        $this->assertArrayHasKey('meta', $data['properties']);
    }

    public function test_generates_pagination_meta_schema(): void
    {
        $schema = $this->scanner->generatePaginationMetaSchema();

        $data = $schema->jsonSerialize();

        $this->assertSame('object', $data['type']);
        $this->assertArrayHasKey('total', $data['properties']);
        $this->assertArrayHasKey('per_page', $data['properties']);
        $this->assertArrayHasKey('current_page', $data['properties']);
    }

    public function test_generates_pagination_links_schema(): void
    {
        $schema = $this->scanner->generatePaginationLinksSchema();

        $data = $schema->jsonSerialize();

        $this->assertSame('object', $data['type']);
        $this->assertArrayHasKey('self', $data['properties']);
        $this->assertArrayHasKey('first', $data['properties']);
        $this->assertArrayHasKey('last', $data['properties']);
        $this->assertArrayHasKey('prev', $data['properties']);
        $this->assertArrayHasKey('next', $data['properties']);
    }
}
