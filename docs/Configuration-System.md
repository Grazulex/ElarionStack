# Système de Configuration - ElarionStack

## Vue d'ensemble

Le système de configuration d'ElarionStack fournit une API élégante et type-safe pour gérer les paramètres de l'application. Inspiré de Laravel mais modernisé avec PHP 8.5.

## Architecture

### Principes SOLID Appliqués

- **SRP** : Chaque composant a une responsabilité unique
- **OCP** : Extensible via interfaces (nouveaux loaders sans modifier le code existant)
- **LSP** : Toutes les implémentations respectent leurs contrats
- **ISP** : Interfaces focalisées et spécifiques
- **DIP** : Dépendances injectées, pas de couplage fort

### Composants Principaux

```
src/Config/
├── Contracts/           # Interfaces
│   ├── ConfigRepositoryInterface.php
│   ├── ConfigLoaderInterface.php
│   └── ConfigCacheInterface.php
├── Loaders/             # Chargeurs de fichiers
│   └── PhpFileLoader.php
├── Cache/               # Système de cache
│   └── FileConfigCache.php
├── ConfigRepository.php  # Stockage des configurations
├── ConfigManager.php     # Orchestrateur principal
├── DotNotationParser.php # Parser pour notation point
├── Environment.php       # Enum des environnements
└── ConfigServiceProvider.php
```

## Utilisation

### Accès Basique

```php
// Via la fonction helper
$name = config('app.name');
$host = config('database.connections.mysql.host', 'localhost');

// Avec valeur par défaut
$debug = config('app.debug', false);
```

### Modification des Valeurs

```php
// Définir une valeur unique
config(['app.name' => 'Mon Application']);

// Définir plusieurs valeurs
config([
    'app.name' => 'Mon App',
    'app.env' => 'production',
]);
```

### Accès Direct au Manager

```php
/** @var \Elarion\Config\ConfigManager $config */
$config = app('config');

// Vérifier l'existence
if ($config->has('app.timezone')) {
    $timezone = $config->get('app.timezone');
}

// Obtenir toute la configuration
$all = $config->all();
```

## Notation Point (Dot Notation)

Le système supporte la notation point pour accéder aux valeurs imbriquées :

```php
// Fichier config/database.php
return [
    'connections' => [
        'mysql' => [
            'host' => 'localhost',
            'port' => 3306,
        ],
    ],
];

// Accès
$host = config('database.connections.mysql.host'); // 'localhost'
```

## Fichiers de Configuration

### Structure des Fichiers

Les fichiers de configuration sont des fichiers PHP qui retournent un tableau :

```php
// config/app.php
<?php

return [
    'name' => env('APP_NAME', 'ElarionStack'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'timezone' => 'UTC',
    'locale' => 'fr',
];
```

### Chargement Automatique

Tous les fichiers `.php` dans le dossier `config/` sont automatiquement chargés.
Le nom du fichier devient la clé racine :

```
config/app.php       → accessible via 'app.xxx'
config/database.php  → accessible via 'database.xxx'
config/services.php  → accessible via 'services.xxx'
```

## Variables d'Environnement

Utilisez la fonction `env()` pour accéder aux variables d'environnement :

```php
// config/app.php
return [
    'name' => env('APP_NAME', 'ElarionStack'),
    'url' => env('APP_URL', 'http://localhost'),
];
```

### Conversion Automatique

La fonction `env()` convertit automatiquement certaines valeurs :

```php
env('ENABLED', true)  // true/false/null
'true'    => true
'(true)'  => true
'false'   => false
'(false)' => false
'empty'   => ''
'(empty)' => ''
'null'    => null
'(null)'  => null
```

## Cache de Configuration

### En Production

En production, les configurations sont automatiquement cachées pour optimiser les performances :

```php
// Le cache est activé si Environment::Production ou ::Staging
$env = Environment::detect(); // depuis APP_ENV
```

### Fichier de Cache

- **Emplacement** : `storage/framework/config.php`
- **Format** : PHP array compilé (opcache-friendly)
- **Génération** : Automatique au premier accès

### Commandes de Gestion

```php
/** @var \Elarion\Config\ConfigManager $config */
$config = app('config');

// Rafraîchir la configuration (recharge depuis les fichiers)
$config->refresh();

// Vider le cache
$config->clearCache();
```

## Environnements

### Enum Environment

PHP 8.5 enum pour une gestion type-safe des environnements :

```php
use Elarion\Config\Environment;

$env = Environment::detect(); // depuis APP_ENV

if ($env->isProduction()) {
    // Code production
}

if ($env->isDevelopment()) {
    // Code développement
}

// Vérifier si le cache doit être utilisé
$shouldCache = $env->shouldCache(); // true pour Production/Staging
```

### Valeurs Supportées

- `Development` (dev, development, local)
- `Testing` (test, testing)
- `Staging` (stage, staging)
- `Production` (production, prod - défaut)

## Extensibilité

### Créer un Nouveau Loader

```php
use Elarion\Config\Contracts\ConfigLoaderInterface;

class JsonFileLoader implements ConfigLoaderInterface
{
    public function load(string $path): array
    {
        $content = file_get_contents($path);
        return json_decode($content, true);
    }

    public function supports(string $path): bool
    {
        return str_ends_with($path, '.json');
    }
}

// Enregistrer dans le container
$container->singleton(ConfigLoaderInterface::class, JsonFileLoader::class);
```

### Créer un Cache Personnalisé

```php
use Elarion\Config\Contracts\ConfigCacheInterface;

class RedisConfigCache implements ConfigCacheInterface
{
    public function has(): bool { /* ... */ }
    public function get(): array { /* ... */ }
    public function put(array $config): void { /* ... */ }
    public function clear(): void { /* ... */ }
    public function getCachePath(): string { /* ... */ }
}
```

## Performances

### Optimisations Implémentées

1. **Lazy Loading** : Les configurations ne sont chargées qu'au premier accès
2. **Cache Opcache** : Fichier PHP compilé optimisé par opcache
3. **Chargement Atomique** : Une seule lecture pour toutes les configs
4. **Invalidation Opcache** : Cache vidé automatiquement lors des mises à jour

### Benchmarks Typiques

- **Sans cache** : ~5-10ms pour 50 fichiers de config
- **Avec cache** : ~0.1ms (lecture d'un seul fichier compilé)
- **Gain** : ~50-100x plus rapide en production

## Tests

### Structure des Tests

```
tests/Unit/Config/
├── DotNotationParserTest.php
├── ConfigRepositoryTest.php
├── PhpFileLoaderTest.php
├── FileConfigCacheTest.php
└── ConfigManagerTest.php
```

### Exemples de Tests

```php
public function test_can_access_nested_config_with_dot_notation(): void
{
    $config = new ConfigRepository();
    $config->load('app', ['name' => 'Test', 'nested' => ['key' => 'value']]);

    $this->assertEquals('value', $config->get('app.nested.key'));
}

public function test_returns_default_when_key_not_found(): void
{
    $config = new ConfigRepository();

    $this->assertEquals('default', $config->get('missing.key', 'default'));
}
```

## Sécurité

### Bonnes Pratiques

1. **Jamais de secrets dans config/** - Utilisez `.env` pour les secrets
2. **Ne commitez jamais .env** - Utilisez `.env.example` comme template
3. **Variables sensibles** - Toujours via `env()`, jamais en dur

### Validation

```php
// config/app.php
return [
    'key' => env('APP_KEY') ?: throw new \RuntimeException('APP_KEY must be set'),
];
```

## Migration depuis Autre Framework

### Depuis Laravel

Le système est très similaire à Laravel :

```php
// Laravel → ElarionStack (identique)
config('app.name')
config('app.name', 'default')
config(['app.name' => 'value'])
```

### Depuis Symfony

```php
// Symfony
$container->getParameter('app.name')

// ElarionStack
config('app.name')
```

## Dépannage

### Configuration Non Chargée

```php
// Vérifier si le fichier existe
file_exists(base_path('config/app.php'));

// Vérifier les permissions
is_readable(base_path('config/app.php'));

// Forcer le rechargement
config()->refresh();
```

### Cache Corrompu

```php
// Vider le cache
config()->clearCache();

// Ou manuellement
unlink(storage_path('framework/config.php'));
```

### Valeur Null Inattendue

```php
// Vérifier que la clé existe
if (config()->has('app.name')) {
    $name = config('app.name');
}

// Ou avec défaut
$name = config('app.name', 'Mon App');
```

## Références API

### ConfigManager

```php
interface ConfigRepositoryInterface
{
    public function get(string $key, mixed $default = null): mixed;
    public function has(string $key): bool;
    public function set(string $key, mixed $value): void;
    public function all(): array;
    public function load(string $name, array $config): void;
}
```

### Environment

```php
enum Environment: string
{
    case Development = 'development';
    case Testing = 'testing';
    case Staging = 'staging';
    case Production = 'production';

    public function isDevelopment(): bool;
    public function isProduction(): bool;
    public function shouldCache(): bool;
    public static function detect(): self;
}
```

## Roadmap

- [ ] Support YAML/JSON loaders
- [ ] Configuration validation via schema
- [ ] Hot reload en développement
- [ ] Commandes CLI pour gérer le cache
- [ ] Synchronisation Redis pour multi-serveurs

---

**Documentation générée le** : <?php echo date('Y-m-d H:i:s'); ?>
**Version** : 1.0.0
**Auteur** : ElarionStack Team
