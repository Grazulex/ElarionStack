<div align="center">

```
███████╗██╗      █████╗ ██████╗ ██╗ ██████╗ ███╗   ██╗
██╔════╝██║     ██╔══██╗██╔══██╗██║██╔═══██╗████╗  ██║
█████╗  ██║     ███████║██████╔╝██║██║   ██║██╔██╗ ██║
██╔══╝  ██║     ██╔══██║██╔══██╗██║██║   ██║██║╚██╗██║
███████╗███████╗██║  ██║██║  ██║██║╚██████╔╝██║ ╚████║
╚══════╝╚══════╝╚═╝  ╚═╝╚═╝  ╚═╝╚═╝ ╚═════╝ ╚═╝  ╚═══╝

███████╗████████╗ █████╗  ██████╗██╗  ██╗
██╔════╝╚══██╔══╝██╔══██╗██╔════╝██║ ██╔╝
███████╗   ██║   ███████║██║     █████╔╝
╚════██║   ██║   ██╔══██║██║     ██╔═██╗
███████║   ██║   ██║  ██║╚██████╗██║  ██╗
╚══════╝   ╚═╝   ╚═╝  ╚═╝ ╚═════╝╚═╝  ╚═╝
```

### Modern PHP Framework for Expressive APIs

Build elegant, maintainable, and high-performance APIs with the power of PHP 8.5

[![Packagist Version](https://img.shields.io/packagist/v/elarion/elarionstack?style=flat-square&logo=packagist)](https://packagist.org/packages/elarion/elarionstack)
[![Packagist Downloads](https://img.shields.io/packagist/dt/elarion/elarionstack?style=flat-square)](https://packagist.org/packages/elarion/elarionstack)
[![PHP Version](https://img.shields.io/packagist/php-v/elarion/elarionstack?style=flat-square&logo=php)](https://www.php.net/)
[![License](https://img.shields.io/packagist/l/elarion/elarionstack?style=flat-square)](LICENSE)
[![Tests](https://img.shields.io/badge/Tests-201%20passed-success?style=flat-square)](tests/)
[![PHPStan](https://img.shields.io/badge/PHPStan-Level%208-blue?style=flat-square)](phpstan.neon)
[![Code Style](https://img.shields.io/badge/Code%20Style-PSR--12-orange?style=flat-square)](https://www.php-fig.org/psr/psr-12/)

[Features](#features) • [Installation](#installation) • [Quick Start](#quick-start) • [Documentation](#documentation) • [Examples](#examples)

</div>

---

## 🌟 Overview

**ElarionStack** is a modern, Laravel-inspired PHP framework designed specifically for building expressive and maintainable APIs. It combines the elegance of Laravel with the performance of PHP 8.5, offering a complete toolkit for REST, JSON:API, and GraphQL development.

**Now available on Packagist! Install in seconds:**

```bash
composer create-project elarion/elarionstack my-api
```

### Why ElarionStack?

- **🚀 Performance-First**: Built on PHP 8.5 with strict typing and modern optimizations
- **🎯 PSR-Compliant**: Follows PSR-7, PSR-11, PSR-15, PSR-17 standards for maximum compatibility
- **🎨 Expressive Syntax**: Write clean, readable code with fluent interfaces and modern PHP features
- **🔧 Batteries Included**: Complete API toolkit with routing, validation, ORM, resources, and more
- **📐 SOLID Architecture**: Clean, testable, and maintainable codebase following best practices
- **✅ Fully Tested**: 201 tests with 355 assertions ensuring reliability
- **📦 Easy Install**: Available on Packagist for instant setup

---

## ✨ Features

### 🏗️ Core Architecture

#### Dependency Injection Container
```php
// PSR-11 compliant with automatic dependency resolution
$container->bind(UserRepository::class, DatabaseUserRepository::class);
$container->singleton(Logger::class, FileLogger::class);

// Auto-wiring with type hints
$service = $container->make(UserService::class);
```

**Features:**
- ✅ PSR-11 Container Interface
- ✅ Automatic dependency injection & auto-wiring
- ✅ Service providers for modular bootstrapping
- ✅ Singleton & factory bindings
- ✅ Contextual binding with `makeWith()`

#### Service Providers
```php
class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(ConnectionManager::class, function($c) {
            return new ConnectionManager($c->make(Config::class));
        });
    }

    public function boot(): void
    {
        // Bootstrap logic after all services registered
    }
}
```

### 🌐 HTTP & Routing

#### Modern Router
```php
use Elarion\Routing\Router;

// Simple routing
$router->get('/users', [UserController::class, 'index']);
$router->post('/users', [UserController::class, 'store']);

// Route parameters with constraints
$router->get('/users/{id}', [UserController::class, 'show'])
    ->where('id', '[0-9]+')
    ->name('users.show');

// Route groups
$router->group(['prefix' => '/api/v1', 'middleware' => ['auth']], function($router) {
    $router->get('/profile', [ProfileController::class, 'show']);
    $router->resource('/posts', PostController::class);
});
```

**Features:**
- ✅ FastRoute integration for performance
- ✅ Named routes with URL generation
- ✅ Route groups with prefixes & middleware
- ✅ Parameter constraints with regex
- ✅ RESTful resource routing

#### PSR-7 HTTP Messages
```php
use Elarion\Http\Message\{Response, ServerRequest};

// Fluent response creation
return Response::json(['message' => 'Success'], 201)
    ->withHeader('X-Custom', 'Value');

return Response::html('<h1>Hello</h1>');
return Response::redirect('/login');
return Response::noContent();

// Rich request interface
$body = $request->getParsedBody();
$query = $request->getQueryParams();
$header = $request->getHeaderLine('Authorization');
```

**Standards:**
- ✅ PSR-7 HTTP Message Interfaces
- ✅ PSR-17 HTTP Factories
- ✅ Immutable messages
- ✅ Stream handling for large files

#### PSR-15 Middleware Pipeline
```php
// FIFO middleware execution
$pipeline = new Pipeline();
$response = $pipeline
    ->pipe(new CorsMiddleware())
    ->pipe(new AuthMiddleware())
    ->pipe(new RateLimitMiddleware())
    ->process($request, $handler);

// Short-circuit support
class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, Handler $handler): Response
    {
        if (!$this->isAuthenticated($request)) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }
        return $handler->handle($request);
    }
}
```

### 💾 Database Layer

#### Connection Manager
```php
use Elarion\Database\ConnectionManager;

// Multiple named connections
$manager = new ConnectionManager($config);
$mysql = $manager->connection('mysql');
$postgres = $manager->connection('postgres');

// Lazy-loading with automatic reconnection
```

**Features:**
- ✅ Multi-driver support (MySQL, PostgreSQL, SQLite)
- ✅ Lazy connection loading
- ✅ Connection pooling
- ✅ Automatic prepared statements

#### Query Builder
```php
use Elarion\Database\DB;

// Fluent interface
$users = DB::table('users')
    ->select('id', 'name', 'email')
    ->where('status', '=', 'active')
    ->where('age', '>', 18)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

// Complex queries
$results = DB::table('orders')
    ->join('users', 'orders.user_id', '=', 'users.id')
    ->whereIn('status', ['pending', 'processing'])
    ->orWhere(function($query) {
        $query->where('priority', 'high')
              ->where('urgent', true);
    })
    ->groupBy('user_id')
    ->having('total', '>', 1000)
    ->get();

// Aggregates
$count = DB::table('users')->count();
$avg = DB::table('orders')->avg('total');
$sum = DB::table('sales')->sum('amount');
```

#### Active Record ORM
```php
use Elarion\Database\Model;

class User extends Model
{
    protected string $table = 'users';
    protected array $fillable = ['name', 'email', 'age'];
    protected bool $timestamps = true;
}

// CRUD operations
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);

$user = User::find(1);
$users = User::where('active', true)->get();

$user->update(['name' => 'Jane Doe']);
$user->delete();

// Mass updates
User::where('inactive', true)->update(['status' => 'archived']);
```

### 🎨 API Resources & Transformers

#### JSON Resources
```php
use Elarion\Http\Resources\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(ServerRequestInterface $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,

            // Conditional attributes
            'role' => $this->when($this->isAdmin(), $this->role),
            'permissions' => $this->when($this->isAdmin(), $this->permissions),

            // Nested resources
            'posts' => PostResource::collection($this->posts),

            // Merge conditional arrays
            'metadata' => $this->mergeWhen(isset($this->metadata), [
                'last_login' => $this->last_login,
                'created_at' => $this->created_at,
            ]),
        ];
    }
}

// Usage in controllers
return UserResource::make($user)->toResponse($request);
return UserResource::collection($users)->toResponse($request);
```

#### JSON:API Support (v1.1 Compliant)
```php
use Elarion\Http\Resources\JsonApi\JsonApiResource;

class UserJsonApiResource extends JsonApiResource
{
    public function type(): string
    {
        return 'users';
    }

    public function attributes(ServerRequestInterface $request): array
    {
        return [
            'name' => $this->resource->name,
            'email' => $this->resource->email,
        ];
    }

    public function relationships(ServerRequestInterface $request): array
    {
        return [
            'posts' => $this->relationship(
                'posts',
                PostJsonApiResource::collection($this->resource->posts)
            ),
        ];
    }
}

// Automatic JSON:API formatting with compound documents
return UserJsonApiResource::make($user)->toResponse($request);
```

**JSON:API Features:**
- ✅ Spec v1.1 compliant
- ✅ Resource objects with type, id, attributes, relationships
- ✅ Compound documents with included resources
- ✅ Pagination with links (first, last, prev, next)
- ✅ Error responses following spec
- ✅ Content-Type: `application/vnd.api+json`

### ✅ Validation System

```php
use Elarion\Validation\Validator;

$validator = new Validator($request->getParsedBody(), [
    'name' => 'required|string|min:3|max:255',
    'email' => 'required|email',
    'age' => 'integer|min:18|max:120',
    'website' => 'url',
    'tags' => 'array',
    'is_active' => 'boolean',

    // Nested validation with dot notation
    'address.street' => 'required|string',
    'address.city' => 'required|string',

    // Wildcard validation for arrays
    'items.*.name' => 'required|string',
    'items.*.quantity' => 'required|integer|min:1',
]);

if (!$validator->validate()) {
    return Response::json([
        'errors' => $validator->errors()
    ], 422);
}

$data = $validator->validated();
```

**Built-in Rules:**
- `required`, `email`, `url`, `date`
- `string`, `integer`, `numeric`, `boolean`, `array`
- `min:n`, `max:n` (supports strings, numbers, arrays)

**Custom Rules:**
```php
// Closure-based
$validator->addRule('username', function($value) {
    return preg_match('/^[a-zA-Z0-9_]+$/', $value);
}, 'Username must be alphanumeric');

// Class-based
class UniqueEmailRule implements RuleInterface
{
    public function passes(string $field, mixed $value, array $data): bool
    {
        return !User::where('email', $value)->exists();
    }

    public function message(string $field): string
    {
        return "The {$field} is already taken.";
    }
}
```

### 📦 Collections

```php
use Elarion\Support\Collection;

$collection = collect([1, 2, 3, 4, 5]);

// Transformations
$mapped = $collection->map(fn($n) => $n * 2);
$filtered = $collection->filter(fn($n) => $n > 2);
$reduced = $collection->reduce(fn($carry, $n) => $carry + $n, 0);

// Utilities
$sum = $collection->sum();
$avg = $collection->avg();
$first = $collection->first();
$last = $collection->last();

// Sorting & grouping
$sorted = $collection->sort();
$sortedBy = $collection->sortBy('name');
$grouped = $collection->groupBy('category');

// Working with nested data
$users = collect([
    ['name' => 'John', 'email' => 'john@example.com'],
    ['name' => 'Jane', 'email' => 'jane@example.com'],
]);

$emails = $users->pluck('email');
$names = $users->pluck('name')->implode(', ');

// Method chaining
$result = collect($data)
    ->filter(fn($item) => $item['active'])
    ->map(fn($item) => $item['value'])
    ->sum();
```

### 📚 OpenAPI / Swagger Documentation

```php
use Elarion\OpenAPI\Attributes\{Get, Post, PathParameter, QueryParameter, RequestBody};

class UserController
{
    #[Get(
        path: '/api/users',
        summary: 'List all users',
        description: 'Returns paginated list of users',
        tags: ['Users']
    )]
    #[QueryParameter('page', 'integer', 'Page number')]
    #[QueryParameter('limit', 'integer', 'Items per page')]
    public function index(ServerRequestInterface $request): Response
    {
        // Automatically generates OpenAPI documentation
    }

    #[Post(
        path: '/api/users',
        summary: 'Create new user',
        tags: ['Users']
    )]
    #[RequestBody('User data', required: true)]
    public function store(ServerRequestInterface $request): Response
    {
        // Validation rules auto-convert to OpenAPI schemas
    }
}
```

**Access Documentation:**
- Swagger UI: `http://localhost:8000/api/documentation`
- ReDoc UI: `http://localhost:8000/api/redoc`
- JSON: `http://localhost:8000/api/documentation.json`
- YAML: `http://localhost:8000/api/documentation.yaml`

### 🛠️ Helper Functions

```php
// Environment variables with type conversion
$debug = env('APP_DEBUG', false); // bool
$port = env('APP_PORT', 8000); // int

// Configuration with dot notation
$dbHost = config('database.connections.mysql.host');

// Debugging
dd($data); // dump and die
dump($data); // dump without stopping

// Response helpers
return response()->json(['status' => 'ok']);
return response()->redirect('/home');

// Collections
$collection = collect([1, 2, 3]);

// Value utilities
$value = value(fn() => expensiveComputation());
$result = tap($user, fn($u) => $u->notify());
```

### ⚙️ Configuration System

```php
// config/database.php
return [
    'default' => env('DB_CONNECTION', 'mysql'),
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', 'localhost'),
            'database' => env('DB_DATABASE', 'app'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
        ],
    ],
];

// Access anywhere
$connection = config('database.default');
$host = config('database.connections.mysql.host');
```

---

## 📦 Installation

### Requirements

- **PHP 8.4+** or **PHP 8.5+** with extensions:
  - PDO (pdo_mysql, pdo_pgsql, or pdo_sqlite)
  - mbstring
  - json
- **Composer 2.x**
- **Docker** (optional, for containerized development)

### Method 1: Via Composer (Recommended)

Create a new project using Composer:

```bash
# Create a new ElarionStack project
composer create-project elarion/elarionstack my-api

# Navigate to project directory
cd my-api

# Copy environment file
cp .env.example .env

# Start development server
php -S localhost:8000 -t public
```

**Or** install as a dependency in an existing project:

```bash
composer require elarion/elarionstack
```

### Method 2: From Source (Development)

Clone the repository for development or contribution:

```bash
# Clone the repository
git clone https://github.com/elarion/elarionstack.git
cd elarionstack

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Start development server
php -S localhost:8000 -t public
```

### Method 3: Docker Setup

Run ElarionStack in a containerized environment:

```bash
# Start PHP 8.5 container
docker-compose -f docker-compose-php85.yml up -d

# Enter container
docker exec -it elarionstack_php85 bash

# Install dependencies
composer install

# Start development server
php -S localhost:8000 -t public
```

### Post-Installation

After installation, your project structure will look like:

```
my-api/
├── app/             # Your application code
│   ├── Controllers/ # Your controllers
│   ├── Models/      # Your models
│   └── Resources/   # Your API resources
├── config/          # Configuration files
├── public/          # Web root (index.php)
├── routes/          # Route definitions
├── vendor/          # Composer dependencies
│   └── elarion/
│       └── elarionstack/  # Framework code is here
├── tests/           # Your test suite
├── .env.example     # Environment template
└── composer.json    # Dependencies
```

Configure your `.env` file with your database credentials and other settings:

```bash
# .env
APP_ENV=development
APP_DEBUG=true

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

Verify installation by running tests:

```bash
composer test
```

---

## 🎯 Getting Started with ElarionStack

Now that you've installed ElarionStack from Packagist, let's build your first API in **5 minutes**!

### Your First API Endpoint

**1. Create a simple route** in `routes/api.php`:

```php
use Elarion\Routing\Router;
use Elarion\Http\Message\Response;

$router->get('/api/status', function($request) {
    return Response::json([
        'status' => 'running',
        'framework' => 'ElarionStack',
        'version' => '1.0.0'
    ]);
});
```

**2. Test it:**
```bash
curl http://localhost:8000/api/status
```

**Response:**
```json
{
    "status": "running",
    "framework": "ElarionStack",
    "version": "1.0.0"
}
```

### Build a Complete CRUD API

Let's create a simple **Blog Post API** with full CRUD operations:

**1. Define routes** (`routes/api.php`):

```php
$router->group(['prefix' => '/api/posts'], function($router) {
    $router->get('/', [PostController::class, 'index']);      // GET /api/posts
    $router->post('/', [PostController::class, 'store']);     // POST /api/posts
    $router->get('/{id}', [PostController::class, 'show']);   // GET /api/posts/1
    $router->put('/{id}', [PostController::class, 'update']); // PUT /api/posts/1
    $router->delete('/{id}', [PostController::class, 'destroy']); // DELETE /api/posts/1
});
```

**2. Create the controller** (`app/Http/Controllers/PostController.php`):

```php
namespace App\Http\Controllers;

use Elarion\Http\Message\Response;
use Elarion\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;

class PostController
{
    private array $posts = []; // In-memory storage for demo

    public function index(ServerRequestInterface $request): Response
    {
        return Response::json([
            'data' => $this->posts,
            'total' => count($this->posts)
        ]);
    }

    public function store(ServerRequestInterface $request): Response
    {
        // Validate input
        $validator = new Validator($request->getParsedBody(), [
            'title' => 'required|string|min:3|max:255',
            'content' => 'required|string|min:10',
            'author' => 'required|string|max:100',
        ]);

        if (!$validator->validate()) {
            return Response::json([
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 422);
        }

        // Create post
        $data = $validator->validated();
        $post = [
            'id' => count($this->posts) + 1,
            'title' => $data['title'],
            'content' => $data['content'],
            'author' => $data['author'],
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $this->posts[] = $post;

        return Response::json($post, 201);
    }

    public function show(int $id): Response
    {
        $post = $this->findPost($id);

        if (!$post) {
            return Response::json(['error' => 'Post not found'], 404);
        }

        return Response::json($post);
    }

    public function update(int $id, ServerRequestInterface $request): Response
    {
        $post = $this->findPost($id);

        if (!$post) {
            return Response::json(['error' => 'Post not found'], 404);
        }

        $validator = new Validator($request->getParsedBody(), [
            'title' => 'string|min:3|max:255',
            'content' => 'string|min:10',
        ]);

        if (!$validator->validate()) {
            return Response::json([
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 422);
        }

        $updated = array_merge($post, $validator->validated());
        $updated['updated_at'] = date('Y-m-d H:i:s');

        return Response::json($updated);
    }

    public function destroy(int $id): Response
    {
        $post = $this->findPost($id);

        if (!$post) {
            return Response::json(['error' => 'Post not found'], 404);
        }

        // Delete post (demo)
        return Response::noContent();
    }

    private function findPost(int $id): ?array
    {
        foreach ($this->posts as $post) {
            if ($post['id'] === $id) {
                return $post;
            }
        }
        return null;
    }
}
```

**3. Test your API:**

```bash
# Create a post
curl -X POST http://localhost:8000/api/posts \
  -H "Content-Type: application/json" \
  -d '{
    "title": "My First Post",
    "content": "This is the content of my first blog post!",
    "author": "John Doe"
  }'

# List all posts
curl http://localhost:8000/api/posts

# Get a specific post
curl http://localhost:8000/api/posts/1

# Update a post
curl -X PUT http://localhost:8000/api/posts/1 \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Updated Title",
    "content": "Updated content!"
  }'

# Delete a post
curl -X DELETE http://localhost:8000/api/posts/1
```

### What's Next?

Now that you have a working API, explore these features:

- **[Database & ORM](docs/ORM-Model.md)** - Connect to MySQL/PostgreSQL and use the Active Record ORM
- **[API Resources](docs/API-Resources.md)** - Transform your data with elegant Resource classes
- **[JSON:API](docs/JSON-API.md)** - Build JSON:API v1.1 compliant APIs
- **[Validation](docs/Validation.md)** - Learn about all validation rules and custom validators
- **[OpenAPI](docs/OpenAPI.md)** - Auto-generate interactive API documentation
- **[Middleware](docs/Middleware-Pipeline.md)** - Add authentication, CORS, rate limiting

---

## 🚀 Quick Start

### Step 0: Install ElarionStack

```bash
# Create a new project from Packagist
composer create-project elarion/elarionstack my-api
cd my-api

# Configure your environment
cp .env.example .env
# Edit .env with your database credentials

# Start the development server
php -S localhost:8000 -t public
```

Your API is now running at `http://localhost:8000`! 🎉

### Step 1: Create Your First Route

```php
// routes/api.php
use Elarion\Routing\Router;
use Elarion\Http\Message\Response;

$router->get('/hello', function($request) {
    return Response::json([
        'message' => 'Hello, ElarionStack!',
        'version' => '1.0.0',
        'timestamp' => date('c')
    ]);
});

$router->get('/users/{id}', [UserController::class, 'show'])
    ->where('id', '[0-9]+')
    ->name('users.show');
```

**Test it:**
```bash
curl http://localhost:8000/hello
# {"message":"Hello, ElarionStack!","version":"1.0.0","timestamp":"2024-..."}
```

### Step 2: Create a Controller

```php
// app/Http/Controllers/UserController.php
namespace App\Http\Controllers;

use Elarion\Http\Message\Response;
use Elarion\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;

class UserController
{
    public function index(ServerRequestInterface $request): Response
    {
        $users = User::all();
        return UserResource::collection($users)->toResponse($request);
    }

    public function store(ServerRequestInterface $request): Response
    {
        $validator = new Validator($request->getParsedBody(), [
            'name' => 'required|string|min:3|max:255',
            'email' => 'required|email',
        ]);

        if (!$validator->validate()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }

        $user = User::create($validator->validated());
        return UserResource::make($user)->toResponse($request);
    }

    public function show(int $id): Response
    {
        $user = User::find($id);

        if (!$user) {
            return Response::json(['error' => 'Not found'], 404);
        }

        return UserResource::make($user)->toResponse($request);
    }
}
```

### 3. Create a Model

```php
// app/Models/User.php
namespace App\Models;

use Elarion\Database\Model;

class User extends Model
{
    protected string $table = 'users';

    protected array $fillable = [
        'name',
        'email',
        'password',
    ];

    protected bool $timestamps = true;
}
```

### 4. Create an API Resource

```php
// app/Http/Resources/UserResource.php
namespace App\Http\Resources;

use Elarion\Http\Resources\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(ServerRequestInterface $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at,
        ];
    }
}
```

### 5. Configure Database

```bash
# .env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

---

## 📖 Documentation

Comprehensive documentation is available in the `docs/` directory:

- **[Container & Dependency Injection](docs/Container-DI.md)** - DI container and service providers
- **[HTTP & Routing](docs/Router-HTTP.md)** - Routing, PSR-7 messages, PSR-17 factories
- **[Middleware Pipeline](docs/Middleware-Pipeline.md)** - PSR-15 middleware system
- **[Database](docs/Database-Connection-Manager.md)** - Connection manager and configuration
- **[Query Builder](docs/Query-Builder.md)** - Fluent database queries
- **[ORM Models](docs/ORM-Model.md)** - Active Record pattern
- **[API Resources](docs/API-Resources.md)** - Data transformation and presentation
- **[JSON:API](docs/JSON-API.md)** - JSON:API v1.1 implementation
- **[Validation](docs/Validation.md)** - Validation system and custom rules
- **[Collections](docs/Collection.md)** - Working with collections
- **[OpenAPI](docs/OpenAPI.md)** - API documentation generation
- **[Configuration](docs/Configuration-System.md)** - Configuration management

---

## 🧪 Testing

ElarionStack includes a comprehensive test suite with **201 tests** and **355 assertions**.

```bash
# Run all tests
composer test

# Run tests with coverage
composer test:coverage

# Run specific test suite
./vendor/bin/phpunit tests/Unit/Container

# Static analysis (PHPStan Level 8)
composer analyse

# Code style check (PSR-12)
composer format

# Run all quality checks
composer quality
```

**Test Structure:**
```
tests/
├── Unit/
│   ├── Container/
│   ├── Database/
│   ├── Http/
│   ├── Routing/
│   ├── Validation/
│   └── Support/
└── Integration/
    ├── Database/
    └── Http/
```

---

## 🎯 Examples

### Building a RESTful API

```php
// routes/api.php
$router->group(['prefix' => '/api/v1'], function($router) {

    // Public routes
    $router->post('/register', [AuthController::class, 'register']);
    $router->post('/login', [AuthController::class, 'login']);

    // Protected routes
    $router->group(['middleware' => ['auth:api']], function($router) {

        // User profile
        $router->get('/profile', [ProfileController::class, 'show']);
        $router->put('/profile', [ProfileController::class, 'update']);

        // Posts resource
        $router->get('/posts', [PostController::class, 'index']);
        $router->post('/posts', [PostController::class, 'store']);
        $router->get('/posts/{id}', [PostController::class, 'show']);
        $router->put('/posts/{id}', [PostController::class, 'update']);
        $router->delete('/posts/{id}', [PostController::class, 'destroy']);

        // Comments
        $router->post('/posts/{postId}/comments', [CommentController::class, 'store']);
        $router->get('/posts/{postId}/comments', [CommentController::class, 'index']);
    });
});
```

### JSON:API Implementation

```php
// Full JSON:API resource with relationships
class PostJsonApiResource extends JsonApiResource
{
    public function type(): string
    {
        return 'posts';
    }

    public function attributes(ServerRequestInterface $request): array
    {
        return [
            'title' => $this->resource->title,
            'content' => $this->resource->content,
            'published_at' => $this->resource->published_at,
        ];
    }

    public function relationships(ServerRequestInterface $request): array
    {
        return [
            'author' => $this->relationship(
                'users',
                UserJsonApiResource::make($this->resource->author),
                links: [
                    'self' => "/api/posts/{$this->id()}/relationships/author",
                    'related' => "/api/posts/{$this->id()}/author",
                ]
            ),
            'comments' => $this->relationship(
                'comments',
                CommentJsonApiResource::collection($this->resource->comments),
                meta: ['count' => count($this->resource->comments)]
            ),
        ];
    }

    public function links(ServerRequestInterface $request): array
    {
        return [
            'self' => "/api/posts/{$this->id()}",
        ];
    }
}

// Controller
public function show(int $id, ServerRequestInterface $request): Response
{
    $post = Post::with(['author', 'comments'])->find($id);

    if (!$post) {
        return JsonApiErrorResponse::notFound('Post not found');
    }

    return PostJsonApiResource::make($post)
        ->toResponse($request);
}
```

### Complex Query Builder Usage

```php
// Advanced queries
$orders = DB::table('orders')
    ->select('orders.*', 'users.name as user_name')
    ->join('users', 'orders.user_id', '=', 'users.id')
    ->leftJoin('shipping', 'orders.id', '=', 'shipping.order_id')
    ->where('orders.status', '=', 'pending')
    ->where(function($query) {
        $query->where('orders.priority', 'high')
              ->orWhere('orders.amount', '>', 1000);
    })
    ->whereIn('orders.payment_method', ['credit_card', 'paypal'])
    ->whereNotNull('shipping.tracking_number')
    ->whereBetween('orders.created_at', ['2024-01-01', '2024-12-31'])
    ->groupBy('orders.user_id')
    ->having('total_amount', '>', 5000)
    ->orderBy('orders.created_at', 'desc')
    ->limit(50)
    ->offset(0)
    ->get();

// Aggregates
$stats = [
    'total_orders' => DB::table('orders')->count(),
    'total_revenue' => DB::table('orders')->sum('amount'),
    'avg_order_value' => DB::table('orders')->avg('amount'),
    'max_order' => DB::table('orders')->max('amount'),
    'min_order' => DB::table('orders')->min('amount'),
];

// Raw queries with bindings
$users = DB::table('users')
    ->whereRaw('YEAR(created_at) = ?', [2024])
    ->get();
```

---

## 🏗️ Project Structure

### Your Application Structure (after `composer create-project`)

When you create a new project with `composer create-project elarion/elarionstack my-api`, you get:

```
my-api/
├── app/                     # Your application code
│   ├── Controllers/         # Your HTTP controllers
│   ├── Models/              # Your database models
│   ├── Resources/           # Your API resources
│   └── Middleware/          # Your custom middleware
├── config/                  # Configuration files
│   ├── app.php
│   ├── database.php
│   └── openapi.php
├── public/                  # Web root
│   └── index.php           # Entry point
├── routes/                  # Route definitions
│   ├── api.php
│   └── web.php
├── vendor/                  # Composer dependencies
│   └── elarion/
│       └── elarionstack/   # Framework code (don't edit)
│           ├── src/        # Framework source code
│           ├── tests/      # Framework tests
│           └── docs/       # Framework documentation
├── tests/                   # Your application tests
│   ├── Unit/
│   └── Feature/
├── .env.example            # Environment template
├── composer.json           # Your dependencies
└── README.md               # Your project readme
```

### Framework Repository Structure (for contributors)

If you're contributing to ElarionStack itself (via `git clone`):

```
elarionstack/
├── src/                     # Framework source code
│   ├── Container/          # DI Container (PSR-11)
│   ├── Database/           # Database layer
│   │   ├── Query/         # Query builder
│   │   ├── Model.php      # Active Record ORM
│   │   └── ConnectionManager.php
│   ├── Http/
│   │   ├── Message/       # PSR-7 Messages
│   │   ├── Factories/     # PSR-17 Factories
│   │   ├── Middleware/    # PSR-15 Middleware
│   │   └── Resources/     # API Resources
│   │       └── JsonApi/   # JSON:API Resources
│   ├── OpenAPI/           # OpenAPI/Swagger generator
│   ├── Providers/         # Service Providers
│   ├── Routing/           # HTTP Router
│   ├── Support/           # Helpers & utilities
│   │   ├── Collection.php
│   │   └── helpers.php
│   └── Validation/        # Validation system
├── tests/                  # Framework test suite
│   ├── Unit/
│   └── Integration/
├── docs/                   # Framework documentation
│   ├── API-Resources.md
│   ├── Container-DI.md
│   ├── Database-Connection-Manager.md
│   └── ...
├── config/                 # Default configuration
├── backlog/               # Project management
├── .env.example          # Environment template
├── composer.json         # Framework dependencies
├── phpstan.neon          # Static analysis config
├── phpunit.xml           # Test configuration
└── README.md             # This file
```

> **Note:** When using ElarionStack via Composer, you work in the **app/** directory for your code. The framework itself lives in **vendor/elarion/elarionstack/** and should not be modified directly.

---

## 🤝 Contributing

We welcome contributions! Please follow these guidelines:

### Development Setup

```bash
# Fork and clone the repository
git clone https://github.com/YOUR_USERNAME/elarionstack.git
cd elarionstack

# Install dependencies
composer install

# Run tests to ensure everything works
composer test
```

### Coding Standards

- **PSR-12** code style
- **PSR-4** autoloading
- **Strict typing**: All files must declare `strict_types=1`
- **Type hints**: Required for all parameters and return types
- **PHPStan Level 8**: All code must pass static analysis
- **Tests**: New features require tests (aim for 80%+ coverage)

### Workflow

1. Create a feature branch: `git checkout -b feature/my-feature`
2. Write your code following standards
3. Add tests for new functionality
4. Run quality checks: `composer quality`
5. Commit with clear messages: `feat: add new validation rule`
6. Push and create a Pull Request

### Commit Convention

Follow [Conventional Commits](https://www.conventionalcommits.org/):

- `feat:` New feature
- `fix:` Bug fix
- `docs:` Documentation changes
- `refactor:` Code refactoring
- `test:` Test additions/changes
- `perf:` Performance improvements
- `chore:` Maintenance tasks

---

## 📊 Quality Metrics

```bash
✅ Tests: 201 passed (355 assertions)
✅ PHPStan: Level 8 (strictest)
✅ Code Style: PSR-12 compliant
✅ Coverage: Comprehensive unit & integration tests
✅ PHP Version: 8.5+ with modern features
```

---

## 🗺️ Roadmap

### Completed Features (15/15) ✅

- [x] Container & Dependency Injection (PSR-11)
- [x] Service Providers
- [x] Configuration System
- [x] HTTP Router with FastRoute
- [x] PSR-7 HTTP Messages
- [x] PSR-17 HTTP Factories
- [x] PSR-15 Middleware Pipeline
- [x] Database Connection Manager
- [x] Fluent Query Builder
- [x] Active Record ORM
- [x] API Resources & Transformers
- [x] JSON:API v1.1 Support
- [x] Validation System
- [x] Collection Class
- [x] OpenAPI/Swagger Documentation

### Future Enhancements

- [ ] **Authentication & Authorization**
  - JWT authentication
  - API token management
  - Role-based access control (RBAC)
  - OAuth2 integration

- [ ] **Database Advanced Features**
  - Database migrations
  - Database seeders
  - Eloquent-style relationships
  - Query scopes and mutators

- [ ] **Caching Layer**
  - PSR-6 & PSR-16 cache interfaces
  - Redis support
  - Memcached support
  - File-based caching

- [ ] **Queue System**
  - Background job processing
  - Redis queue driver
  - Database queue driver
  - Failed job handling

- [ ] **Event System**
  - Event dispatcher (PSR-14)
  - Event listeners
  - Event subscribers

- [ ] **GraphQL Support**
  - GraphQL server integration
  - Schema builder
  - Query resolvers
  - Mutations

- [ ] **CLI Tools**
  - Artisan-like command system
  - Code generators (models, controllers, resources)
  - Database migrations CLI
  - Development server

- [ ] **Testing Utilities**
  - HTTP testing helpers
  - Database factories
  - Mock helpers
  - Integration test utilities

---

## 📄 License

ElarionStack is open-sourced software licensed under the **[MIT License](LICENSE)**.

```
MIT License

Copyright (c) 2024 ElarionStack

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

## 🙏 Acknowledgments

ElarionStack is inspired by the excellent work of:

- **[Laravel](https://laravel.com/)** - For API design philosophy and elegant syntax
- **[Symfony](https://symfony.com/)** - For robust component architecture
- **[PHP-FIG](https://www.php-fig.org/)** - For PSR standards

---

## 📞 Support & Community

- **Documentation**: [docs/](docs/)
- **Issues**: [GitHub Issues](https://github.com/elarion/elarionstack/issues)
- **Discussions**: [GitHub Discussions](https://github.com/elarion/elarionstack/discussions)

---

<div align="center">

**Built with ❤️ using PHP 8.5**

**[⬆ Back to Top](#)**

</div>
