# ElarionStack Framework

Un framework PHP moderne pour créer des API expressives, maintenables et élégantes.

## Vision

ElarionStack est inspiré par la philosophie artisanale de Laravel, offrant une architecture claire, une syntaxe fluide, et une intégration native avec les conventions modernes du développement d'API (REST, JSON:API, GraphQL).

## Prérequis

- PHP 8.5+
- Composer
- Docker & Docker Compose (optionnel)

## Installation

### Avec Docker

```bash
# Copier le fichier d'environnement
cp .env.example .env

# Démarrer le conteneur
docker-compose -f docker-compose-php85.yml up -d

# Entrer dans le conteneur
docker exec -it elarionstack_php85 bash

# Installer les dépendances
composer install
```

### Sans Docker

```bash
# Vérifier la version de PHP
php -v  # Doit être >= 8.5

# Installer les dépendances
composer install

# Démarrer le serveur de développement
php -S localhost:8000 -t public
```

## Fonctionnalités

### 🎯 Container & DI
- **PSR-11 compliant** avec auto-wiring automatique
- Service providers pour bootstrapping modulaire
- Support singletons et factory bindings
- Résolution contextuelle avec `make()` et `makeWith()`

### 🌐 HTTP & Routing
- **Router** basé sur FastRoute (performance optimale)
- Groupes de routes avec préfixes et middlewares
- Routes nommées pour génération d'URLs
- Contraintes regex sur paramètres (`where()`)
- **PSR-7** Messages (Request, Response, ServerRequest, Uri, Stream)
- **PSR-17** Factories pour création d'objets HTTP
- Helpers: `Response::json()`, `Response::html()`, `Response::redirect()`

### 🔄 Middleware
- **PSR-15** compliant avec pipeline FIFO
- Short-circuit support (arrêt précoce de la chaîne)
- Modification request/response dans la chaîne
- Intégration complète avec le router
- Support callables, classes, et MiddlewareInterface

### 💾 Database
- Connection Manager avec lazy-loading
- Support **MySQL, PostgreSQL, SQLite**
- Multiple connexions nommées indépendantes
- Configuration centralisée et validation
- Exceptions claires avec contexte (driver, host, database)

### ⚙️ Configuration
- Gestionnaire centralisé de configuration
- Support fichiers .env avec parsing
- Accès par dot notation (`config('database.host')`)
- Valeurs par défaut et types sûrs

### ✅ Qualité
- **171 tests** (317 assertions) - 100% passing
- **PHPStan level 9** - Analyse statique stricte
- **PHP-CS-Fixer** - Code style uniforme
- PHP 8.5 ready avec features modernes

## Structure du Projet

```
├── src/
│   ├── Container/        # DI Container PSR-11
│   ├── Providers/        # Service Providers
│   ├── Config/           # Configuration Manager
│   ├── Routing/          # Router HTTP
│   ├── Http/
│   │   ├── Message/      # PSR-7 Messages
│   │   ├── Factories/    # PSR-17 Factories
│   │   └── Middleware/   # PSR-15 Middleware Pipeline
│   ├── Database/         # Connection Manager
│   └── Support/          # Helpers
├── tests/                # Tests unitaires et intégration
├── config/               # Configuration
└── backlog/              # Gestion de projet (tasks, docs)
```

## Développement

```bash
# Lancer les tests
composer test

# Analyse statique (PHPStan level 9)
composer analyse

# Formater le code
composer format

# Vérifier la qualité globale
composer quality
```

## Roadmap

### ✅ Complété (8/15)

- [x] **Container DI** - PSR-11 avec auto-wiring et service providers
- [x] **Service Providers** - Système de bootstrapping modulaire
- [x] **Configuration** - Gestionnaire centralisé avec support .env
- [x] **Router HTTP** - Routing avec FastRoute, groupes, noms, contraintes
- [x] **PSR-7 Messages** - Request, Response, ServerRequest, Uri, Stream
- [x] **PSR-17 Factories** - Factories pour création d'objets HTTP
- [x] **Middleware Pipeline** - PSR-15 avec FIFO, short-circuit, router integration
- [x] **Database Connection** - PDO manager avec lazy-loading, multiple connections

### 🚧 En cours (0/7)

- [ ] **Query Builder** - Fluent API pour SQL (SELECT, INSERT, UPDATE, DELETE)
- [ ] **ORM** - Active Record avec relations, timestamps, scopes
- [ ] **API Resources** - Transformers pour réponses JSON
- [ ] **Validation** - Système de règles et messages
- [ ] **JSON:API Support** - Compliance complète JSON:API spec
- [ ] **Collection Class** - Collection Laravel-like
- [ ] **Helper Functions** - Fonctions utilitaires globales

**Progression: 53% (8/15 tasks)**

Voir le backlog complet: `backlog task list --plain`

## License

MIT License

## Status

🚧 **En développement actif** - Version 0.1.0-dev
