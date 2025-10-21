---
id: task-00011
title: API Resources
status: In Progress
assignee:
  - '@claude'
created_date: '2025-10-21 19:58'
updated_date: '2025-10-21 21:58'
labels:
  - api
  - resources
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Implémenter un système de transformation de données pour convertir les models en réponses API structurées. Les Resources permettent de contrôler exactement quelles données sont exposées par l'API.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Une classe Resource abstraite existe avec méthode toArray()
- [x] #2 Les resources peuvent transformer des models individuels
- [x] #3 Les resources peuvent transformer des collections
- [x] #4 Support des relations imbriquées (nested resources)
- [x] #5 Les resources peuvent inclure des métadonnées
- [x] #6 Les tests démontrent la transformation de models en JSON
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
1. Design Resource architecture (transformation layer pattern)
2. Create abstract Resource base class with:
   - toArray($request) method for transformation
   - toResponse($request) for HTTP Response
   - Static collection() for handling arrays
   - Static make() factory method
3. Implement single resource transformation:
   - Accept Model instance
   - Transform to array structure
   - Control which attributes to expose
4. Implement collection transformation:
   - Accept array of Models
   - Transform each item
   - Wrap in data envelope
5. Implement nested resources:
   - Support loading related resources
   - Conditional includes
   - whenLoaded() helper
6. Implement metadata support:
   - with() method for extra data
   - additional() for top-level meta
   - Pagination meta helpers
7. Implement conditional attributes:
   - when() for conditional inclusion
   - merge() for merging arrays
   - Attribute helpers
8. Create comprehensive tests:
   - Single resource transformation
   - Collection transformation
   - Nested resources
   - Metadata and pagination
   - Conditional attributes
9. Run quality checks (PHPStan, PHP-CS-Fixer, tests)
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
# Implementation Notes

## Summary
Implemented a complete API Resource transformation system following the Transformer/Presenter pattern. Provides clean, flexible, and testable way to convert models/data to structured API responses with full control over output format.

## Key Components

### Resource Base Class
- **Abstract Resource**: Base class with toArray() for transformation
- **JsonResource**: Concrete implementation for generic use
- **MissingValue**: Marker class for conditional exclusion
- **Magic property access**: Access underlying resource via $this->property
- **Method delegation**: Call methods on underlying resource

### ResourceCollection
- Handles transformation of multiple resources
- Wraps items in "data" envelope
- Supports pagination metadata
- Supports custom metadata

### Transformation Features

**Single Resource**
```php
class UserResource extends Resource {
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}

UserResource::make($user)->toResponse($request);
```

**Collections**
```php
UserResource::collection($users)->toResponse($request);
// {"data": [{...}, {...}]}
```

**Conditional Attributes**
```php
'secret' => $this->when($isAdmin, $value)
'email' => $this->when($request->user()->isAdmin(), $this->email)
```

**Merge When**
```php
return array_merge([
    'base' => 'value',
], $this->mergeWhen($condition, [
    'extra' => 'data',
]));
```

**Nested Resources**
```php
return [
    'id' => $this->id,
    'author' => UserResource::make($this->author)->toArray($request),
    'comments' => CommentResource::collection($this->comments),
];
```

**Metadata**
```php
// Via with() method
protected function with($request): array {
    return ['meta' => ['version' => '1.0']];
}

// Via additional()
$resource->additional(['meta' => ['total' => 100]]);

// Via pagination helper
$collection->withPagination($total, $perPage, $currentPage);
```

## Architecture

**Transformation Flow**
1. Resource wraps underlying data (Model, array, object)
2. toArray() transforms data to array structure
3. resolve() filters MissingValue instances
4. Merges with() data
5. Adds additional() top-level data
6. toResponse() converts to JSON Response

**MissingValue Pattern**
- when() returns MissingValue when condition false
- resolve() filters out all MissingValue instances
- Allows clean conditional attributes in toArray()

**Magic Methods**
- __get(): Access properties on underlying resource
- __isset(): Check if property exists
- __call(): Call methods on underlying resource

## Testing
Created comprehensive test suite (**ResourceTest.php**) with:
- 24 tests covering all functionality
- Basic transformation (arrays, objects, models)
- Custom resource transformation
- Magic property access and method calls
- Conditional attributes (when, mergeWhen)
- Nested resources
- With data and additional data
- HTTP Response generation
- Collections (basic, pagination, metadata)
- All tests passing (24/24, 56 assertions)

## Quality Assurance
- **PHPStan Level 8**: No errors
- **PHP-CS-Fixer**: All style checks passed
- **All tests passing**: 24/24

## Usage Examples

**Basic API endpoint**
```php
// Controller
public function show($id) {
    $user = User::find($id);
    return UserResource::make($user)->toResponse($request);
}

public function index() {
    $users = User::all();
    return UserResource::collection($users)
        ->withPagination(100, 10, 1)
        ->toResponse($request);
}
```

**Complex resource with conditions**
```php
class UserResource extends Resource {
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_admin' => $this->when(
                $request->user()?->isAdmin(),
                $this->is_admin
            ),
            'secret_data' => $this->when(
                $request->user()?->id === $this->id,
                fn() => $this->getSecretData()
            ),
        ];
    }
}
```

**Nested resources**
```php
class PostResource extends Resource {
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'author' => UserResource::make($this->author)->toArray($request),
            'comments' => CommentResource::collection($this->comments)->toArray($request),
        ];
    }
}
```

## Files Created
- `src/Http/Resources/Resource.php` (~230 lines)
- `src/Http/Resources/ResourceCollection.php` (~130 lines)
- `src/Http/Resources/JsonResource.php` (~45 lines)
- `src/Http/Resources/MissingValue.php` (~15 lines)
- `tests/Http/Resources/ResourceTest.php` (~480 lines with 24 tests)

## Benefits
- **Separation of concerns**: Transformation logic separate from controllers
- **Reusable**: Same resource can be used across multiple endpoints
- **Testable**: Resources can be tested independently
- **Flexible**: Conditional attributes, nested resources, metadata
- **Type-safe**: Full type hints with PSR-7 ServerRequestInterface
- **Clean API**: Consistent response format across application
<!-- SECTION:NOTES:END -->
