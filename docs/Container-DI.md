# Container & Dependency Injection - ElarionStack

## Vue d'ensemble

Le Container d'ElarionStack implémente **PSR-11** avec auto-wiring automatique, résolution contextuelle, et support complet des service providers. Architecture inspirée de Laravel avec optimisations PHP 8.5.

## Architecture

### PSR-11 Compliance

```php
use Psr\Container\ContainerInterface;

interface ContainerInterface
{
    public function get(string $id): mixed;
    public function has(string $id): bool;
}
```

### Principes SOLID Appliqués

- **SRP** : Container gère uniquement la résolution de dépendances
- **OCP** : Extensible via bindings sans modifier le core
- **LSP** : Implémente PSR-11 strictement
- **ISP** : Interface minimale et focalisée
- **DIP** : Injection de dépendances plutôt que création directe

### Composants Principaux

```
src/Container/
├── Container.php              # Container PSR-11 principal
├── Exceptions/
│   ├── ContainerException.php
│   └── NotFoundException.php
└── Contracts/
    └── BindingResolutionException.php
```

## Utilisation Basique

### Création et Résolution

```php
use Elarion\Container\Container;

$container = new Container();

// Auto-wiring automatique
class UserRepository
{
    public function __construct(
        private PDO $pdo,
        private CacheInterface $cache
    ) {}
}

// Résolution automatique avec injection
$repository = $container->make(UserRepository::class);
// Le container injecte automatiquement PDO et CacheInterface
```

### Bindings Simples

```php
// Bind une interface à une implémentation
$container->bind(CacheInterface::class, RedisCache::class);

// Bind avec une closure
$container->bind(PDO::class, function ($container) {
    return new PDO('mysql:host=localhost;dbname=app', 'root', 'secret');
});

// Résolution
$cache = $container->make(CacheInterface::class);
// Retourne une nouvelle instance de RedisCache
```

### Singletons

```php
// Singleton - même instance à chaque résolution
$container->singleton(PDO::class, function ($container) {
    return new PDO('mysql:host=localhost;dbname=app', 'root', 'secret');
});

$pdo1 = $container->make(PDO::class);
$pdo2 = $container->make(PDO::class);

var_dump($pdo1 === $pdo2); // true
```

### Instances Directes

```php
// Enregistrer une instance existante
$logger = new Logger();
$container->instance(LoggerInterface::class, $logger);

// Toujours la même instance
$log = $container->make(LoggerInterface::class);
var_dump($log === $logger); // true
```

## Auto-Wiring

Le container résout automatiquement les dépendances via reflection.

### Type Hinting

```php
class UserController
{
    public function __construct(
        private UserRepository $repository,
        private LoggerInterface $logger,
        private EventDispatcher $dispatcher
    ) {}
}

// Auto-wiring automatique
$controller = $container->make(UserController::class);
// Container injecte: UserRepository, LoggerInterface, EventDispatcher
```

### Dépendances Imbriquées

```php
class UserRepository
{
    public function __construct(private PDO $pdo) {}
}

class UserService
{
    public function __construct(
        private UserRepository $repository  // Dépend de UserRepository
    ) {}
}

class UserController
{
    public function __construct(
        private UserService $service  // Dépend de UserService
    ) {}
}

// Le container résout toute la chaîne
$controller = $container->make(UserController::class);
// Résout: PDO → UserRepository → UserService → UserController
```

### Paramètres Primitifs

```php
class ApiClient
{
    public function __construct(
        private HttpClient $client,
        private string $apiKey,      // ❌ Primitif : ne peut pas auto-wire
        private int $timeout = 30    // ✅ Défaut fourni : OK
    ) {}
}

// ❌ Échec : $apiKey n'a pas de valeur par défaut
$client = $container->make(ApiClient::class);
// BindingResolutionException: Cannot resolve parameter $apiKey

// ✅ Solution : utiliser makeWith()
$client = $container->makeWith(ApiClient::class, [
    'apiKey' => env('API_KEY'),
]);
```

## Résolution Contextuelle

### makeWith() - Paramètres Explicites

```php
class EmailService
{
    public function __construct(
        private Mailer $mailer,
        private string $from,
        private string $subject
    ) {}
}

// Fournir les paramètres primitifs
$service = $container->makeWith(EmailService::class, [
    'from' => 'noreply@example.com',
    'subject' => 'Welcome!',
]);
// Mailer est auto-wiré, from et subject sont fournis
```

### Closure avec Contexte

```php
$container->bind(ApiClient::class, function ($container) {
    return new ApiClient(
        $container->make(HttpClient::class),
        env('API_KEY'),
        30
    );
});
```

## Binding Patterns

### Interface → Implémentation

```php
// Abstraction
interface CacheInterface
{
    public function get(string $key): mixed;
    public function set(string $key, mixed $value): void;
}

// Implémentations
class RedisCache implements CacheInterface { /* ... */ }
class FileCache implements CacheInterface { /* ... */ }

// Bind selon l'environnement
if (env('CACHE_DRIVER') === 'redis') {
    $container->singleton(CacheInterface::class, RedisCache::class);
} else {
    $container->singleton(CacheInterface::class, FileCache::class);
}

// Utilisation
class UserRepository
{
    // Dépend de l'abstraction, pas de l'implémentation
    public function __construct(private CacheInterface $cache) {}
}
```

### Factory Pattern

```php
$container->bind(DatabaseConnection::class, function ($container) {
    $config = $container->make(ConfigRepository::class);

    return new DatabaseConnection(
        host: $config->get('database.host'),
        database: $config->get('database.name'),
        username: $config->get('database.username'),
        password: $config->get('database.password')
    );
});
```

### Conditional Binding

```php
// Bind selon l'environnement
if (app()->environment('production')) {
    $container->singleton(CacheInterface::class, RedisCache::class);
} else {
    $container->singleton(CacheInterface::class, ArrayCache::class);
}

// Bind selon la configuration
if (config('logging.driver') === 'syslog') {
    $container->singleton(LoggerInterface::class, SyslogLogger::class);
} else {
    $container->singleton(LoggerInterface::class, FileLogger::class);
}
```

## Fonction Helper

```php
// Obtenir l'instance du container
$container = app();

// Résoudre depuis le container
$repository = app(UserRepository::class);

// Avec paramètres
$service = app()->makeWith(EmailService::class, [
    'from' => 'admin@example.com',
]);

// Vérifier binding
if (app()->has(CacheInterface::class)) {
    $cache = app(CacheInterface::class);
}
```

## Service Providers

Les Service Providers organisent les bindings du container.

### Créer un Provider

```php
use Elarion\Providers\ServiceProvider;

class CacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CacheInterface::class, function ($app) {
            $driver = config('cache.driver', 'file');

            return match($driver) {
                'redis' => new RedisCache(
                    host: config('cache.redis.host'),
                    port: config('cache.redis.port')
                ),
                'memcached' => new MemcachedCache(
                    servers: config('cache.memcached.servers')
                ),
                default => new FileCache(
                    path: storage_path('cache')
                ),
            };
        });
    }

    public function boot(): void
    {
        // Configuration après tous les bindings
    }
}
```

### Enregistrer un Provider

```php
use Elarion\Container\Container;

$container = new Container();

// Enregistrer le provider
$provider = new CacheServiceProvider($container);
$provider->register();
$provider->boot();

// Ou via Application (si disponible)
$app->register(CacheServiceProvider::class);
```

## Patterns Avancés

### Repository Pattern

```php
// Interfaces
interface UserRepositoryInterface
{
    public function find(int $id): ?User;
    public function all(): array;
}

// Implémentation
class EloquentUserRepository implements UserRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function find(int $id): ?User { /* ... */ }
    public function all(): array { /* ... */ }
}

// Binding
$container->bind(UserRepositoryInterface::class, EloquentUserRepository::class);

// Utilisation dans un controller
class UserController
{
    public function __construct(
        private UserRepositoryInterface $users  // Interface, pas classe concrète
    ) {}

    public function show(int $id)
    {
        $user = $this->users->find($id);
        return Response::json($user);
    }
}
```

### Strategy Pattern

```php
interface PaymentGateway
{
    public function charge(int $amount): bool;
}

class StripeGateway implements PaymentGateway { /* ... */ }
class PayPalGateway implements PaymentGateway { /* ... */ }

// Factory pour sélectionner la stratégie
$container->bind(PaymentGateway::class, function ($container) {
    $gateway = config('payment.default');

    return match($gateway) {
        'stripe' => new StripeGateway(config('payment.stripe.key')),
        'paypal' => new PayPalGateway(config('payment.paypal.client_id')),
        default => throw new \RuntimeException("Unknown gateway: $gateway"),
    };
});
```

### Observer Pattern

```php
interface EventDispatcherInterface
{
    public function dispatch(string $event, array $payload = []): void;
}

class EventDispatcher implements EventDispatcherInterface
{
    private array $listeners = [];

    public function listen(string $event, callable $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    public function dispatch(string $event, array $payload = []): void
    {
        foreach ($this->listeners[$event] ?? [] as $listener) {
            $listener(...$payload);
        }
    }
}

// Singleton pour partager les listeners
$container->singleton(EventDispatcherInterface::class, EventDispatcher::class);
```

## Testing

### Test Doubles

```php
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    public function test_creates_user(): void
    {
        $container = new Container();

        // Mock repository
        $mockRepo = $this->createMock(UserRepositoryInterface::class);
        $mockRepo->expects($this->once())
            ->method('create')
            ->with(['name' => 'John'])
            ->willReturn(new User(1, 'John'));

        // Bind le mock
        $container->instance(UserRepositoryInterface::class, $mockRepo);

        // Test le service avec le mock
        $service = $container->make(UserService::class);
        $user = $service->createUser(['name' => 'John']);

        $this->assertEquals('John', $user->name);
    }
}
```

### Container Isolation

```php
public function test_container_isolation(): void
{
    // Nouveau container pour chaque test
    $container = new Container();

    // Bindings de test
    $container->singleton(CacheInterface::class, ArrayCache::class);
    $container->instance(LoggerInterface::class, new NullLogger());

    // Test avec dépendances mocké
    $service = $container->make(UserService::class);
    // ...
}
```

## Performances

### Benchmarks

- **Résolution simple** : ~0.01ms
- **Auto-wiring complexe** (5 dépendances) : ~0.05ms
- **Singleton** (2ème accès) : ~0.001ms (cache hit)
- **Reflection overhead** : ~0.02ms par classe

### Optimisations

1. **Singletons** : Réutilise la même instance
2. **Cache de reflection** : Méta-données mises en cache
3. **Résolution précoce** : Bindings résolus au boot si possible

### Cache de Résolution

```php
// Container met en cache les singletons automatiquement
$container->singleton(PDO::class, function () {
    return new PDO(/* ... */);
});

// 1er appel : résolution + cache
$pdo1 = $container->make(PDO::class); // ~0.05ms

// Appels suivants : depuis cache
$pdo2 = $container->make(PDO::class); // ~0.001ms
```

## Gestion des Erreurs

### NotFoundException

```php
use Psr\Container\NotFoundExceptionInterface;

try {
    $service = $container->make(NonExistentClass::class);
} catch (NotFoundExceptionInterface $e) {
    // Classe ou binding introuvable
    echo "Service not found: " . $e->getMessage();
}
```

### BindingResolutionException

```php
use Elarion\Container\Exceptions\BindingResolutionException;

class ApiClient
{
    public function __construct(private string $apiKey) {}
}

try {
    // ❌ $apiKey ne peut pas être résolu
    $client = $container->make(ApiClient::class);
} catch (BindingResolutionException $e) {
    echo "Cannot resolve: " . $e->getMessage();
    // "Cannot resolve parameter $apiKey of type string for ApiClient"
}
```

### Circular Dependencies

```php
class A
{
    public function __construct(B $b) {}
}

class B
{
    public function __construct(A $a) {}  // ❌ Circular!
}

try {
    $container->make(A::class);
} catch (BindingResolutionException $e) {
    echo "Circular dependency detected";
}
```

**Solution:** Injecter via setter ou utiliser une interface

```php
class A
{
    private ?B $b = null;

    public function setB(B $b): void
    {
        $this->b = $b;
    }
}
```

## Best Practices

### ✅ DO

```php
// Dépendre d'abstractions, pas d'implémentations
public function __construct(CacheInterface $cache) {}

// Utiliser constructor injection
public function __construct(private UserRepository $users) {}

// Bindings dans Service Providers
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CacheInterface::class, RedisCache::class);
    }
}

// Singleton pour services stateful
$container->singleton(DatabaseConnection::class);
```

### ❌ DON'T

```php
// Ne pas dépendre de classes concrètes quand une abstraction existe
public function __construct(RedisCache $cache) {} // ❌

// Ne pas utiliser le container comme Service Locator
public function index()
{
    $users = app(UserRepository::class)->all(); // ❌
    // Injecter dans le constructor à la place
}

// Ne pas faire de new dans le code métier
$service = new UserService(new UserRepository(new PDO(...))); // ❌
// Laisser le container gérer

// Ne pas créer des bindings cycliques
$container->bind(A::class, fn() => new A($container->make(B::class)));
$container->bind(B::class, fn() => new B($container->make(A::class))); // ❌
```

## Migration depuis Autre Framework

### Depuis Laravel

```php
// Laravel → ElarionStack (très similaire)
app(UserRepository::class)           // Identique
app()->make(UserRepository::class)   // Identique
app()->singleton(...)                // Identique
app()->bind(...)                     // Identique

// Différence: pas de app()->when()->needs()->give()
// Utiliser makeWith() à la place
```

### Depuis Symfony

```php
// Symfony
$container->get(UserRepository::class)

// ElarionStack
app(UserRepository::class)
```

## API Reference

### Container

```php
interface ContainerInterface
{
    // PSR-11
    public function get(string $id): mixed;
    public function has(string $id): bool;

    // Elarion Extensions
    public function bind(string $abstract, Closure|string|null $concrete = null): void;
    public function singleton(string $abstract, Closure|string|null $concrete = null): void;
    public function instance(string $abstract, object $instance): void;
    public function make(string $abstract): mixed;
    public function makeWith(string $abstract, array $parameters): mixed;
}
```

## Dépannage

### Service Not Found

```php
// Vérifier le binding
if ($container->has(CacheInterface::class)) {
    echo "Binding exists";
} else {
    echo "No binding for CacheInterface";
}

// Lister tous les bindings (debug)
// Note: Container n'expose pas cette méthode par défaut
// Ajouter un DebugServiceProvider si nécessaire
```

### Auto-Wiring Fails

```php
// Problème: Paramètre primitif sans défaut
class Service
{
    public function __construct(private string $apiKey) {} // ❌
}

// Solution 1: Valeur par défaut
class Service
{
    public function __construct(private string $apiKey = '') {} // ✅
}

// Solution 2: makeWith()
$service = $container->makeWith(Service::class, [
    'apiKey' => env('API_KEY'),
]);

// Solution 3: Factory binding
$container->bind(Service::class, function ($container) {
    return new Service(env('API_KEY'));
});
```

## Roadmap

- [ ] Contextual bindings avancés (`when()->needs()->give()`)
- [ ] Tagged services (`tag()`, `tagged()`)
- [ ] Method injection en plus de constructor
- [ ] Container compilation pour production
- [ ] Lazy proxies pour singletons coûteux

---

**Documentation générée le** : 2025-10-21
**Version** : 1.0.0
**Auteur** : ElarionStack Team
