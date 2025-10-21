<?php

declare(strict_types=1);

namespace Elarion\Tests\Unit\OpenAPI;

use Elarion\OpenAPI\Generator\OpenApiGenerator;
use Elarion\OpenAPI\Schema\OpenApiDocument;
use Elarion\Routing\Router;
use PHPUnit\Framework\TestCase;

/**
 * OpenAPI Generator Tests
 */
final class OpenApiGeneratorTest extends TestCase
{
    private Router $router;
    private OpenApiGenerator $generator;

    protected function setUp(): void
    {
        $this->router = new Router();
        $this->generator = new OpenApiGenerator($this->router, [
            'title' => 'Test API',
            'version' => '1.0.0',
        ]);
    }

    public function test_generates_openapi_document(): void
    {
        $document = $this->generator->generate();

        $this->assertInstanceOf(OpenApiDocument::class, $document);
    }

    public function test_document_has_correct_version(): void
    {
        $document = $this->generator->generate();
        $data = $document->jsonSerialize();

        $this->assertSame('3.1.0', $data['openapi']);
    }

    public function test_document_has_info(): void
    {
        $document = $this->generator->generate();
        $data = $document->jsonSerialize();

        $this->assertArrayHasKey('info', $data);
        $this->assertSame('Test API', $data['info']->jsonSerialize()['title']);
        $this->assertSame('1.0.0', $data['info']->jsonSerialize()['version']);
    }

    public function test_generates_paths_from_routes(): void
    {
        $this->router->get('/users', fn () => 'list users');
        $this->router->post('/users', fn () => 'create user');

        $document = $this->generator->generate();
        $data = $document->jsonSerialize();

        $this->assertArrayHasKey('paths', $data);
        $this->assertArrayHasKey('/users', $data['paths']);
    }

    public function test_extracts_path_parameters(): void
    {
        $this->router->get('/users/{id}', fn ($id) => "user $id");

        $document = $this->generator->generate();
        $data = $document->jsonSerialize();

        $this->assertArrayHasKey('/users/{id}', $data['paths']);
        $pathItem = $data['paths']['/users/{id}'];
        $pathItemData = $pathItem->jsonSerialize();

        $this->assertArrayHasKey('get', $pathItemData);
        $operation = $pathItemData['get'];
        $operationData = $operation->jsonSerialize();

        $this->assertNotEmpty($operationData['parameters']);
    }

    public function test_generates_json_output(): void
    {
        $document = $this->generator->generate();
        $json = $document->toJson();

        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertSame('3.1.0', $decoded['openapi']);
    }

    public function test_generates_yaml_output(): void
    {
        $document = $this->generator->generate();
        $yaml = $document->toYaml();

        $this->assertStringContainsString('openapi: 3.1.0', $yaml);
        $this->assertStringContainsString('title: Test API', $yaml);
    }
}
