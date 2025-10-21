<?php

declare(strict_types=1);

namespace Elarion\Tests\Http\Resources;

use Elarion\Http\Resources\JsonResource;
use Elarion\Http\Resources\Resource;
use Elarion\Http\Resources\ResourceCollection;
use Elarion\Http\Message\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Resource Tests
 *
 * Comprehensive tests for API Resource transformation system.
 */
final class ResourceTest extends TestCase
{
    private ServerRequestInterface $request;

    protected function setUp(): void
    {
        $this->request = $this->createMock(ServerRequestInterface::class);
    }

    // ========================================
    // BASIC RESOURCE TRANSFORMATION
    // ========================================

    public function test_resource_can_transform_array(): void
    {
        $resource = new JsonResource(['name' => 'John', 'email' => 'john@example.com']);
        $result = $resource->toArray($this->request);

        $this->assertSame([
            'name' => 'John',
            'email' => 'john@example.com',
        ], $result);
    }

    public function test_resource_can_transform_object(): void
    {
        $user = new class {
            public string $name = 'Jane';
            public string $email = 'jane@example.com';
        };

        $resource = new JsonResource($user);
        $result = $resource->toArray($this->request);

        $this->assertSame('Jane', $result['name']);
        $this->assertSame('jane@example.com', $result['email']);
    }

    public function test_resource_can_transform_model_with_to_array(): void
    {
        $model = new class {
            public function toArray(): array
            {
                return ['id' => 1, 'name' => 'Test'];
            }
        };

        $resource = new JsonResource($model);
        $result = $resource->toArray($this->request);

        $this->assertSame(['id' => 1, 'name' => 'Test'], $result);
    }

    public function test_resource_make_factory(): void
    {
        $resource = JsonResource::make(['name' => 'John']);

        $this->assertInstanceOf(JsonResource::class, $resource);
    }

    // ========================================
    // CUSTOM RESOURCE TRANSFORMATION
    // ========================================

    public function test_custom_resource_transformation(): void
    {
        $user = new TestUser(1, 'John Doe', 'john@example.com', 'secret');

        $resource = new TestUserResource($user);
        $result = $resource->toArray($this->request);

        $this->assertSame([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ], $result);

        // Password should not be included
        $this->assertArrayNotHasKey('password', $result);
    }

    // ========================================
    // MAGIC PROPERTY ACCESS
    // ========================================

    public function test_resource_can_access_array_properties(): void
    {
        $resource = new JsonResource(['name' => 'John', 'email' => 'john@example.com']);

        $this->assertSame('John', $resource->name);
        $this->assertSame('john@example.com', $resource->email);
        $this->assertNull($resource->nonexistent);
    }

    public function test_resource_can_access_object_properties(): void
    {
        $user = new TestUser(1, 'Jane', 'jane@example.com', 'secret');
        $resource = new JsonResource($user);

        $this->assertSame(1, $resource->id);
        $this->assertSame('Jane', $resource->name);
        $this->assertSame('jane@example.com', $resource->email);
    }

    public function test_resource_isset_works(): void
    {
        $resource = new JsonResource(['name' => 'John']);

        $this->assertTrue(isset($resource->name));
        $this->assertFalse(isset($resource->email));
    }

    public function test_resource_can_call_methods_on_underlying_resource(): void
    {
        $user = new TestUser(1, 'John', 'john@example.com', 'secret');
        $resource = new JsonResource($user);

        $this->assertSame('JOHN', $resource->getUpperName());
    }

    // ========================================
    // CONDITIONAL ATTRIBUTES
    // ========================================

    public function test_when_includes_value_when_true(): void
    {
        $resource = new TestConditionalResource(['admin' => true]);
        $result = $resource->toArray($this->request);

        $this->assertSame('secret-value', $result['secret']);
    }

    public function test_when_excludes_value_when_false(): void
    {
        $resource = new TestConditionalResource(['admin' => false]);
        $result = $resource->resolve($this->request);

        $this->assertArrayNotHasKey('secret', $result);
    }

    public function test_when_with_closure(): void
    {
        $resource = new TestConditionalResource(['admin' => true]);
        $result = $resource->toArray($this->request);

        $this->assertSame('computed-value', $result['computed']);
    }

    public function test_merge_when_condition_true(): void
    {
        $resource = new TestMergeResource(['include_extra' => true]);
        $result = $resource->toArray($this->request);

        $this->assertSame('base', $result['base']);
        $this->assertSame('extra1', $result['extra1']);
        $this->assertSame('extra2', $result['extra2']);
    }

    public function test_merge_when_condition_false(): void
    {
        $resource = new TestMergeResource(['include_extra' => false]);
        $result = $resource->toArray($this->request);

        $this->assertSame('base', $result['base']);
        $this->assertArrayNotHasKey('extra1', $result);
        $this->assertArrayNotHasKey('extra2', $result);
    }

    // ========================================
    // NESTED RESOURCES
    // ========================================

    public function test_nested_resource(): void
    {
        $user = new TestUser(1, 'John', 'john@example.com', 'secret');
        $post = new TestPost(1, 'My Post', $user);

        $resource = new TestPostResource($post);
        $result = $resource->toArray($this->request);

        $this->assertSame(1, $result['id']);
        $this->assertSame('My Post', $result['title']);
        $this->assertIsArray($result['author']);
        $this->assertSame(1, $result['author']['id']);
        $this->assertSame('John', $result['author']['name']);
    }

    // ========================================
    // WITH DATA
    // ========================================

    public function test_with_data_merged_into_response(): void
    {
        $resource = new TestWithResource(['name' => 'John']);
        $result = $resource->resolve($this->request);

        $this->assertSame('John', $result['name']);
        $this->assertSame('extra-value', $result['extra_key']);
    }

    // ========================================
    // ADDITIONAL DATA
    // ========================================

    public function test_additional_data_at_top_level(): void
    {
        $resource = JsonResource::make(['name' => 'John'])
            ->additional([
                'meta' => ['version' => '1.0'],
            ]);

        $result = $resource->resolve($this->request);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertSame('John', $result['data']['name']);
        $this->assertSame('1.0', $result['meta']['version']);
    }

    // ========================================
    // HTTP RESPONSE
    // ========================================

    public function test_to_response_returns_json_response(): void
    {
        $resource = JsonResource::make(['name' => 'John']);
        $response = $resource->toResponse($this->request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_to_response_with_custom_status(): void
    {
        $resource = JsonResource::make(['name' => 'John']);
        $response = $resource->toResponse($this->request, 201);

        $this->assertSame(201, $response->getStatusCode());
    }

    // ========================================
    // RESOURCE COLLECTIONS
    // ========================================

    public function test_collection_transforms_multiple_resources(): void
    {
        $users = [
            new TestUser(1, 'John', 'john@example.com', 'pass'),
            new TestUser(2, 'Jane', 'jane@example.com', 'pass'),
        ];

        $collection = TestUserResource::collection($users);
        $result = $collection->toArray($this->request);

        $this->assertArrayHasKey('data', $result);
        $this->assertCount(2, $result['data']);
        $this->assertSame('John', $result['data'][0]['name']);
        $this->assertSame('Jane', $result['data'][1]['name']);
    }

    public function test_collection_with_additional_data(): void
    {
        $users = [new TestUser(1, 'John', 'john@example.com', 'pass')];

        $collection = TestUserResource::collection($users)
            ->additional(['meta' => ['total' => 100]]);

        $result = $collection->resolve($this->request);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertSame(100, $result['meta']['total']);
    }

    public function test_collection_with_pagination(): void
    {
        $users = [
            new TestUser(1, 'John', 'john@example.com', 'pass'),
            new TestUser(2, 'Jane', 'jane@example.com', 'pass'),
        ];

        $collection = TestUserResource::collection($users)
            ->withPagination(100, 10, 1);

        $result = $collection->resolve($this->request);

        $this->assertArrayHasKey('meta', $result);
        $this->assertSame(100, $result['meta']['total']);
        $this->assertSame(10, $result['meta']['per_page']);
        $this->assertSame(1, $result['meta']['current_page']);
        $this->assertSame(10, $result['meta']['last_page']);
        $this->assertSame(1, $result['meta']['from']);
        $this->assertSame(10, $result['meta']['to']);
    }

    public function test_collection_with_meta(): void
    {
        $users = [new TestUser(1, 'John', 'john@example.com', 'pass')];

        $collection = TestUserResource::collection($users)
            ->withMeta(['version' => '2.0']);

        $result = $collection->resolve($this->request);

        $this->assertSame('2.0', $result['meta']['version']);
    }

    public function test_collection_to_response(): void
    {
        $users = [new TestUser(1, 'John', 'john@example.com', 'pass')];

        $collection = TestUserResource::collection($users);
        $response = $collection->toResponse($this->request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }
}

// ========================================
// TEST CLASSES
// ========================================

/**
 * Test User Model
 */
class TestUser
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $password
    ) {
    }

    public function getUpperName(): string
    {
        return strtoupper($this->name);
    }
}

/**
 * Test Post Model
 */
class TestPost
{
    public function __construct(
        public int $id,
        public string $title,
        public TestUser $author
    ) {
    }
}

/**
 * Test User Resource
 */
class TestUserResource extends Resource
{
    public function toArray(ServerRequestInterface $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            // password excluded for security
        ];
    }
}

/**
 * Test Post Resource with nested User Resource
 */
class TestPostResource extends Resource
{
    public function toArray(ServerRequestInterface $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => TestUserResource::make($this->author)->toArray($request),
        ];
    }
}

/**
 * Test Resource with conditional attributes
 */
class TestConditionalResource extends Resource
{
    public function toArray(ServerRequestInterface $request): array
    {
        return [
            'name' => 'test',
            'secret' => $this->when($this->resource['admin'] ?? false, 'secret-value'),
            'computed' => $this->when(
                $this->resource['admin'] ?? false,
                fn () => 'computed-value'
            ),
        ];
    }
}

/**
 * Test Resource with merge when
 */
class TestMergeResource extends Resource
{
    public function toArray(ServerRequestInterface $request): array
    {
        return array_merge([
            'base' => 'base',
        ], $this->mergeWhen($this->resource['include_extra'] ?? false, [
            'extra1' => 'extra1',
            'extra2' => 'extra2',
        ]));
    }
}

/**
 * Test Resource with additional data
 */
class TestWithResource extends Resource
{
    public function toArray(ServerRequestInterface $request): array
    {
        return [
            'name' => $this->resource['name'],
        ];
    }

    protected function with(ServerRequestInterface $request): array
    {
        return [
            'extra_key' => 'extra-value',
        ];
    }
}
