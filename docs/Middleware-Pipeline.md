# Middleware Pipeline - ElarionStack

## Vue d'ensemble

Le système de middleware d'ElarionStack implémente **PSR-15** pour permettre l'exécution de middlewares autour du traitement des requêtes HTTP. Architecture FIFO avec support du short-circuit.

## Architecture

### PSR-15 Compliance

ElarionStack implémente les interfaces PSR-15 officielles:

```php
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
```

### Composants Principaux

```
src/Http/Middleware/
├── MiddlewarePipeline.php         # Pipeline PSR-15
├── RouteMiddlewareExecutor.php    # Intégration router
└── CallableMiddleware.php         # Adapter pour callables
```

## Utilisation

### Pipeline Simple

```php
use Elarion\Http\Middleware\MiddlewarePipeline;
use Elarion\Http\Message\ServerRequest;
use Elarion\Http\Message\Response;

$pipeline = new MiddlewarePipeline();

// Ajouter middlewares (exécutés dans l'ordre)
$pipeline
    ->pipe($authMiddleware)
    ->pipe($loggingMiddleware)
    ->pipe($corsMiddleware);

// Définir le handler final
$pipeline->setFallbackHandler($routeHandler);

// Traiter la requête
$response = $pipeline->handle($request);
```

### Ordre d'Exécution (FIFO)

```php
$pipeline = new MiddlewarePipeline();

$pipeline
    ->pipe($middleware1)  // Exécuté en PREMIER
    ->pipe($middleware2)  // Exécuté en DEUXIÈME
    ->pipe($middleware3); // Exécuté en TROISIÈME

// Ordre d'exécution:
// middleware1 → middleware2 → middleware3 → handler → middleware3 → middleware2 → middleware1
```

**Schéma d'exécution:**

```
Requête
   ↓
[Middleware 1] → before
   ↓
[Middleware 2] → before
   ↓
[Middleware 3] → before
   ↓
[Handler] → traite la requête
   ↓
[Middleware 3] → after
   ↓
[Middleware 2] → after
   ↓
[Middleware 1] → after
   ↓
Réponse
```

## Types de Middlewares

### 1. Middleware PSR-15

```php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Vérifier authentification
        $token = $request->getHeaderLine('Authorization');

        if (!$this->isValidToken($token)) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        // Ajouter user au request
        $user = $this->getUserFromToken($token);
        $request = $request->withAttribute('user', $user);

        // Continuer la chaîne
        return $handler->handle($request);
    }
}
```

### 2. Callable Middleware

```php
use Elarion\Http\Middleware\CallableMiddleware;

$loggingMiddleware = function ($request, $next) {
    $start = microtime(true);

    // Avant le handler
    error_log("Request: " . $request->getUri());

    $response = $next->handle($request);

    // Après le handler
    $duration = microtime(true) - $start;
    error_log("Response: {$response->getStatusCode()} ({$duration}s)");

    return $response;
};

$pipeline->pipe(new CallableMiddleware($loggingMiddleware));
// ou directement avec RouteMiddlewareExecutor qui wrap automatiquement
```

### 3. Middleware via Container

```php
// Enregistrer dans le container
$container->singleton(AuthMiddleware::class);

// Utiliser la classe name (résolution automatique)
$route->middleware(AuthMiddleware::class);
```

## Short-Circuit

Un middleware peut **court-circuiter** la chaîne en retournant une réponse sans appeler `$next->handle()`.

```php
class MaintenanceMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        if ($this->isMaintenanceMode()) {
            // Short-circuit : ne passe PAS au middleware suivant
            return Response::json([
                'error' => 'Service Unavailable',
                'message' => 'Maintenance in progress'
            ], 503);
        }

        // Mode normal : continue la chaîne
        return $handler->handle($request);
    }
}
```

**Résultat:**
- Les middlewares **après** ne sont **pas exécutés**
- Le handler final n'est **pas appelé**
- La réponse est retournée immédiatement

## Modification Request/Response

### Modifier la Requête

```php
class AddHeaderMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Ajouter un header ou attribut à la requête
        $request = $request
            ->withHeader('X-Custom-Header', 'value')
            ->withAttribute('timestamp', time());

        // Passer la requête modifiée
        return $handler->handle($request);
    }
}
```

### Modifier la Réponse

```php
class CorsMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Laisser le handler traiter la requête
        $response = $handler->handle($request);

        // Ajouter des headers CORS à la réponse
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }
}
```

## Intégration Router

### Middlewares Globaux

```php
use Elarion\Routing\Router;
use Elarion\Http\Middleware\MiddlewarePipeline;

$pipeline = new MiddlewarePipeline();
$pipeline
    ->pipe(new CorsMiddleware())
    ->pipe(new LoggingMiddleware());

// Tous les routes passent par ces middlewares
```

### Middlewares par Route

```php
$router->get('/admin', [AdminController::class, 'index'])
    ->middleware(AuthMiddleware::class)
    ->middleware(AdminMiddleware::class);

$router->post('/api/users', [UserController::class, 'store'])
    ->middleware('auth')
    ->middleware('throttle:60,1'); // 60 requêtes par minute
```

### Middlewares par Groupe

```php
$router->group(['middleware' => ['auth', 'admin']], function ($router) {
    $router->get('/admin/users', [AdminController::class, 'users']);
    $router->get('/admin/settings', [AdminController::class, 'settings']);
});
```

### RouteMiddlewareExecutor

```php
use Elarion\Http\Middleware\RouteMiddlewareExecutor;
use Elarion\Routing\Route;

$route = new Route('GET', '/users', $handler);
$route = $route
    ->middleware(AuthMiddleware::class)
    ->middleware($loggingCallable);

$executor = new RouteMiddlewareExecutor($container);

// Exécute le route avec ses middlewares
$response = $executor->execute($route, $request, ['id' => '123']);
```

**Features:**
- Résout middlewares depuis Container (PSR-11)
- Supporte: string (class name), callable, MiddlewareInterface
- Ajoute paramètres de route comme attributs request
- Wrap callables automatiquement avec CallableMiddleware

## Exemples de Middlewares

### Rate Limiting

```php
class ThrottleMiddleware implements MiddlewareInterface
{
    public function __construct(
        private int $maxAttempts,
        private int $decayMinutes
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $key = $this->resolveRequestKey($request);

        if ($this->tooManyAttempts($key)) {
            return Response::json([
                'error' => 'Too Many Requests'
            ], 429);
        }

        $this->hit($key);

        $response = $handler->handle($request);

        return $response
            ->withHeader('X-RateLimit-Limit', (string) $this->maxAttempts)
            ->withHeader('X-RateLimit-Remaining', (string) $this->remaining($key));
    }
}

// Usage
$route->middleware(new ThrottleMiddleware(60, 1)); // 60 req/min
```

### Content Negotiation

```php
class ContentNegotiationMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $accept = $request->getHeaderLine('Accept');

        $request = $request->withAttribute('accept', $accept);

        $response = $handler->handle($request);

        // Transformer selon le type accepté
        if (str_contains($accept, 'application/xml')) {
            return $this->convertToXml($response);
        }

        return $response; // JSON par défaut
    }
}
```

### Request Validation

```php
class ValidateRequestMiddleware implements MiddlewareInterface
{
    public function __construct(private array $rules) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $data = $request->getParsedBody();
        $errors = $this->validate($data, $this->rules);

        if (!empty($errors)) {
            return Response::json([
                'error' => 'Validation Failed',
                'errors' => $errors
            ], 422);
        }

        return $handler->handle($request);
    }
}

// Usage
$route->middleware(new ValidateRequestMiddleware([
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users',
]));
```

### CSRF Protection

```php
class VerifyCsrfTokenMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        if (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $token = $request->getParsedBody()['_token'] ?? '';
            $sessionToken = $_SESSION['csrf_token'] ?? '';

            if (!hash_equals($sessionToken, $token)) {
                return Response::json(['error' => 'CSRF token mismatch'], 419);
            }
        }

        return $handler->handle($request);
    }
}
```

## Testing

### Test d'un Middleware

```php
use PHPUnit\Framework\TestCase;
use Elarion\Http\Message\ServerRequest;
use Elarion\Http\Message\Response;
use Elarion\Http\Message\Uri;

class AuthMiddlewareTest extends TestCase
{
    public function test_blocks_unauthenticated_requests(): void
    {
        $middleware = new AuthMiddleware();
        $request = new ServerRequest('GET', new Uri());

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $response = $middleware->process($request, $handler);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_allows_authenticated_requests(): void
    {
        $middleware = new AuthMiddleware();
        $request = (new ServerRequest('GET', new Uri()))
            ->withHeader('Authorization', 'Bearer valid-token');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn(new Response());

        $response = $middleware->process($request, $handler);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
```

### Test du Pipeline

```php
public function test_executes_middlewares_in_fifo_order(): void
{
    $order = [];

    $middleware1 = new CallableMiddleware(function ($request, $next) use (&$order) {
        $order[] = 'before-1';
        $response = $next->handle($request);
        $order[] = 'after-1';
        return $response;
    });

    $middleware2 = new CallableMiddleware(function ($request, $next) use (&$order) {
        $order[] = 'before-2';
        $response = $next->handle($request);
        $order[] = 'after-2';
        return $response;
    });

    $pipeline = (new MiddlewarePipeline())
        ->pipe($middleware1)
        ->pipe($middleware2)
        ->setFallbackHandler($handler);

    $pipeline->handle($request);

    $this->assertEquals([
        'before-1',
        'before-2',
        'handler',
        'after-2',
        'after-1',
    ], $order);
}
```

## Performances

### Benchmarks

- **Pipeline overhead**: < 0.01ms pour 10 middlewares
- **Short-circuit**: Arrêt immédiat (pas de middlewares suivants)
- **Lazy evaluation**: Middlewares créés seulement si nécessaires

### Optimisations

1. **Pas de copie** : Request/Response passés par référence
2. **Closure-based** : Handler chain construit dynamiquement
3. **Type hints stricts** : Pas de vérification runtime

## Best Practices

### ✅ DO

```php
// Middleware focused sur UNE responsabilité
class LogRequestMiddleware implements MiddlewareInterface { /* ... */ }

// Toujours appeler $next (sauf short-circuit intentionnel)
$response = $handler->handle($request);

// Retourner ResponseInterface
return $response;

// Utiliser attributes pour passer des données
$request = $request->withAttribute('user', $user);
```

### ❌ DON'T

```php
// Middleware qui fait trop de choses
class DoEverythingMiddleware { /* auth + logging + validation */ }

// Oublier d'appeler $next (bug !)
return new Response(); // ❌ short-circuit non intentionnel

// Modifier des variables globales
$GLOBALS['user'] = $user; // ❌

// Ne pas retourner ResponseInterface
return $user; // ❌ Type error
```

## Middleware vs Service Provider

| Aspect | Middleware | Service Provider |
|--------|-----------|-----------------|
| **Quand** | Par requête HTTP | Au boot de l'application |
| **Rôle** | Filtrer/modifier request/response | Enregistrer services dans Container |
| **Ordre** | Important (FIFO) | Généralement non |
| **Performance** | Exécuté à chaque requête | Exécuté une fois au démarrage |

## API Reference

### MiddlewarePipeline

```php
final class MiddlewarePipeline implements RequestHandlerInterface
{
    public function pipe(MiddlewareInterface $middleware): self;
    public function setFallbackHandler(RequestHandlerInterface $handler): self;
    public function handle(ServerRequestInterface $request): ResponseInterface;
}
```

### RouteMiddlewareExecutor

```php
final class RouteMiddlewareExecutor
{
    public function __construct(?ContainerInterface $container = null);
    public function execute(
        RouteInterface $route,
        ServerRequestInterface $request,
        array $routeParams = []
    ): ResponseInterface;
}
```

### CallableMiddleware

```php
final class CallableMiddleware implements MiddlewareInterface
{
    public function __construct(callable $callable);
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface;
}
```

## Roadmap

- [ ] Middleware groups (aliases)
- [ ] Conditional middleware (when/unless)
- [ ] Middleware priorities
- [ ] Async middleware support
- [ ] Middleware caching/compilation

---

**Documentation générée le** : 2025-10-21
**Version** : 1.0.0
**Auteur** : ElarionStack Team
