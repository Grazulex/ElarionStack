# Router HTTP - ElarionStack

## Vue d'ensemble

Le Router HTTP d'ElarionStack fournit un système de routing performant basé sur **FastRoute**, avec support des groupes, middlewares, routes nommées, et contraintes de paramètres.

## Architecture

### Principes SOLID Appliqués

- **SRP** : RouteCollector (collection), RouteDispatcher (dispatch), Router (facade)
- **OCP** : Extensible via adapters pour d'autres libraries
- **LSP** : Toutes les routes respectent RouteInterface
- **ISP** : Interfaces focalisées et minimales
- **DIP** : Dépend d'abstractions (contracts), pas de FastRoute directement

### Composants Principaux

```
src/Routing/
├── Contracts/
│   ├── RouteInterface.php
│   ├── RouteCollectorInterface.php
│   ├── RouteDispatcherInterface.php
│   └── RouteMatchInterface.php
├── Adapters/
│   ├── FastRouteCollector.php
│   └── FastRouteDispatcher.php
├── Route.php                      # Value Object pour routes
├── Router.php                     # Facade principal
└── RouteGroup.php                 # Groupes de routes
```

## Utilisation Basique

### Définir des Routes

```php
use Elarion\Routing\Router;

$router = new Router();

// GET route
$router->get('/users', [UserController::class, 'index']);

// POST route
$router->post('/users', [UserController::class, 'store']);

// PUT route
$router->put('/users/{id}', [UserController::class, 'update']);

// DELETE route
$router->delete('/users/{id}', [UserController::class, 'destroy']);

// PATCH route
$router->patch('/users/{id}', [UserController::class, 'patch']);

// OPTIONS route
$router->options('/users', [UserController::class, 'options']);
```

### Paramètres de Route

```php
// Paramètre simple
$router->get('/users/{id}', function ($request) {
    $id = $request->getAttribute('id');
    return Response::json(['user_id' => $id]);
});

// Multiples paramètres
$router->get('/posts/{postId}/comments/{commentId}',
    [CommentController::class, 'show']
);

// Paramètre optionnel
$router->get('/users/{id?}', [UserController::class, 'show']);
```

### Handlers

```php
// Controller + Méthode
$router->get('/users', [UserController::class, 'index']);

// Closure
$router->get('/health', function ($request) {
    return Response::json(['status' => 'ok']);
});

// Fonction
$router->get('/info', 'phpinfo');

// Invokable class
class HomeController
{
    public function __invoke($request) {
        return Response::html('<h1>Home</h1>');
    }
}
$router->get('/', HomeController::class);
```

## Routes Nommées

### Définir un Nom

```php
$router->get('/users/{id}', [UserController::class, 'show'])
    ->name('users.show');

$router->post('/users', [UserController::class, 'store'])
    ->name('users.store');
```

### Générer des URLs

```php
// URL simple
$url = $router->url('users.show', ['id' => 123]);
// Retourne: /users/123

// URL avec plusieurs paramètres
$router->get('/posts/{postId}/comments/{commentId}', [...])
    ->name('comments.show');

$url = $router->url('comments.show', [
    'postId' => 456,
    'commentId' => 789
]);
// Retourne: /posts/456/comments/789
```

### Routes Nommées dans les Templates

```php
// Dans un controller
public function index()
{
    $editUrl = $router->url('users.edit', ['id' => $user->id]);

    return Response::html(view('users.index', [
        'editUrl' => $editUrl
    ]));
}
```

## Contraintes de Paramètres

### Where - Contraintes Regex

```php
// ID numérique seulement
$router->get('/users/{id}', [UserController::class, 'show'])
    ->where('id', '[0-9]+');

// Slug alphanumérique avec tirets
$router->get('/posts/{slug}', [PostController::class, 'show'])
    ->where('slug', '[a-z0-9-]+');

// Multiple contraintes
$router->get('/posts/{year}/{month}/{slug}', [PostController::class, 'show'])
    ->where([
        'year' => '[0-9]{4}',
        'month' => '[0-9]{2}',
        'slug' => '[a-z0-9-]+',
    ]);
```

### Contraintes Communes

```php
// UUID
->where('id', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')

// Email
->where('email', '[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}')

// Date (YYYY-MM-DD)
->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}')

// Langue (en, fr, es, etc.)
->where('locale', '[a-z]{2}')
```

## Groupes de Routes

### Préfixe d'URI

```php
$router->group(['prefix' => '/api/v1'], function ($router) {
    $router->get('/users', [UserController::class, 'index']);
    // URI: /api/v1/users

    $router->get('/posts', [PostController::class, 'index']);
    // URI: /api/v1/posts
});
```

### Middleware de Groupe

```php
$router->group(['middleware' => ['auth', 'admin']], function ($router) {
    $router->get('/admin/users', [AdminController::class, 'users']);
    $router->get('/admin/settings', [AdminController::class, 'settings']);
    // Toutes ces routes passent par 'auth' puis 'admin'
});
```

### Groupes Imbriqués

```php
$router->group(['prefix' => '/api'], function ($router) {

    // /api/v1/*
    $router->group(['prefix' => '/v1'], function ($router) {
        $router->get('/users', [UserController::class, 'index']);
        // URI: /api/v1/users
    });

    // /api/v2/*
    $router->group(['prefix' => '/v2'], function ($router) {
        $router->get('/users', [UserV2Controller::class, 'index']);
        // URI: /api/v2/users
    });
});
```

### Attributs Multiples

```php
$router->group([
    'prefix' => '/admin',
    'middleware' => ['auth', 'admin'],
    'namespace' => 'App\\Controllers\\Admin',
], function ($router) {
    $router->get('/dashboard', 'DashboardController@index');
    $router->get('/users', 'UserController@index');
});
```

## Middlewares

### Middleware Global

```php
// Appliqué à toutes les routes
$pipeline = new MiddlewarePipeline();
$pipeline
    ->pipe(new CorsMiddleware())
    ->pipe(new LoggingMiddleware());
```

### Middleware par Route

```php
$router->get('/admin', [AdminController::class, 'index'])
    ->middleware(AuthMiddleware::class)
    ->middleware(AdminMiddleware::class);

// Ou avec array
$router->get('/admin', [AdminController::class, 'index'])
    ->middleware([
        AuthMiddleware::class,
        AdminMiddleware::class,
    ]);
```

### Middleware par Groupe

```php
$router->group(['middleware' => 'auth'], function ($router) {
    $router->get('/profile', [ProfileController::class, 'show']);
    $router->put('/profile', [ProfileController::class, 'update']);
    // Les deux routes passent par 'auth'
});
```

## Dispatch et Matching

### Dispatcher une Requête

```php
use Elarion\Routing\Router;

$router = new Router();

// Définir les routes
$router->get('/users', [UserController::class, 'index']);
$router->get('/users/{id}', [UserController::class, 'show']);

// Dispatcher
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

$match = $router->dispatch($method, $uri);
```

### RouteMatch

```php
interface RouteMatchInterface
{
    public function isFound(): bool;
    public function isMethodNotAllowed(): bool;
    public function getRoute(): ?RouteInterface;
    public function getParameters(): array;
    public function getAllowedMethods(): array;
}

// Utilisation
if ($match->isFound()) {
    $route = $match->getRoute();
    $params = $match->getParameters();

    // Exécuter le handler
    $handler = $route->getHandler();
    $response = $handler($request);

} elseif ($match->isMethodNotAllowed()) {
    $allowed = $match->getAllowedMethods();
    return Response::json([
        'error' => 'Method Not Allowed',
        'allowed' => $allowed
    ], 405);

} else {
    return Response::json([
        'error' => 'Not Found'
    ], 404);
}
```

## Méthodes HTTP Avancées

### ANY - Toutes les Méthodes

```php
// Répond à GET, POST, PUT, PATCH, DELETE, etc.
$router->any('/webhook', [WebhookController::class, 'handle']);
```

### MATCH - Méthodes Spécifiques

```php
// Répond uniquement à GET et POST
$router->match(['GET', 'POST'], '/form', [FormController::class, 'handle']);

// API avec GET, PUT, DELETE
$router->match(['GET', 'PUT', 'DELETE'], '/resource/{id}', [
    ResourceController::class, 'handle'
]);
```

### Resource Routes

```php
// Pattern RESTful complet
$router->get('/posts', [PostController::class, 'index']);        // Liste
$router->get('/posts/{id}', [PostController::class, 'show']);    // Afficher
$router->post('/posts', [PostController::class, 'store']);       // Créer
$router->put('/posts/{id}', [PostController::class, 'update']);  // Modifier
$router->delete('/posts/{id}', [PostController::class, 'destroy']); // Supprimer

// Avec noms
$router->get('/posts', [PostController::class, 'index'])->name('posts.index');
$router->get('/posts/{id}', [PostController::class, 'show'])->name('posts.show');
$router->post('/posts', [PostController::class, 'store'])->name('posts.store');
$router->put('/posts/{id}', [PostController::class, 'update'])->name('posts.update');
$router->delete('/posts/{id}', [PostController::class, 'destroy'])->name('posts.destroy');
```

## Patterns Avancés

### API Versioning

```php
// Version dans l'URI
$router->group(['prefix' => '/api/v1'], function ($router) {
    $router->get('/users', [V1\UserController::class, 'index']);
});

$router->group(['prefix' => '/api/v2'], function ($router) {
    $router->get('/users', [V2\UserController::class, 'index']);
});

// Version via header
$router->get('/api/users', function ($request) {
    $version = $request->getHeaderLine('Accept-Version');

    return match($version) {
        'v1' => (new V1\UserController())->index($request),
        'v2' => (new V2\UserController())->index($request),
        default => Response::json(['error' => 'Invalid version'], 400),
    };
});
```

### Subdomain Routing

```php
// Note: Nécessite configuration serveur web
$host = $_SERVER['HTTP_HOST'];

if (str_starts_with($host, 'api.')) {
    // Routes API
    $router->group(['prefix' => '/v1'], function ($router) {
        $router->get('/users', [ApiController::class, 'users']);
    });
} elseif (str_starts_with($host, 'admin.')) {
    // Routes Admin
    $router->get('/dashboard', [AdminController::class, 'dashboard']);
}
```

### Locale/Language Routes

```php
$router->group(['prefix' => '/{locale}'], function ($router) {
    $router->get('/about', [PageController::class, 'about'])
        ->where('locale', 'en|fr|es');

    $router->get('/contact', [PageController::class, 'contact'])
        ->where('locale', 'en|fr|es');
});

// URIs: /en/about, /fr/about, /es/about
```

### Fallback Route

```php
// Définir toutes les routes normales d'abord
$router->get('/users', [UserController::class, 'index']);
$router->get('/posts', [PostController::class, 'index']);

// Route fallback (404 handler)
$router->any('/{path:.*}', function ($request) {
    return Response::json([
        'error' => 'Not Found',
        'message' => 'The requested resource was not found'
    ], 404);
});
```

## Testing

### Test du Router

```php
use PHPUnit\Framework\TestCase;
use Elarion\Routing\Router;

class RouterTest extends TestCase
{
    public function test_routes_to_correct_handler(): void
    {
        $router = new Router();
        $router->get('/users', [UserController::class, 'index']);

        $match = $router->dispatch('GET', '/users');

        $this->assertTrue($match->isFound());
        $this->assertEquals([UserController::class, 'index'], $match->getRoute()->getHandler());
    }

    public function test_extracts_route_parameters(): void
    {
        $router = new Router();
        $router->get('/users/{id}', [UserController::class, 'show']);

        $match = $router->dispatch('GET', '/users/123');

        $this->assertTrue($match->isFound());
        $this->assertEquals(['id' => '123'], $match->getParameters());
    }

    public function test_respects_where_constraints(): void
    {
        $router = new Router();
        $router->get('/users/{id}', [UserController::class, 'show'])
            ->where('id', '[0-9]+');

        // Valide
        $match = $router->dispatch('GET', '/users/123');
        $this->assertTrue($match->isFound());

        // Invalide
        $match = $router->dispatch('GET', '/users/abc');
        $this->assertFalse($match->isFound());
    }
}
```

### Test d'Intégration

```php
public function test_full_request_cycle(): void
{
    $router = new Router();
    $router->get('/users/{id}', function ($request) {
        $id = $request->getAttribute('id');
        return Response::json(['id' => $id]);
    });

    // Simuler une requête
    $request = new ServerRequest('GET', new Uri('http://localhost/users/123'));
    $match = $router->dispatch('GET', '/users/123');

    $this->assertTrue($match->isFound());

    // Ajouter paramètres à la requête
    foreach ($match->getParameters() as $key => $value) {
        $request = $request->withAttribute($key, $value);
    }

    // Exécuter le handler
    $handler = $match->getRoute()->getHandler();
    $response = $handler($request);

    $this->assertEquals(200, $response->getStatusCode());
    $this->assertStringContainsString('"id":"123"', (string) $response->getBody());
}
```

## Performances

### Benchmarks

- **Route simple** : ~0.02ms
- **Route avec paramètre** : ~0.03ms
- **Route avec contrainte** : ~0.04ms
- **100 routes** : ~0.1ms (FastRoute = très rapide)

### Optimisations FastRoute

FastRoute compile les routes en structures optimisées:

1. **Groupement** : Routes similaires groupées
2. **Arbre de préfixes** : Partage des préfixes communs
3. **Cache** : Routes compilées mises en cache

```php
// FastRoute génère du code PHP optimisé
// Exemple interne (simplifié):
return [
    'GET' => [
        '/users' => [...],
        '/users/{id:\d+}' => [...],
    ],
    'POST' => [
        '/users' => [...],
    ],
];
```

## Intégration Application

### Bootstrap Complet

```php
use Elarion\Container\Container;
use Elarion\Routing\Router;
use Elarion\Http\Message\ServerRequest;

// Container
$container = new Container();

// Router
$router = new Router();

// Charger les routes
require __DIR__ . '/routes/web.php';
require __DIR__ . '/routes/api.php';

// Créer la requête depuis globals
$request = ServerRequestFactory::fromGlobals();

// Dispatcher
$match = $router->dispatch(
    $request->getMethod(),
    $request->getUri()->getPath()
);

// Handle
if ($match->isFound()) {
    $route = $match->getRoute();

    // Ajouter paramètres
    foreach ($match->getParameters() as $key => $value) {
        $request = $request->withAttribute($key, $value);
    }

    // Exécuter avec middlewares
    $executor = new RouteMiddlewareExecutor($container);
    $response = $executor->execute($route, $request, $match->getParameters());

} elseif ($match->isMethodNotAllowed()) {
    $response = Response::json([
        'error' => 'Method Not Allowed',
        'allowed' => $match->getAllowedMethods()
    ], 405);

} else {
    $response = Response::json(['error' => 'Not Found'], 404);
}

// Envoyer la réponse
http_response_code($response->getStatusCode());
foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header("$name: $value", false);
    }
}
echo $response->getBody();
```

## API Reference

### Router

```php
interface RouteCollectorInterface
{
    public function get(string $uri, callable|array $handler): RouteInterface;
    public function post(string $uri, callable|array $handler): RouteInterface;
    public function put(string $uri, callable|array $handler): RouteInterface;
    public function patch(string $uri, callable|array $handler): RouteInterface;
    public function delete(string $uri, callable|array $handler): RouteInterface;
    public function options(string $uri, callable|array $handler): RouteInterface;
    public function match(array $methods, string $uri, callable|array $handler): RouteInterface;
    public function any(string $uri, callable|array $handler): RouteInterface;
    public function group(array $attributes, callable $callback): void;
}

interface RouteDispatcherInterface
{
    public function dispatch(string $method, string $uri): RouteMatchInterface;
}
```

### Route

```php
interface RouteInterface
{
    public function getMethod(): string;
    public function getUri(): string;
    public function getHandler(): callable|array;
    public function getMiddleware(): array;
    public function getName(): ?string;
    public function getWhereConstraints(): array;

    public function name(string $name): self;
    public function middleware(string|callable|array $middleware): self;
    public function where(string|array $parameter, ?string $pattern = null): self;
}
```

## Dépannage

### Route Non Trouvée

```php
// 1. Vérifier que la route est définie
$routes = $router->getRoutes();
var_dump($routes);

// 2. Vérifier la méthode HTTP
$match = $router->dispatch('GET', '/users'); // GET
$match = $router->dispatch('POST', '/users'); // POST

// 3. Vérifier les contraintes
$router->get('/users/{id}', [...])
    ->where('id', '[0-9]+'); // Accepte uniquement chiffres
```

### Ordre des Routes

```php
// ❌ MAUVAIS ORDRE
$router->get('/users/{id}', [...]); // Plus spécifique EN PREMIER
$router->get('/users/create', [...]);  // Jamais atteint!

// ✅ BON ORDRE
$router->get('/users/create', [...]);  // Plus spécifique EN PREMIER
$router->get('/users/{id}', [...]);     // Plus générique APRÈS
```

## Roadmap

- [ ] Route caching pour production
- [ ] Resource routes helper (`$router->resource()`)
- [ ] API resource routes (`$router->apiResource()`)
- [ ] Route model binding
- [ ] Signed URLs avec expiration
- [ ] Rate limiting intégré

---

**Documentation générée le** : 2025-10-21
**Version** : 1.0.0
**Auteur** : ElarionStack Team
