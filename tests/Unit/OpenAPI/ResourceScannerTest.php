<?php

declare(strict_types=1);

namespace Elarion\Tests\Unit\OpenAPI;

use Elarion\Http\Resources\JsonResource;
use Elarion\OpenAPI\Generator\ResourceScanner;
use Elarion\OpenAPI\Schema\Schema;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Resource Scanner Tests
 */
final class ResourceScannerTest extends TestCase
{
    private ResourceScanner $scanner;

    protected function setUp(): void
    {
        $this->scanner = new ResourceScanner();
    }

    public function test_scans_resource_with_sample_data(): void
    {
        $sampleData = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
            'is_active' => true,
        ];

        $schema = $this->scanner->scan(JsonResource::class, $sampleData);

        $this->assertInstanceOf(Schema::class, $schema);
        $data = $schema->jsonSerialize();

        $this->assertSame('object', $data['type']);
        $this->assertArrayHasKey('properties', $data);
        $this->assertArrayHasKey('id', $data['properties']);
        $this->assertArrayHasKey('name', $data['properties']);
        $this->assertArrayHasKey('email', $data['properties']);
    }

    public function test_infers_integer_type(): void
    {
        $sampleData = ['count' => 42];

        $schema = $this->scanner->scan(JsonResource::class, $sampleData);
        $data = $schema->jsonSerialize();

        $countSchema = $data['properties']['count']->jsonSerialize();
        $this->assertSame('integer', $countSchema['type']);
    }

    public function test_infers_string_type(): void
    {
        $sampleData = ['name' => 'Test'];

        $schema = $this->scanner->scan(JsonResource::class, $sampleData);
        $data = $schema->jsonSerialize();

        $nameSchema = $data['properties']['name']->jsonSerialize();
        $this->assertSame('string', $nameSchema['type']);
    }

    public function test_infers_boolean_type(): void
    {
        $sampleData = ['is_active' => true];

        $schema = $this->scanner->scan(JsonResource::class, $sampleData);
        $data = $schema->jsonSerialize();

        $activeSchema = $data['properties']['is_active']->jsonSerialize();
        $this->assertSame('boolean', $activeSchema['type']);
    }

    public function test_infers_email_format(): void
    {
        $sampleData = ['email' => 'test@example.com'];

        $schema = $this->scanner->scan(JsonResource::class, $sampleData);
        $data = $schema->jsonSerialize();

        $emailSchema = $data['properties']['email']->jsonSerialize();
        $this->assertSame('string', $emailSchema['type']);
        $this->assertSame('email', $emailSchema['format']);
    }

    public function test_infers_array_type(): void
    {
        $sampleData = ['tags' => ['php', 'laravel', 'openapi']];

        $schema = $this->scanner->scan(JsonResource::class, $sampleData);
        $data = $schema->jsonSerialize();

        $tagsSchema = $data['properties']['tags']->jsonSerialize();
        $this->assertSame('array', $tagsSchema['type']);
    }

    public function test_infers_nested_object(): void
    {
        $sampleData = [
            'user' => [
                'id' => 1,
                'name' => 'John',
            ],
        ];

        $schema = $this->scanner->scan(JsonResource::class, $sampleData);
        $data = $schema->jsonSerialize();

        $userSchema = $data['properties']['user']->jsonSerialize();
        $this->assertSame('object', $userSchema['type']);
        $this->assertArrayHasKey('properties', $userSchema);
    }

    public function test_returns_generic_schema_without_sample_data(): void
    {
        $schema = $this->scanner->scan(JsonResource::class);

        $this->assertInstanceOf(Schema::class, $schema);
        $data = $schema->jsonSerialize();

        $this->assertSame('object', $data['type']);
    }
}
