# JSON:API Support

ElarionStack provides full support for the [JSON:API specification v1.1](https://jsonapi.org/format/), enabling you to build standardized, consistent REST APIs.

## Table of Contents

- [Overview](#overview)
- [JSON:API Resources](#jsonapi-resources)
  - [Basic Resource](#basic-resource)
  - [Resource Attributes](#resource-attributes)
  - [Resource Relationships](#resource-relationships)
  - [Resource Links and Meta](#resource-links-and-meta)
- [JSON:API Collections](#jsonapi-collections)
  - [Basic Collection](#basic-collection)
  - [Pagination](#pagination)
  - [Included Resources](#included-resources)
- [JSON:API Errors](#jsonapi-errors)
  - [Basic Error Response](#basic-error-response)
  - [Validation Errors](#validation-errors)
  - [Helper Methods](#helper-methods)
- [Document Structure](#document-structure)
- [Content-Type Header](#content-type-header)
- [Best Practices](#best-practices)

## Overview

JSON:API is a specification for building APIs in JSON. It defines:
- **Resource objects** with type, id, attributes, and relationships
- **Compound documents** for including related resources
- **Pagination** with standardized link formats
- **Error objects** with consistent structure

ElarionStack implements this through three main classes:
- `JsonApiResource` - Individual resources
- `JsonApiCollection` - Resource collections with pagination
- `JsonApiErrorResponse` - Error responses

All responses include the `jsonapi` version object and use the correct `application/vnd.api+json` Content-Type header.

## JSON:API Resources

### Basic Resource

Every JSON:API resource must have a `type` and `id`. Create a resource by extending `JsonApiResource`:

```php
use Elarion\Http\Resources\JsonApi\JsonApiResource;
use Psr\Http\Message\ServerRequestInterface;

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
            'body' => $this->resource['body'],
            'published_at' => $this->resource['published_at'],
        ];
    }
}
```

**Usage:**

```php
$article = ['id' => 1, 'title' => 'Hello World', 'body' => 'Content...'];
$resource = new ArticleResource($article);

$response = $resource->toResponse($request);
```

**Output:**

```json
{
  "data": {
    "type": "articles",
    "id": "1",
    "attributes": {
      "title": "Hello World",
      "body": "Content...",
      "published_at": "2025-10-22T10:00:00Z"
    }
  },
  "jsonapi": {
    "version": "1.1"
  }
}
```

### Resource Attributes

The `toArray()` method defines resource attributes. You can also override the `attributes()` method for more control:

```php
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

    public function attributes(ServerRequestInterface $request): array
    {
        return [
            'name' => $this->resource['name'],
            'email' => $this->resource['email'],
            'created_at' => $this->resource['created_at'],
        ];
    }
}
```

### Resource Relationships

Define relationships using the `relationships()` method:

```php
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
            'body' => $this->resource['body'],
        ];
    }

    public function relationships(ServerRequestInterface $request): array
    {
        return [
            'author' => $this->relationship(
                'author',
                new UserResource($this->resource['author'])
            ),
            'comments' => $this->relationship(
                'comments',
                array_map(
                    fn($comment) => new CommentResource($comment),
                    $this->resource['comments'] ?? []
                )
            ),
        ];
    }
}
```

**Output:**

```json
{
  "data": {
    "type": "articles",
    "id": "1",
    "attributes": {
      "title": "Hello World",
      "body": "Content..."
    },
    "relationships": {
      "author": {
        "data": {
          "type": "users",
          "id": "42"
        }
      },
      "comments": {
        "data": [
          { "type": "comments", "id": "1" },
          { "type": "comments", "id": "2" }
        ]
      }
    }
  },
  "jsonapi": {
    "version": "1.1"
  }
}
```

**Relationship with Links:**

```php
public function relationships(ServerRequestInterface $request): array
{
    return [
        'author' => $this->relationship(
            'author',
            new UserResource($this->resource['author']),
            links: [
                'self' => "/articles/{$this->id()}/relationships/author",
                'related' => "/articles/{$this->id()}/author",
            ]
        ),
    ];
}
```

### Resource Links and Meta

Add resource-level links and meta:

```php
class ArticleResource extends JsonApiResource
{
    // ... type, id, toArray methods ...

    public function links(ServerRequestInterface $request): array
    {
        return [
            'self' => "/articles/{$this->id()}",
        ];
    }

    public function meta(ServerRequestInterface $request): array
    {
        return [
            'view_count' => $this->resource['view_count'],
            'is_featured' => $this->resource['is_featured'],
        ];
    }
}
```

**Output:**

```json
{
  "data": {
    "type": "articles",
    "id": "1",
    "attributes": { "title": "Hello World" },
    "links": {
      "self": "/articles/1"
    },
    "meta": {
      "view_count": 1234,
      "is_featured": true
    }
  },
  "jsonapi": {
    "version": "1.1"
  }
}
```

## JSON:API Collections

### Basic Collection

Use `JsonApiCollection` to return multiple resources:

```php
use Elarion\Http\Resources\JsonApi\JsonApiCollection;

$articles = [
    ['id' => 1, 'title' => 'First Article'],
    ['id' => 2, 'title' => 'Second Article'],
    ['id' => 3, 'title' => 'Third Article'],
];

$collection = new JsonApiCollection($articles, ArticleResource::class);
$response = $collection->toResponse($request);
```

**Output:**

```json
{
  "data": [
    {
      "type": "articles",
      "id": "1",
      "attributes": { "title": "First Article" }
    },
    {
      "type": "articles",
      "id": "2",
      "attributes": { "title": "Second Article" }
    },
    {
      "type": "articles",
      "id": "3",
      "attributes": { "title": "Third Article" }
    }
  ],
  "jsonapi": {
    "version": "1.1"
  }
}
```

### Pagination

Add JSON:API compliant pagination with links and meta:

```php
$collection = new JsonApiCollection($articles, ArticleResource::class);

$collection->withJsonApiPagination(
    total: 100,        // Total items
    perPage: 10,       // Items per page
    currentPage: 2,    // Current page number
    baseUrl: 'https://api.example.com/articles'
);

$response = $collection->toResponse($request);
```

**Output:**

```json
{
  "data": [ /* ... resources ... */ ],
  "links": {
    "first": "https://api.example.com/articles?page%5Bnumber%5D=1&page%5Bsize%5D=10",
    "last": "https://api.example.com/articles?page%5Bnumber%5D=10&page%5Bsize%5D=10",
    "prev": "https://api.example.com/articles?page%5Bnumber%5D=1&page%5Bsize%5D=10",
    "next": "https://api.example.com/articles?page%5Bnumber%5D=3&page%5Bsize%5D=10"
  },
  "meta": {
    "pagination": {
      "total": 100,
      "count": 10,
      "per_page": 10,
      "current_page": 2,
      "total_pages": 10
    }
  },
  "jsonapi": {
    "version": "1.1"
  }
}
```

**Pagination Behavior:**
- First page (page 1) has no `prev` link
- Last page has no `next` link
- URL encoding: `page[number]` becomes `page%5Bnumber%5D`
- Links include both `page[number]` and `page[size]` parameters

### Included Resources

Include related resources in a compound document using the `include()` method:

```php
$article = new ArticleResource(['id' => 1, 'title' => 'Hello']);
$author = new UserResource(['id' => 42, 'name' => 'John Doe']);
$comment = new CommentResource(['id' => 5, 'text' => 'Great!']);

$article->include([$author, $comment]);

$response = $article->toResponse($request);
```

**Output:**

```json
{
  "data": {
    "type": "articles",
    "id": "1",
    "attributes": { "title": "Hello" }
  },
  "included": [
    {
      "type": "users",
      "id": "42",
      "attributes": { "name": "John Doe" }
    },
    {
      "type": "comments",
      "id": "5",
      "attributes": { "text": "Great!" }
    }
  ],
  "jsonapi": {
    "version": "1.1"
  }
}
```

**Automatic Deduplication:**

```php
$author = new UserResource(['id' => 42, 'name' => 'John']);

$article->include($author);
$article->include($author); // Same resource twice

$document = $article->resolve($request);
// included array will only contain ONE instance of user:42
```

**Collections Automatically Collect Includes:**

```php
class ArticleResource extends JsonApiResource
{
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

// When you create a collection, included resources from relationships
// are automatically collected
$collection = new JsonApiCollection($articles, ArticleResource::class);
// The collection will automatically include all authors in the 'included' section
```

## JSON:API Errors

### Basic Error Response

Create error responses using `JsonApiErrorResponse`:

```php
use Elarion\Http\Resources\JsonApi\JsonApiErrorResponse;

$errorResponse = new JsonApiErrorResponse();
$errorResponse->addError(
    status: '404',
    title: 'Not Found',
    detail: 'The requested article was not found.'
);

$response = $errorResponse->toResponse(); // Status 404
```

**Output:**

```json
{
  "errors": [
    {
      "status": "404",
      "title": "Not Found",
      "detail": "The requested article was not found."
    }
  ],
  "jsonapi": {
    "version": "1.1"
  }
}
```

### Validation Errors

Use the `validationErrors()` factory method for validation errors:

```php
$errors = [
    'title' => ['The title field is required.'],
    'email' => ['The email must be valid.', 'The email is already taken.'],
];

$errorResponse = JsonApiErrorResponse::validationErrors($errors);
$response = $errorResponse->toResponse(); // Status 422
```

**Output:**

```json
{
  "errors": [
    {
      "status": "422",
      "title": "Validation Error",
      "detail": "The title field is required.",
      "source": {
        "pointer": "/data/attributes/title"
      }
    },
    {
      "status": "422",
      "title": "Validation Error",
      "detail": "The email must be valid.",
      "source": {
        "pointer": "/data/attributes/email"
      }
    },
    {
      "status": "422",
      "title": "Validation Error",
      "detail": "The email is already taken.",
      "source": {
        "pointer": "/data/attributes/email"
      }
    }
  ],
  "jsonapi": {
    "version": "1.1"
  }
}
```

### Helper Methods

ElarionStack provides convenient factory methods for common errors:

```php
// 404 Not Found
$error = JsonApiErrorResponse::notFound('articles', 123);

// 401 Unauthorized
$error = JsonApiErrorResponse::unauthorized();
$error = JsonApiErrorResponse::unauthorized('Invalid API key.');

// 403 Forbidden
$error = JsonApiErrorResponse::forbidden();
$error = JsonApiErrorResponse::forbidden('You cannot edit this article.');

// 500 Server Error
$error = JsonApiErrorResponse::serverError();
$error = JsonApiErrorResponse::serverError('Database connection failed.');
```

**Multiple Errors:**

```php
$errorResponse = new JsonApiErrorResponse();
$errorResponse
    ->addError('400', 'Bad Request', 'Invalid JSON payload.')
    ->addError('400', 'Bad Request', 'Missing required fields.');

$response = $errorResponse->toResponse(400);
```

**Error with Source and Code:**

```php
$errorResponse->addError(
    status: '422',
    title: 'Validation Error',
    detail: 'Invalid email format.',
    code: 'INVALID_EMAIL',
    source: ['pointer' => '/data/attributes/email'],
    meta: ['pattern' => '^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$']
);
```

## Document Structure

All JSON:API responses follow a consistent structure:

### Resource Document

```json
{
  "data": {
    "type": "articles",
    "id": "1",
    "attributes": { /* ... */ },
    "relationships": { /* ... */ },
    "links": { /* ... */ },
    "meta": { /* ... */ }
  },
  "included": [ /* ... related resources ... */ ],
  "jsonapi": {
    "version": "1.1"
  },
  "meta": { /* ... top-level meta ... */ }
}
```

### Collection Document

```json
{
  "data": [
    { /* ... resource object ... */ },
    { /* ... resource object ... */ }
  ],
  "included": [ /* ... related resources ... */ ],
  "links": {
    "first": "...",
    "last": "...",
    "prev": "...",
    "next": "..."
  },
  "meta": {
    "pagination": { /* ... */ }
  },
  "jsonapi": {
    "version": "1.1"
  }
}
```

### Error Document

```json
{
  "errors": [
    {
      "status": "...",
      "code": "...",
      "title": "...",
      "detail": "...",
      "source": { /* ... */ },
      "meta": { /* ... */ }
    }
  ],
  "jsonapi": {
    "version": "1.1"
  },
  "meta": { /* ... */ }
}
```

## Content-Type Header

All JSON:API responses automatically include the correct Content-Type header:

```
Content-Type: application/vnd.api+json
```

This is required by the JSON:API specification. ElarionStack handles this automatically when you use:
- `JsonApiResource::toResponse()`
- `JsonApiCollection::toResponse()`
- `JsonApiErrorResponse::toResponse()`

## Best Practices

### 1. Resource Naming

Use plural, lowercase resource types:

```php
✓ 'articles', 'users', 'comments'
✗ 'Article', 'User', 'comment'
```

### 2. ID as String

Always cast IDs to strings (JSON:API requirement):

```php
public function id(): string|int
{
    return $this->resource['id']; // Framework casts to string
}
```

### 3. Sparse Fieldsets

For advanced implementations, support sparse fieldsets:

```php
public function attributes(ServerRequestInterface $request): array
{
    $fields = $request->getQueryParams()['fields']['articles'] ?? null;

    $attributes = [
        'title' => $this->resource['title'],
        'body' => $this->resource['body'],
        'published_at' => $this->resource['published_at'],
    ];

    if ($fields !== null) {
        $requestedFields = explode(',', $fields);
        $attributes = array_intersect_key(
            $attributes,
            array_flip($requestedFields)
        );
    }

    return $attributes;
}
```

### 4. Relationship Links

Provide self and related links for relationships:

```php
public function relationships(ServerRequestInterface $request): array
{
    return [
        'author' => $this->relationship(
            'author',
            new UserResource($this->resource['author']),
            links: [
                'self' => "/articles/{$this->id()}/relationships/author",
                'related' => "/articles/{$this->id()}/author",
            ]
        ),
    ];
}
```

### 5. Top-Level Meta

Add top-level metadata for collections:

```php
$collection = new JsonApiCollection($articles, ArticleResource::class);
$collection->additional([
    'meta' => [
        'copyright' => 'Copyright 2025 Example Corp.',
        'generated_at' => date('c'),
    ],
]);
```

### 6. Error Handling

Use appropriate HTTP status codes and error structures:

```php
// In your controller
try {
    $article = $repository->find($id);
} catch (NotFoundException $e) {
    return JsonApiErrorResponse::notFound('articles', $id)
        ->toResponse(); // Returns 404
}
```

### 7. Include Optimization

Only include relationships that are requested:

```php
$includes = $request->getQueryParams()['include'] ?? '';
$requestedIncludes = explode(',', $includes);

if (in_array('author', $requestedIncludes)) {
    $article->include(new UserResource($data['author']));
}
```

### 8. Pagination Best Practices

- Always provide `total` count for better UX
- Use reasonable default page sizes (10-25 items)
- Include pagination links even for single-page results
- Document your pagination parameters

### 9. API Versioning

Include API version in top-level meta:

```php
$resource->additional([
    'meta' => [
        'api_version' => '1.0',
    ],
]);
```

### 10. Testing

Write comprehensive tests for your JSON:API responses:

```php
public function test_article_resource_structure(): void
{
    $resource = new ArticleResource(['id' => 1, 'title' => 'Test']);
    $data = $resource->toJsonApi($request);

    $this->assertSame('articles', $data['type']);
    $this->assertSame('1', $data['id']);
    $this->assertArrayHasKey('attributes', $data);
}
```

## Advanced Features

### Custom JSON:API Version

Override the JSON:API version if needed:

```php
class ArticleResource extends JsonApiResource
{
    protected string $jsonApiVersion = '1.0';

    // ...
}
```

### Nested Includes

Includes are automatically deduplicated across all levels:

```php
$article = new ArticleResource($data);
$author = new UserResource($authorData);
$author->include(new CountryResource($countryData));

$article->include($author);
// Country will be included in the document
```

### Dynamic Relationships

Build relationships dynamically based on request:

```php
public function relationships(ServerRequestInterface $request): array
{
    $relationships = [];

    if ($this->resource['author_id']) {
        $relationships['author'] = $this->relationship(
            'author',
            new UserResource($this->loadAuthor())
        );
    }

    return $relationships;
}
```

## Summary

ElarionStack's JSON:API implementation provides:

- **Full spec compliance** with JSON:API v1.1
- **Type-safe resources** with abstract base classes
- **Automatic deduplication** of included resources
- **Standardized pagination** with proper link formatting
- **Consistent error responses** with helper factories
- **Correct Content-Type** headers automatically set
- **PSR-7 integration** for seamless HTTP handling

For more information, see:
- [JSON:API Specification](https://jsonapi.org/format/)
- [Resource Classes Documentation](Resources.md)
- [Collection Documentation](Collection.md)
