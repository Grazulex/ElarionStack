# Service Providers - ElarionStack

## Vue d'ensemble

Les Service Providers sont le point d'entrée pour configurer et enregistrer les services dans le Container. Pattern inspiré de Laravel pour organiser le bootstrapping de l'application.

## Architecture

### Principes SOLID Appliqués

- **SRP** : Chaque provider gère l'enregistrement d'un domaine spécifique
- **OCP** : Nouveaux providers sans modifier le core
- **LSP** : Tous les providers étendent ServiceProvider
- **ISP** : Interface minimale (register, boot)
- **DIP** : Providers dépendent du Container, pas l'inverse

### Composants Principaux

```
src/Providers/
└── ServiceProvider.php    # Classe abstraite de base
```

## Utilisation Basique

### Créer un Provider

```php
use Elarion\Providers\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Register bindings dans le container
     */
    public function register(): void
    {
        $this->app->singleton(ConnectionManager::class, function ($app) {
            $config = $app->make('config')->get('database');

            return new ConnectionManager($config['connections']);
        });
    }

    /**
     * Bootstrap après tous les bindings
     */
    public function boot(): void
    {
        // Migrations, seeds, etc.
    }
}
```

### Enregistrer un Provider

```php
use Elarion\Container\Container;

$app = new Container();

// Créer et enregistrer
$provider = new DatabaseServiceProvider($app);
$provider->register();
$provider->boot();
```

## Cycle de Vie

### 1. Register Phase

Tous les providers s'enregistrent **avant** le boot.

```php
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bindings uniquement
        $this->app->singleton(UserRepository::class);
        $this->app->bind(CacheInterface::class, RedisCache::class);

        // ❌ NE PAS résoudre de services ici
        // $cache = $this->app->make(CacheInterface::class); // Peut échouer!
    }
}
```

**Règles:**
- Enregistrer des bindings uniquement
- Ne pas résoudre de services
- Pas d'accès à d'autres services

### 2. Boot Phase

Après tous les `register()`, les `boot()` s'exécutent.

```php
class EventServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EventDispatcher::class);
    }

    public function boot(): void
    {
        // ✅ Résolution OK ici
        $dispatcher = $this->app->make(EventDispatcher::class);

        // Enregistrer les listeners
        $dispatcher->listen('user.created', SendWelcomeEmail::class);
        $dispatcher->listen('user.created', CreateProfile::class);
    }
}
```

**Règles:**
- Résoudre des services du container
- Configuration après bindings
- Enregistrer events, routes, views, etc.

### 3. Ordre d'Exécution

```php
// 1. REGISTER de tous les providers
$configProvider->register();
$databaseProvider->register();
$routingProvider->register();

// 2. BOOT de tous les providers
$configProvider->boot();
$databaseProvider->boot();
$routingProvider->boot();
```

## Patterns Communs

### Database Provider

```php
class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Connection Manager
        $this->app->singleton(ConnectionManager::class, function ($app) {
            $connections = $app->make('config')->get('database.connections');

            return new ConnectionManager($connections);
        });

        // Default PDO connection
        $this->app->singleton(PDO::class, function ($app) {
            $manager = $app->make(ConnectionManager::class);

            return $manager->connection();
        });
    }

    public function boot(): void
    {
        // Optionnel: Run migrations si en développement
        if ($this->app->make('config')->get('app.env') === 'development') {
            // $this->runMigrations();
        }
    }
}
```

### Cache Provider

```php
class CacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CacheInterface::class, function ($app) {
            $driver = $app->make('config')->get('cache.driver', 'file');

            return match($driver) {
                'redis' => new RedisCache(
                    host: config('cache.redis.host'),
                    port: config('cache.redis.port')
                ),
                'memcached' => new MemcachedCache(
                    servers: config('cache.memcached.servers')
                ),
                'array' => new ArrayCache(),
                default => new FileCache(
                    path: storage_path('cache')
                ),
            };
        });
    }
}
```

### Logging Provider

```php
class LoggingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LoggerInterface::class, function ($app) {
            $channel = $app->make('config')->get('logging.default', 'stack');
            $channels = $app->make('config')->get('logging.channels', []);

            return $this->createLogger($channel, $channels);
        });
    }

    private function createLogger(string $channel, array $channels): LoggerInterface
    {
        $config = $channels[$channel] ?? [];

        return match($config['driver'] ?? 'file') {
            'syslog' => new SyslogLogger(),
            'errorlog' => new ErrorLogLogger(),
            'stack' => new StackLogger(
                $this->createChannels($config['channels'] ?? [])
            ),
            default => new FileLogger(
                path: storage_path('logs/app.log')
            ),
        };
    }
}
```

### Routing Provider

```php
class RouteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Router::class);
    }

    public function boot(): void
    {
        $router = $this->app->make(Router::class);

        // Charger les routes
        $this->loadRoutes($router);
    }

    protected function loadRoutes(Router $router): void
    {
        // Web routes
        require base_path('routes/web.php');

        // API routes avec préfixe
        $router->group(['prefix' => '/api'], function ($router) {
            require base_path('routes/api.php');
        });
    }
}
```

### View Provider

```php
class ViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ViewFactory::class, function ($app) {
            return new ViewFactory(
                paths: [
                    resource_path('views'),
                ],
                cachePath: storage_path('views')
            );
        });
    }

    public function boot(): void
    {
        $factory = $this->app->make(ViewFactory::class);

        // Share variables globales
        $factory->share('app', $this->app);
        $factory->share('config', $this->app->make('config'));

        // Composers
        $factory->composer('layouts.app', function ($view) {
            $view->with('user', auth()->user());
        });
    }
}
```

## Deferred Providers

### Lazy Loading

Pour optimiser le boot, certains providers peuvent être **deferred** (chargés uniquement quand nécessaire).

```php
class MailServiceProvider extends ServiceProvider
{
    /**
     * Indiquer que ce provider est deferred
     */
    public bool $defer = true;

    /**
     * Services fournis par ce provider
     */
    public function provides(): array
    {
        return [
            MailerInterface::class,
            'mailer',
        ];
    }

    public function register(): void
    {
        $this->app->singleton(MailerInterface::class, function ($app) {
            return new SmtpMailer(
                host: config('mail.host'),
                port: config('mail.port'),
                username: config('mail.username'),
                password: config('mail.password'),
            );
        });

        $this->app->alias(MailerInterface::class, 'mailer');
    }
}
```

**Avantages:**
- Réduit le temps de boot
- Services lourds chargés uniquement si utilisés
- Meilleure performance

**Inconvénients:**
- Complexité accrue
- Difficile à debugger

## Testing

### Test d'un Provider

```php
use PHPUnit\Framework\TestCase;
use Elarion\Container\Container;

class DatabaseServiceProviderTest extends TestCase
{
    public function test_registers_connection_manager(): void
    {
        $app = new Container();

        // Mock config
        $app->instance('config', $this->createConfigMock());

        // Enregistrer le provider
        $provider = new DatabaseServiceProvider($app);
        $provider->register();

        // Vérifier le binding
        $this->assertTrue($app->has(ConnectionManager::class));

        // Résoudre
        $manager = $app->make(ConnectionManager::class);
        $this->assertInstanceOf(ConnectionManager::class, $manager);
    }

    public function test_boot_runs_without_errors(): void
    {
        $app = new Container();
        $app->instance('config', $this->createConfigMock());

        $provider = new DatabaseServiceProvider($app);
        $provider->register();

        // Boot ne doit pas lever d'exception
        $provider->boot();

        $this->assertTrue(true);
    }

    private function createConfigMock()
    {
        $mock = $this->createMock(ConfigRepository::class);
        $mock->method('get')
            ->willReturn([
                'connections' => [
                    'default' => [
                        'driver' => 'sqlite',
                        'database' => ':memory:',
                    ],
                ],
            ]);

        return $mock;
    }
}
```

## Patterns Avancés

### Conditional Registration

```php
class DebugServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->make('config')->get('app.debug')) {
            $this->app->singleton(DebugBar::class);
            $this->app->singleton(Profiler::class);
        }
    }

    public function boot(): void
    {
        if ($this->app->make('config')->get('app.debug')) {
            $debugBar = $this->app->make(DebugBar::class);
            $debugBar->boot();
        }
    }
}
```

### Environment-Specific

```php
class CacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $env = $this->app->make('config')->get('app.env');

        if ($env === 'production') {
            // Redis en production
            $this->app->singleton(CacheInterface::class, RedisCache::class);
        } elseif ($env === 'testing') {
            // Array cache en test (isolation)
            $this->app->singleton(CacheInterface::class, ArrayCache::class);
        } else {
            // File cache en développement
            $this->app->singleton(CacheInterface::class, FileCache::class);
        }
    }
}
```

### Extending Existing Services

```php
class CustomValidationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $validator = $this->app->make(Validator::class);

        // Ajouter une règle custom
        $validator->extend('phone', function ($attribute, $value) {
            return preg_match('/^[0-9]{10}$/', $value);
        });

        // Ajouter un message custom
        $validator->message('phone', 'The :attribute must be a valid phone number.');
    }
}
```

### Package Providers

```php
class PackageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge config du package
        $this->mergeConfigFrom(
            __DIR__ . '/../config/package.php',
            'package'
        );

        // Bindings du package
        $this->app->singleton(PackageService::class);
    }

    public function boot(): void
    {
        // Publier config
        $this->publishes([
            __DIR__ . '/../config/package.php' => config_path('package.php'),
        ], 'config');

        // Publier views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'package');

        // Publier migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }

    protected function mergeConfigFrom(string $path, string $key): void
    {
        $config = $this->app->make('config');
        $config->set($key, array_merge(
            require $path,
            $config->get($key, [])
        ));
    }
}
```

## Best Practices

### ✅ DO

```php
// Séparer par domaine
class DatabaseServiceProvider extends ServiceProvider {}
class CacheServiceProvider extends ServiceProvider {}
class RouteServiceProvider extends ServiceProvider {}

// Register = bindings seulement
public function register(): void
{
    $this->app->singleton(Service::class);
}

// Boot = configuration après bindings
public function boot(): void
{
    $service = $this->app->make(Service::class);
    $service->configure();
}

// Utiliser match() pour factories propres
$this->app->singleton(Cache::class, fn($app) =>
    match(config('cache.driver')) {
        'redis' => new RedisCache(),
        'file' => new FileCache(),
        default => new ArrayCache(),
    }
);
```

### ❌ DON'T

```php
// Tout dans un seul provider
class GodServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ❌ Trop de responsabilités
        $this->registerDatabase();
        $this->registerCache();
        $this->registerMail();
        $this->registerQueue();
        // ...
    }
}

// Résoudre des services dans register()
public function register(): void
{
    // ❌ $cache peut ne pas être disponible encore
    $cache = $this->app->make(CacheInterface::class);
}

// Logique métier dans les providers
public function boot(): void
{
    // ❌ Providers = configuration, pas logique métier
    $users = User::all();
    foreach ($users as $user) {
        $user->sendEmail();
    }
}
```

## Organisation

### Structure Recommandée

```
app/Providers/
├── AppServiceProvider.php          # Bindings généraux de l'app
├── RouteServiceProvider.php        # Routes
├── EventServiceProvider.php        # Events
├── AuthServiceProvider.php         # Authentification
└── ViewServiceProvider.php         # Views, composers

src/Database/
└── DatabaseServiceProvider.php     # Database (dans son module)

src/Cache/
└── CacheServiceProvider.php        # Cache (dans son module)
```

### Bootstrap

```php
// bootstrap/app.php

use Elarion\Container\Container;

$app = new Container();

// Providers
$providers = [
    \App\Providers\AppServiceProvider::class,
    \Elarion\Database\DatabaseServiceProvider::class,
    \Elarion\Cache\CacheServiceProvider::class,
    \App\Providers\RouteServiceProvider::class,
];

// Register phase
foreach ($providers as $providerClass) {
    $provider = new $providerClass($app);
    $provider->register();
}

// Boot phase
foreach ($providers as $providerClass) {
    $provider = new $providerClass($app);
    $provider->boot();
}

return $app;
```

## API Reference

### ServiceProvider

```php
abstract class ServiceProvider
{
    /**
     * @param Container $app Container instance
     */
    public function __construct(protected Container $app) {}

    /**
     * Register bindings in the container
     */
    abstract public function register(): void;

    /**
     * Bootstrap after all providers registered
     */
    public function boot(): void {}
}
```

## Debugging

### Liste des Bindings

```php
// Dans un provider de debug
class DebugServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (config('app.debug')) {
            $this->dumpBindings();
        }
    }

    private function dumpBindings(): void
    {
        // Note: Container ne fournit pas cette méthode par défaut
        // À implémenter si nécessaire
        foreach ($this->app->getBindings() as $abstract => $concrete) {
            dump("$abstract => $concrete");
        }
    }
}
```

### Provider Order

```php
// Tracker l'ordre d'exécution
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        logger('AppServiceProvider::register');
    }

    public function boot(): void
    {
        logger('AppServiceProvider::boot');
    }
}
```

## Roadmap

- [ ] Auto-discovery des providers
- [ ] Lazy provider loading automatique
- [ ] Provider caching pour production
- [ ] Package provider helpers (publishes, etc.)
- [ ] Provider dependencies management

---

**Documentation générée le** : 2025-10-21
**Version** : 1.0.0
**Auteur** : ElarionStack Team
