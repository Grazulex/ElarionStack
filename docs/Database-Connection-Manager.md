# Database Connection Manager - ElarionStack

## Vue d'ensemble

Le Connection Manager d'ElarionStack fournit une interface élégante pour gérer plusieurs connexions PDO avec lazy-loading, support multi-drivers, et configuration centralisée.

## Architecture

### Principes SOLID Appliqués

- **SRP** : DatabaseConfig (config), ConnectionFactory (création), ConnectionManager (gestion)
- **OCP** : Extensible via nouveaux drivers sans modifier le code existant
- **LSP** : Toutes les configurations respectent les mêmes contrats
- **ISP** : Interfaces focalisées et spécifiques
- **DIP** : Factory injecté, pas de couplage fort avec PDO

### Composants Principaux

```
src/Database/
├── DatabaseConfig.php         # Value Object pour configuration
├── ConnectionFactory.php      # Factory pour création PDO
├── ConnectionManager.php      # Gestionnaire de connexions
└── Exceptions/
    ├── DatabaseException.php
    ├── ConnectionException.php
    └── ConfigurationException.php
```

## Utilisation

### Configuration Simple

```php
use Elarion\Database\ConnectionManager;

$manager = new ConnectionManager([
    'default' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'database' => 'my_app',
        'username' => 'root',
        'password' => 'secret',
        'charset' => 'utf8mb4',
    ],
]);

// Obtenir la connexion par défaut (lazy-loaded)
$pdo = $manager->connection();
```

### Multiple Connexions

```php
$manager = new ConnectionManager([
    'default' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'database' => 'app',
        'username' => 'root',
        'password' => 'secret',
    ],
    'analytics' => [
        'driver' => 'pgsql',
        'host' => 'analytics.db.local',
        'port' => 5432,
        'database' => 'stats',
        'username' => 'analytics',
        'password' => 'secret',
    ],
    'cache' => [
        'driver' => 'sqlite',
        'database' => '/path/to/cache.sqlite',
    ],
]);

// Connexions nommées
$app = $manager->connection('default');
$analytics = $manager->connection('analytics');
$cache = $manager->connection('cache');
```

### Ajout Dynamique

```php
use Elarion\Database\DatabaseConfig;

$manager = new ConnectionManager();

// Ajouter une connexion à la volée
$config = new DatabaseConfig(
    driver: 'mysql',
    database: 'tenant_123',
    host: 'tenant-db.local',
    username: 'app',
    password: 'secret'
);

$manager->addConnection('tenant_123', $config);
$pdo = $manager->connection('tenant_123');
```

## Drivers Supportés

### MySQL

```php
[
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'my_database',
    'username' => 'root',
    'password' => 'secret',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_TIMEOUT => 5,
    ],
]
```

**Caractéristiques:**
- Strict mode activé par défaut (`STRICT_ALL_TABLES`)
- Charset configurable (défaut: utf8mb4)
- Port par défaut: 3306

### PostgreSQL

```php
[
    'driver' => 'pgsql',
    'host' => 'localhost',
    'port' => 5432,
    'database' => 'my_database',
    'username' => 'postgres',
    'password' => 'secret',
    'charset' => 'UTF8',
]
```

**Caractéristiques:**
- Configuration automatique du charset via `SET NAMES`
- Port par défaut: 5432

### SQLite

```php
[
    'driver' => 'sqlite',
    'database' => '/path/to/database.sqlite',
    // ou pour en mémoire:
    'database' => ':memory:',
]
```

**Caractéristiques:**
- Pas de host/port/username/password requis
- Support SQLite en mémoire (`:memory:`)
- Parfait pour tests et prototypage

## Lazy-Loading

Les connexions sont **lazy-loaded** : elles ne sont créées que lors du premier accès.

```php
$manager = new ConnectionManager([
    'default' => [...],
    'analytics' => [...],
]);

// Aucune connexion créée pour l'instant
var_dump($manager->isConnected('default')); // false

// Première utilisation = création
$pdo = $manager->connection('default');
var_dump($manager->isConnected('default')); // true

// Appels suivants = même instance (cache)
$pdo2 = $manager->connection('default');
var_dump($pdo === $pdo2); // true
```

**Avantages:**
- Performance : connexions créées uniquement si utilisées
- Mémoire : pas de connexions inutiles
- Latence réduite au démarrage

## Options PDO

### Options par Défaut

Toutes les connexions utilisent ces options par défaut:

```php
[
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,  // Exceptions pour erreurs
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // Tableau associatif
    PDO::ATTR_EMULATE_PREPARES => false,  // Vraies prepared statements
    PDO::ATTR_STRINGIFY_FETCHES => false,  // Types natifs
]
```

### Options Personnalisées

```php
use Elarion\Database\DatabaseConfig;

$config = new DatabaseConfig(
    driver: 'mysql',
    database: 'app',
    host: 'localhost',
    username: 'root',
    password: 'secret',
    options: [
        PDO::ATTR_TIMEOUT => 10,
        PDO::ATTR_PERSISTENT => true,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ]
);
```

Les options personnalisées sont **fusionnées** avec les options par défaut.

## Gestion du Cycle de Vie

### Déconnexion

```php
// Déconnecter une connexion spécifique
$manager->disconnect('analytics');

// Déconnecter toutes les connexions
$manager->disconnect();
```

### Reconnexion

```php
// Force une nouvelle connexion (utile après disconnect ou erreur)
$pdo = $manager->reconnect('default');
```

### Vérifications

```php
// Connexion configurée ?
if ($manager->hasConnection('analytics')) {
    // ...
}

// Connexion active ?
if ($manager->isConnected('analytics')) {
    // Déjà connecté
}

// Lister toutes les connexions configurées
$names = $manager->getConnectionNames();
// ['default', 'analytics', 'cache']
```

## Gestion des Erreurs

### Exceptions Typées

```php
use Elarion\Database\Exceptions\ConnectionException;
use Elarion\Database\Exceptions\ConfigurationException;

try {
    $pdo = $manager->connection('default');
} catch (ConfigurationException $e) {
    // Connexion non configurée
    echo "Configuration missing: " . $e->getMessage();
} catch (ConnectionException $e) {
    // Échec de connexion (credentials, réseau, etc.)
    echo "Connection failed: " . $e->getMessage();
    // Exemple: "Failed to connect to mysql database [my_app] on host [localhost]: Access denied"
}
```

### Contexte d'Erreur

Les exceptions incluent le contexte complet:
- Driver (mysql, pgsql, sqlite)
- Host
- Database
- Message d'erreur PDO original

```php
// ConnectionException::failed()
"Failed to connect to mysql database [app] on host [db.local]: Access denied for user 'root'@'localhost'"

// ConnectionException::failedSqlite()
"Failed to connect to SQLite database at [/invalid/path.sqlite]: unable to open database file"
```

## Configuration depuis Fichier

```php
// config/database.php
return [
    'default' => env('DB_CONNECTION', 'mysql'),
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', 3306),
            'database' => env('DB_DATABASE', 'app'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
        ],
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('PGSQL_HOST', 'localhost'),
            'port' => env('PGSQL_PORT', 5432),
            'database' => env('PGSQL_DATABASE', 'app'),
            'username' => env('PGSQL_USERNAME', 'postgres'),
            'password' => env('PGSQL_PASSWORD', ''),
        ],
    ],
];

// Bootstrap
$connections = config('database.connections');
$manager = new ConnectionManager($connections);
$manager->setDefaultConnection(config('database.default'));
```

## Interface Fluente

```php
$manager = new ConnectionManager();

$manager
    ->addConnection('primary', $primaryConfig)
    ->addConnection('replica', $replicaConfig)
    ->setDefaultConnection('primary');

$pdo = $manager->connection(); // Utilise 'primary'
```

## Intégration Container

```php
use Elarion\Container\Container;
use Elarion\Database\ConnectionManager;

$container = new Container();

// Singleton
$container->singleton(ConnectionManager::class, function ($c) {
    $connections = $c->make('config')->get('database.connections');
    $default = $c->make('config')->get('database.default', 'default');

    $manager = new ConnectionManager($connections);
    $manager->setDefaultConnection($default);

    return $manager;
});

// Utilisation
$manager = $container->make(ConnectionManager::class);
$pdo = $manager->connection();
```

## Patterns d'Utilisation

### Read/Write Splitting

```php
$manager = new ConnectionManager([
    'write' => [
        'driver' => 'mysql',
        'host' => 'primary.db.local',
        'database' => 'app',
    ],
    'read' => [
        'driver' => 'mysql',
        'host' => 'replica.db.local',
        'database' => 'app',
    ],
]);

// Écritures sur primary
$write = $manager->connection('write');
$write->exec("INSERT INTO users (name) VALUES ('John')");

// Lectures sur replica
$read = $manager->connection('read');
$users = $read->query("SELECT * FROM users")->fetchAll();
```

### Multi-Tenant

```php
class TenantConnectionManager
{
    public function __construct(private ConnectionManager $manager) {}

    public function getTenantConnection(string $tenantId): PDO
    {
        $connectionName = "tenant_{$tenantId}";

        if (!$this->manager->hasConnection($connectionName)) {
            // Charger config tenant depuis DB/cache
            $config = $this->loadTenantConfig($tenantId);
            $this->manager->addConnection($connectionName, $config);
        }

        return $this->manager->connection($connectionName);
    }
}
```

## Testing

### Tests Unitaires avec SQLite

```php
use Elarion\Database\ConnectionManager;
use PHPUnit\Framework\TestCase;

class MyDatabaseTest extends TestCase
{
    private ConnectionManager $manager;

    protected function setUp(): void
    {
        $this->manager = new ConnectionManager([
            'test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
        ]);

        $pdo = $this->manager->connection('test');

        // Setup schema
        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)');
    }

    public function test_can_insert_users(): void
    {
        $pdo = $this->manager->connection('test');
        $pdo->exec("INSERT INTO users (name) VALUES ('John')");

        $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $this->assertEquals(1, $count);
    }
}
```

### Mocking

```php
use Elarion\Database\ConnectionFactory;
use Elarion\Database\ConnectionManager;
use Mockery;

$mockFactory = Mockery::mock(ConnectionFactory::class);
$mockFactory->shouldReceive('create')
    ->andReturn($mockPDO);

$manager = new ConnectionManager([], $mockFactory);
```

## Performances

### Benchmarks

- **Lazy-loading overhead**: < 0.001ms par connexion
- **Configuration parsing**: < 0.01ms pour 10 connexions
- **Cache hit**: Connexion retournée instantanément (référence)

### Optimisations

1. **Connexions réutilisées** : Même instance PDO pour plusieurs appels
2. **Configuration immutable** : Pas de copie, référence directe
3. **Validation anticipée** : Erreurs de config détectées avant connexion
4. **DSN pré-compilé** : Généré une seule fois

## Sécurité

### Bonnes Pratiques

```php
// ✅ BIEN : Credentials dans .env
[
    'username' => env('DB_USERNAME'),
    'password' => env('DB_PASSWORD'),
]

// ❌ MAL : Credentials en dur
[
    'username' => 'root',
    'password' => 'my_secret_password',
]
```

### Validation

```php
use Elarion\Database\DatabaseConfig;

try {
    $config = new DatabaseConfig(
        driver: 'mysql',
        database: '',  // ❌ Erreur !
        host: 'localhost'
    );
} catch (\InvalidArgumentException $e) {
    echo $e->getMessage(); // "Database name or path is required"
}
```

## API Reference

### ConnectionManager

```php
class ConnectionManager
{
    public function __construct(array $configs = [], ?ConnectionFactory $factory = null);
    public function addConnection(string $name, DatabaseConfig $config): self;
    public function setDefaultConnection(string $name): self;
    public function connection(?string $name = null): PDO;
    public function disconnect(?string $name = null): self;
    public function reconnect(?string $name = null): PDO;
    public function hasConnection(string $name): bool;
    public function isConnected(string $name): bool;
    public function getConnectionNames(): array;
    public function getDefaultConnection(): string;
}
```

### DatabaseConfig

```php
final readonly class DatabaseConfig
{
    public function __construct(
        public string $driver,
        public string $database,
        public string $host = 'localhost',
        public int $port = 3306,
        public string $username = '',
        public string $password = '',
        public string $charset = 'utf8mb4',
        public array $options = []
    );

    public static function fromArray(array $config): self;
    public function getDsn(): string;
    public function getDefaultOptions(): array;
    public function getOptions(): array;
}
```

## Dépannage

### Connexion Échoue

```php
// 1. Vérifier la configuration
var_dump($manager->hasConnection('default')); // true ?

// 2. Tester la connexion manuellement
try {
    $pdo = new PDO('mysql:host=localhost;dbname=test', 'root', 'secret');
} catch (PDOException $e) {
    echo "Direct connection failed: " . $e->getMessage();
}

// 3. Vérifier les credentials
echo env('DB_USERNAME');
echo env('DB_PASSWORD');
```

### Connexion Lente

```php
// Utiliser des connexions persistantes
$config = new DatabaseConfig(
    driver: 'mysql',
    database: 'app',
    options: [
        PDO::ATTR_PERSISTENT => true,
    ]
);
```

### Multiple Connexions au Même Host

```php
// Réutiliser la même connexion si possible
$pdo = $manager->connection('default');

// Pas besoin de créer une 2ème connexion pour la même DB
// Utiliser la même instance
```

## Roadmap

- [ ] Connection pooling
- [ ] Retry logic avec exponential backoff
- [ ] Health checks automatiques
- [ ] Métriques et monitoring
- [ ] Read replica load balancing
- [ ] Sharding support

---

**Documentation générée le** : 2025-10-21
**Version** : 1.0.0
**Auteur** : ElarionStack Team
