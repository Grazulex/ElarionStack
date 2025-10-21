<?php

declare(strict_types=1);

namespace Elarion\Tests\Http\Resources\JsonApi;

use Elarion\Http\Resources\JsonApi\JsonApiCollection;
use Elarion\Http\Resources\JsonApi\JsonApiErrorResponse;
use Elarion\Http\Resources\JsonApi\JsonApiResource;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * JSON:API Tests
 *
 * Tests for JSON:API specification compliance.
 */
final class JsonApiTest extends TestCase
{
    private ServerRequestInterface $request;

    protected function setUp(): void
    {
        $this->request = $this->createMock(ServerRequestInterface::class);
    }

    // ========================================
    // RESOURCE TESTS
    // ========================================

    public function test_resource_has_type_and_id(): void
    {
        $resource = new ArticleResource(['id' => 1, 'title' => 'Hello World']);

        $data = $resource->toJsonApi($this->request);

        $this->assertSame('articles', $data['type']);
        $this->assertSame('1', $data['id']);
    }

    public function test_resource_has_attributes(): void
    {
        $resource = new ArticleResource(['id' => 1, 'title' => 'Hello', 'body' => 'World']);

        $data = $resource->toJsonApi($this->request);

        $this->assertArrayHasKey('attributes', $data);
        $this->assertSame('Hello', $data['attributes']['title']);
        $this->assertSame('World', $data['attributes']['body']);
    }

    public function test_resource_document_structure(): void
    {
        $resource = new ArticleResource(['id' => 1, 'title' => 'Test']);

        $document = $resource->resolve($this->request);

        $this->assertArrayHasKey('data', $document);
        $this->assertArrayHasKey('jsonapi', $document);
        $this->assertSame('1.1', $document['jsonapi']['version']);
    }

    public function test_resource_response_has_correct_content_type(): void
    {
        $resource = new ArticleResource(['id' => 1, 'title' => 'Test']);

        $response = $resource->toResponse($this->request);

        $this->assertTrue($response->hasHeader('Content-Type'));
        $this->assertSame('application/vnd.api+json', $response->getHeaderLine('Content-Type'));
    }

    // ========================================
    // RELATIONSHIPS TESTS
    // ========================================

    public function test_resource_can_have_relationships(): void
    {
        $article = new ArticleWithAuthorResource([
            'id' => 1,
            'title' => 'Test',
            'author' => ['id' => 5, 'name' => 'John'],
        ]);

        $data = $article->toJsonApi($this->request);

        $this->assertArrayHasKey('relationships', $data);
        $this->assertArrayHasKey('author', $data['relationships']);
        $this->assertArrayHasKey('data', $data['relationships']['author']);
        $this->assertSame('users', $data['relationships']['author']['data']['type']);
        $this->assertSame('5', $data['relationships']['author']['data']['id']);
    }

    // ========================================
    // INCLUDED RESOURCES TESTS
    // ========================================

    public function test_resource_can_include_related_resources(): void
    {
        $author = new UserResource(['id' => 5, 'name' => 'John']);

        $article = new ArticleResource(['id' => 1, 'title' => 'Test']);
        $article->include($author);

        $document = $article->resolve($this->request);

        $this->assertArrayHasKey('included', $document);
        $this->assertCount(1, $document['included']);
        $this->assertSame('users', $document['included'][0]['type']);
        $this->assertSame('5', $document['included'][0]['id']);
    }

    public function test_included_resources_are_deduplicated(): void
    {
        $author = new UserResource(['id' => 5, 'name' => 'John']);

        $article = new ArticleResource(['id' => 1, 'title' => 'Test']);
        $article->include($author);
        $article->include($author); // Same resource twice

        $document = $article->resolve($this->request);

        $this->assertCount(1, $document['included']);
    }

    // ========================================
    // COLLECTION TESTS
    // ========================================

    public function test_collection_returns_array_of_resources(): void
    {
        $articles = [
            ['id' => 1, 'title' => 'First'],
            ['id' => 2, 'title' => 'Second'],
        ];

        $collection = new JsonApiCollection($articles, ArticleResource::class);

        $data = $collection->toArray($this->request);

        $this->assertArrayHasKey('data', $data);
        $this->assertCount(2, $data['data']);
        $this->assertSame('articles', $data['data'][0]['type']);
        $this->assertSame('1', $data['data'][0]['id']);
    }

    public function test_collection_has_jsonapi_version(): void
    {
        $collection = new JsonApiCollection([], ArticleResource::class);

        $document = $collection->resolve($this->request);

        $this->assertArrayHasKey('jsonapi', $document);
        $this->assertSame('1.1', $document['jsonapi']['version']);
    }

    // ========================================
    // PAGINATION TESTS
    // ========================================

    public function test_collection_with_pagination_has_links(): void
    {
        $articles = [
            ['id' => 1, 'title' => 'First'],
            ['id' => 2, 'title' => 'Second'],
        ];

        $collection = new JsonApiCollection($articles, ArticleResource::class);
        $collection->withJsonApiPagination(100, 10, 2, 'http://example.com/articles');

        $document = $collection->resolve($this->request);

        $this->assertArrayHasKey('links', $document);
        $this->assertArrayHasKey('first', $document['links']);
        $this->assertArrayHasKey('last', $document['links']);
        $this->assertArrayHasKey('prev', $document['links']);
        $this->assertArrayHasKey('next', $document['links']);
    }

    public function test_pagination_links_format(): void
    {
        $collection = new JsonApiCollection([], ArticleResource::class);
        $collection->withJsonApiPagination(100, 10, 2, 'http://example.com/articles');

        $document = $collection->resolve($this->request);

        $this->assertStringContainsString('page%5Bnumber%5D=1', $document['links']['first']);
        $this->assertStringContainsString('page%5Bsize%5D=10', $document['links']['first']);
    }

    public function test_pagination_meta(): void
    {
        $articles = [
            ['id' => 1, 'title' => 'First'],
            ['id' => 2, 'title' => 'Second'],
        ];

        $collection = new JsonApiCollection($articles, ArticleResource::class);
        $collection->withJsonApiPagination(100, 10, 2, 'http://example.com/articles');

        $document = $collection->resolve($this->request);

        $this->assertArrayHasKey('meta', $document);
        $this->assertArrayHasKey('pagination', $document['meta']);
        $this->assertSame(100, $document['meta']['pagination']['total']);
        $this->assertSame(2, $document['meta']['pagination']['count']);
        $this->assertSame(10, $document['meta']['pagination']['per_page']);
        $this->assertSame(2, $document['meta']['pagination']['current_page']);
        $this->assertSame(10, $document['meta']['pagination']['total_pages']);
    }

    public function test_first_page_has_no_prev_link(): void
    {
        $collection = new JsonApiCollection([], ArticleResource::class);
        $collection->withJsonApiPagination(100, 10, 1, 'http://example.com/articles');

        $document = $collection->resolve($this->request);

        $this->assertArrayNotHasKey('prev', $document['links']);
        $this->assertArrayHasKey('next', $document['links']);
    }

    public function test_last_page_has_no_next_link(): void
    {
        $collection = new JsonApiCollection([], ArticleResource::class);
        $collection->withJsonApiPagination(100, 10, 10, 'http://example.com/articles');

        $document = $collection->resolve($this->request);

        $this->assertArrayNotHasKey('next', $document['links']);
        $this->assertArrayHasKey('prev', $document['links']);
    }

    // ========================================
    // ERROR RESPONSE TESTS
    // ========================================

    public function test_error_response_structure(): void
    {
        $errorResponse = new JsonApiErrorResponse();
        $errorResponse->addError('404', 'Not Found', 'The resource was not found.');

        $document = $errorResponse->toArray();

        $this->assertArrayHasKey('errors', $document);
        $this->assertArrayHasKey('jsonapi', $document);
        $this->assertSame('1.1', $document['jsonapi']['version']);
    }

    public function test_error_has_required_fields(): void
    {
        $errorResponse = new JsonApiErrorResponse();
        $errorResponse->addError('422', 'Validation Error', 'The title field is required.');

        $errors = $errorResponse->toArray()['errors'];

        $this->assertCount(1, $errors);
        $this->assertSame('422', $errors[0]['status']);
        $this->assertSame('Validation Error', $errors[0]['title']);
        $this->assertSame('The title field is required.', $errors[0]['detail']);
    }

    public function test_error_with_source(): void
    {
        $errorResponse = new JsonApiErrorResponse();
        $errorResponse->addError(
            '422',
            'Validation Error',
            'Invalid value',
            null,
            ['pointer' => '/data/attributes/email']
        );

        $errors = $errorResponse->toArray()['errors'];

        $this->assertArrayHasKey('source', $errors[0]);
        $this->assertSame('/data/attributes/email', $errors[0]['source']['pointer']);
    }

    public function test_validation_errors_helper(): void
    {
        $validationErrors = [
            'title' => ['The title field is required.'],
            'email' => ['The email must be valid.'],
        ];

        $errorResponse = JsonApiErrorResponse::validationErrors($validationErrors);

        $errors = $errorResponse->toArray()['errors'];

        $this->assertCount(2, $errors);
        $this->assertSame('422', $errors[0]['status']);
        $this->assertArrayHasKey('source', $errors[0]);
    }

    public function test_not_found_helper(): void
    {
        $errorResponse = JsonApiErrorResponse::notFound('articles', 123);

        $errors = $errorResponse->toArray()['errors'];

        $this->assertSame('404', $errors[0]['status']);
        $this->assertSame('Resource Not Found', $errors[0]['title']);
    }

    public function test_error_response_content_type(): void
    {
        $errorResponse = new JsonApiErrorResponse();
        $errorResponse->addError('500', 'Server Error');

        $response = $errorResponse->toResponse();

        $this->assertSame('application/vnd.api+json', $response->getHeaderLine('Content-Type'));
    }

    public function test_error_response_uses_first_error_status(): void
    {
        $errorResponse = new JsonApiErrorResponse();
        $errorResponse->addError('422', 'Validation Error');

        $response = $errorResponse->toResponse();

        $this->assertSame(422, $response->getStatusCode());
    }
}

// ========================================
// TEST RESOURCES
// ========================================

/**
 * Test Article Resource
 */
class ArticleResource extends JsonApiResource
{
    public function type(): string
    {
        return 'articles';
    }

    public function id(): string|int
    {
        return $this->resource['id'];
    }

    public function toArray(ServerRequestInterface $request): array
    {
        return [
            'title' => $this->resource['title'],
            'body' => $this->resource['body'] ?? null,
        ];
    }
}

/**
 * Test Article with Author Resource
 */
class ArticleWithAuthorResource extends JsonApiResource
{
    public function type(): string
    {
        return 'articles';
    }

    public function id(): string|int
    {
        return $this->resource['id'];
    }

    public function toArray(ServerRequestInterface $request): array
    {
        return [
            'title' => $this->resource['title'],
        ];
    }

    public function relationships(ServerRequestInterface $request): array
    {
        return [
            'author' => $this->relationship(
                'author',
                new UserResource($this->resource['author'])
            ),
        ];
    }
}

/**
 * Test User Resource
 */
class UserResource extends JsonApiResource
{
    public function type(): string
    {
        return 'users';
    }

    public function id(): string|int
    {
        return $this->resource['id'];
    }

    public function toArray(ServerRequestInterface $request): array
    {
        return [
            'name' => $this->resource['name'],
        ];
    }
}
