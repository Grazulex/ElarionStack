# OpenAPI / Swagger Documentation Generator

Automatically generate OpenAPI 3.1 documentation for your ElarionStack APIs with interactive Swagger UI.

## Features

- ✅ **OpenAPI 3.1 Specification** - Full compliance with latest OpenAPI standard
- ✅ **PHP 8+ Attributes** - Clean, native annotations for endpoints
- ✅ **Automatic Generation** - Routes and validation rules auto-converted to schemas
- ✅ **Swagger UI** - Interactive API documentation interface
- ✅ **JSON & YAML Export** - Multiple output formats
- ✅ **Validation Integration** - Automatically converts validation rules to request schemas
- ✅ **JSON:API Support** - Specialized handling for JSON:API responses

## Installation

The OpenAPI generator is included in ElarionStack. Just register the service provider:

```php
// bootstrap/app.php or config/app.php
return [
    'providers' => [
        // ... other providers
        \Elarion\OpenAPI\OpenAPIServiceProvider::class,
    ],
];
```

## Configuration

Configure your API documentation in `config/openapi.php`:

```php
return [
    'title' => env('API_TITLE', 'My API'),
    'version' => env('API_VERSION', '1.0.0'),
    'description' => env('API_DESCRIPTION', 'API Documentation'),

    'servers' => [
        [
            'url' => env('API_URL', 'http://localhost:8000'),
            'description' => 'Development Server',
        ],
        [
            'url' => 'https://api.production.com',
            'description' => 'Production Server',
        ],
    ],

    'routes' => [
        'ui' => '/api/documentation',
        'json' => '/api/documentation.json',
        'yaml' => '/api/documentation.yaml',
    ],
];
```

## Quick Start

### 1. Basic Route Documentation

Routes are automatically discovered. For basic documentation, no annotations needed:

```php
// Automatically generates basic OpenAPI documentation
$router->get('/users', [UserController::class, 'index']);
$router->post('/users', [UserController::class, 'store']);
$router->get('/users/{id}', [UserController::class, 'show']);
```

### 2. Enhanced Documentation with Attributes

Use PHP Attributes for detailed documentation:

```php
use Elarion\OpenAPI\Attributes\{Get, Post, PathParameter, QueryParameter};

class UserController
{
    #[Get(
        path: '/users',
        summary: 'List all users',
        description: 'Returns paginated list of users',
        tags: ['Users']
    )]
    #[QueryParameter('page', 'integer', 'Page number')]
    #[QueryParameter('limit', 'integer', 'Items per page')]
    public function index(ServerRequestInterface $request): Response
    {
        // ...
    }

    #[Post(
        path: '/users',
        summary: 'Create new user',
        tags: ['Users'],
        operationId: 'createUser'
    )]
    public function store(ServerRequestInterface $request): Response
    {
        // ...
    }

    #[Get(
        path: '/users/{id}',
        summary: 'Get user by ID',
        tags: ['Users']
    )]
    #[PathParameter('id', 'integer', 'User ID')]
    public function show(int $id): Response
    {
        // ...
    }
}
```

### 3. Request Body Documentation

Document request bodies with validation rules:

```php
use Elarion\OpenAPI\Attributes\{Post, RequestBody};
use Elarion\Validation\Validator;

class UserController
{
    #[Post(
        path: '/users',
        summary: 'Create new user',
        tags: ['Users']
    )]
    #[RequestBody(
        description: 'User data',
        required: true
    )]
    public function store(ServerRequestInterface $request): Response
    {
        // Validation rules are automatically converted to OpenAPI schemas
        $validator = new Validator($request->getParsedBody(), [
            'name' => 'required|string|min:3|max:255',
            'email' => 'required|email',
            'age' => 'integer|min:18|max:120',
            'is_active' => 'boolean',
        ]);

        if (!$validator->validate()) {
            return Response::json($validator->errors(), 422);
        }

        // ...
    }
}
```

### 4. Response Documentation

Document responses with the Response attribute:

```php
use Elarion\OpenAPI\Attributes\{Get, Response as ResponseAttr};

class UserController
{
    #[Get(
        path: '/users/{id}',
        summary: 'Get user by ID',
        tags: ['Users']
    )]
    #[ResponseAttr(
        statusCode: '200',
        description: 'User found',
        contentType: 'application/json'
    )]
    #[ResponseAttr(
        statusCode: '404',
        description: 'User not found'
    )]
    public function show(int $id): Response
    {
        // ...
    }
}
```

## Available Attributes

### HTTP Method Attributes

All support the same parameters:

```php
use Elarion\OpenAPI\Attributes\{Get, Post, Put, Patch, Delete};

#[Get(
    path: '/endpoint',           // Required: endpoint path
    summary: 'Short description', // Optional: brief summary
    description: 'Long description', // Optional: detailed description
    tags: ['Category'],          // Optional: tags for grouping
    operationId: 'uniqueId',     // Optional: unique operation ID
    deprecated: false            // Optional: mark as deprecated
)]
```

### Parameter Attributes

```php
use Elarion\OpenAPI\Attributes\{PathParameter, QueryParameter};

// Path parameter (from route like /users/{id})
#[PathParameter(
    name: 'id',
    type: 'integer',
    description: 'User ID',
    format: 'int64'  // Optional: format specification
)]

// Query parameter (from URL like ?page=1)
#[QueryParameter(
    name: 'page',
    type: 'integer',
    description: 'Page number',
    required: false  // Optional: default is false for query params
)]
```

### Request Body Attribute

```php
use Elarion\OpenAPI\Attributes\RequestBody;

#[RequestBody(
    description: 'Request payload description',
    required: true,  // Optional: default is true
    contentType: 'application/json'  // Optional: default is application/json
)]
```

### Response Attribute

```php
use Elarion\OpenAPI\Attributes\Response;

#[Response(
    statusCode: '200',
    description: 'Success response',
    contentType: 'application/json'
)]
```

### Tag Attribute

```php
use Elarion\OpenAPI\Attributes\Tag;

#[Tag('Users')]  // Simple tag
#[Tag('Users', 'User management endpoints')]  // Tag with description
```

## Accessing Documentation

Once configured, access your API documentation at:

### Swagger UI (Interactive)
```
http://localhost:8000/api/documentation
```

### JSON Format
```
http://localhost:8000/api/documentation.json
```

### YAML Format
```
http://localhost:8000/api/documentation.yaml
```

## Validation Rules to OpenAPI Schema Mapping

The generator automatically converts validation rules to OpenAPI schemas:

| Validation Rule | OpenAPI Mapping |
|----------------|-----------------|
| `required` | Added to required array |
| `string` | `type: "string"` |
| `integer` | `type: "integer"` |
| `numeric` | `type: "number"` |
| `boolean` | `type: "boolean"` |
| `array` | `type: "array"` |
| `email` | `type: "string"`, `format: "email"` |
| `url` | `type: "string"`, `format: "uri"` |
| `date` | `type: "string"`, `format: "date"` |
| `min:3` | `minLength: 3` (string) or `minimum: 3` (number) |
| `max:100` | `maxLength: 100` (string) or `maximum: 100` (number) |

## Complete Example

Here's a complete example of a well-documented API controller:

```php
<?php

namespace App\Http\Controllers;

use Elarion\Http\{ServerRequestInterface, Response};
use Elarion\OpenAPI\Attributes\{
    Get,
    Post,
    Put,
    Delete,
    PathParameter,
    QueryParameter,
    RequestBody,
    Response as ResponseAttr,
    Tag
};
use Elarion\Validation\Validator;

#[Tag('Users', 'User management endpoints')]
class UserController
{
    #[Get(
        path: '/users',
        summary: 'List all users',
        description: 'Returns a paginated list of users with optional filtering',
        tags: ['Users'],
        operationId: 'listUsers'
    )]
    #[QueryParameter('page', 'integer', 'Page number (default: 1)')]
    #[QueryParameter('limit', 'integer', 'Items per page (default: 20, max: 100)')]
    #[QueryParameter('search', 'string', 'Search users by name or email')]
    #[ResponseAttr('200', 'Success - Returns user list')]
    #[ResponseAttr('400', 'Bad Request - Invalid parameters')]
    public function index(ServerRequestInterface $request): Response
    {
        $params = $request->getQueryParams();
        $page = (int) ($params['page'] ?? 1);
        $limit = min((int) ($params['limit'] ?? 20), 100);

        // Fetch users...

        return Response::json([
            'data' => $users,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
            ],
        ]);
    }

    #[Post(
        path: '/users',
        summary: 'Create a new user',
        description: 'Creates a new user with the provided data',
        tags: ['Users'],
        operationId: 'createUser'
    )]
    #[RequestBody('User creation data', required: true)]
    #[ResponseAttr('201', 'Created - User created successfully')]
    #[ResponseAttr('422', 'Validation Error - Invalid user data')]
    public function store(ServerRequestInterface $request): Response
    {
        $validator = new Validator($request->getParsedBody(), [
            'name' => 'required|string|min:3|max:255',
            'email' => 'required|email',
            'password' => 'required|string|min:8',
            'age' => 'integer|min:18|max:120',
            'is_active' => 'boolean',
        ]);

        if (!$validator->validate()) {
            return Response::json([
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Create user...

        return Response::json($user, 201);
    }

    #[Get(
        path: '/users/{id}',
        summary: 'Get user by ID',
        description: 'Returns detailed information about a specific user',
        tags: ['Users'],
        operationId: 'getUser'
    )]
    #[PathParameter('id', 'integer', 'User ID', format: 'int64')]
    #[ResponseAttr('200', 'Success - Returns user data')]
    #[ResponseAttr('404', 'Not Found - User does not exist')]
    public function show(int $id): Response
    {
        // Find user...

        if (!$user) {
            return Response::json(['error' => 'User not found'], 404);
        }

        return Response::json($user);
    }

    #[Put(
        path: '/users/{id}',
        summary: 'Update user',
        description: 'Updates an existing user with the provided data',
        tags: ['Users'],
        operationId: 'updateUser'
    )]
    #[PathParameter('id', 'integer', 'User ID')]
    #[RequestBody('User update data', required: true)]
    #[ResponseAttr('200', 'Success - User updated')]
    #[ResponseAttr('404', 'Not Found - User does not exist')]
    #[ResponseAttr('422', 'Validation Error - Invalid data')]
    public function update(int $id, ServerRequestInterface $request): Response
    {
        // Similar to store()...
    }

    #[Delete(
        path: '/users/{id}',
        summary: 'Delete user',
        description: 'Soft deletes a user from the system',
        tags: ['Users'],
        operationId: 'deleteUser'
    )]
    #[PathParameter('id', 'integer', 'User ID')]
    #[ResponseAttr('204', 'No Content - User deleted')]
    #[ResponseAttr('404', 'Not Found - User does not exist')]
    public function destroy(int $id): Response
    {
        // Delete user...

        return Response::noContent();
    }
}
```

## Architecture

The OpenAPI generator consists of several components:

### Schema Classes
- `OpenApiDocument` - Root OpenAPI document
- `Info`, `Contact`, `License` - API metadata
- `Server`, `ServerVariable` - Server configuration
- `PathItem`, `Operation` - Endpoint definitions
- `Parameter`, `RequestBody`, `Response` - Request/response documentation
- `Schema` - JSON Schema for data validation
- `Components` - Reusable components

### Scanners
- `RouteScanner` - Extracts registered routes from Router
- `AttributeScanner` - Reads PHP Attributes from controller methods
- `ValidationScanner` - Converts validation rules to OpenAPI schemas
- `ResourceScanner` - Converts API Resources to response schemas (partial)
- `JsonApiScanner` - Handles JSON:API format responses (partial)

### Generator
- `OpenApiGenerator` - Main orchestrator that combines all scanners

### Controller
- `DocumentationController` - Serves JSON, YAML, and Swagger UI

## Programmatic Usage

Generate OpenAPI documentation programmatically:

```php
use Elarion\OpenAPI\Generator\OpenApiGenerator;
use Elarion\Routing\Router;

$router = $container->make(Router::class);
$generator = new OpenApiGenerator($router, [
    'title' => 'My API',
    'version' => '1.0.0',
    'description' => 'API Documentation',
]);

$document = $generator->generate();

// Export as JSON
$json = $document->toJson();
file_put_contents('openapi.json', $json);

// Export as YAML
$yaml = $document->toYaml();
file_put_contents('openapi.yaml', $yaml);

// Get as array
$array = $document->jsonSerialize();
```

## Testing

The OpenAPI generator includes comprehensive tests:

```bash
# Run OpenAPI tests
./vendor/bin/phpunit tests/Unit/OpenAPI/OpenApiGeneratorTest.php

# Run all tests
./vendor/bin/phpunit
```

## Best Practices

1. **Use Tags** - Group related endpoints with tags for better organization
2. **Document Parameters** - Always describe path and query parameters
3. **Specify Response Codes** - Document all possible HTTP responses
4. **Leverage Validation** - Use validation rules to auto-generate schemas
5. **Provide Examples** - Include example requests/responses (future feature)
6. **Version Your API** - Update version in config when making breaking changes

## Known Limitations

- ResourceScanner is partially implemented (basic support only)
- JsonApiScanner is partially implemented (structure in place)
- ReDoc UI is not yet integrated (only Swagger UI)
- Custom schema components need manual definition
- Some PHPStan warnings for array type specifications (cosmetic)

## Roadmap

- ✅ OpenAPI 3.1 schema classes
- ✅ PHP Attributes for endpoints
- ✅ Auto-generation from routes
- ✅ Validation rules → Request schemas
- ✅ Swagger UI integration
- ✅ JSON and YAML export
- ⏳ Complete ResourceScanner
- ⏳ Complete JsonApiScanner
- ⏳ ReDoc UI integration
- ⏳ Security schemes (OAuth2, API Key, etc.)
- ⏳ Request/response examples
- ⏳ Webhooks documentation
- ⏳ Custom schema components

## Troubleshooting

### Swagger UI not loading

Check that routes are registered in your service provider:

```php
$router->get('/api/documentation', [DocumentationController::class, 'swaggerUI']);
$router->get('/api/documentation.json', [DocumentationController::class, 'json']);
$router->get('/api/documentation.yaml', [DocumentationController::class, 'yaml']);
```

### Routes not appearing in documentation

Ensure routes are registered before the OpenAPI generator runs. The generator scans the Router's registered routes.

### Validation rules not converting

Check that you're using supported validation rules. Custom rules need manual schema definition.

## Contributing

Contributions are welcome! Areas needing work:

- Complete ResourceScanner implementation
- Complete JsonApiScanner implementation
- Add ReDoc UI support
- Add security schemes
- Add request/response examples
- Improve YAML export (consider symfony/yaml)

## License

Part of ElarionStack - see main project license.
