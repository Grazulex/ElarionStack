---
id: task-00008
title: Database Connection Manager
status: Done
assignee:
  - '@claude'
created_date: '2025-10-21 19:57'
updated_date: '2025-10-21 21:17'
labels:
  - database
  - pdo
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Créer un gestionnaire de connexions PDO pour supporter plusieurs connexions bases de données avec configuration centralisée. Fondation pour le Query Builder et l'ORM.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Le gestionnaire peut créer des connexions PDO depuis la configuration
- [x] #2 Support de MySQL, PostgreSQL, SQLite
- [x] #3 Les connexions sont lazy-loaded
- [x] #4 Support de plusieurs connexions nommées
- [x] #5 Les erreurs de connexion sont gérées avec des exceptions claires
- [x] #6 Les tests utilisent SQLite en mémoire
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
1. Create DatabaseConfig value object for connection configuration
2. Create ConnectionFactory to build PDO instances from config
3. Create ConnectionManager with lazy-loading and named connections
4. Support MySQL, PostgreSQL, SQLite drivers
5. Add comprehensive error handling with custom exceptions
6. Create tests using SQLite in-memory database
7. Integration with existing Container (PSR-11)
8. Run quality checks (PHPStan level 9, PHP-CS-Fixer, tests)
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implemented comprehensive PDO-based database connection manager with lazy-loading.

## Components Delivered:

### DatabaseConfig (Value Object)
- Immutable configuration with readonly properties
- Support MySQL, PostgreSQL, SQLite
- DSN generation for each driver
- Default PDO options (ERRMODE_EXCEPTION, FETCH_ASSOC, etc.)
- Validation: driver, database, host, port
- Factory method: fromArray()

### Exception Hierarchy
- DatabaseException (base)
- ConnectionException (connection failures with context)
- ConfigurationException (missing/invalid config)
- Clear error messages with driver, host, database context

### ConnectionFactory
- Creates PDO instances from DatabaseConfig
- Driver-specific configuration (MySQL strict mode, PostgreSQL charset)
- Wraps PDOException with ConnectionException

### ConnectionManager (Facade)
- Lazy-loading: connections created only when used
- Multiple named connections (default, analytics, etc.)
- Fluent interface: addConnection(), setDefaultConnection()
- Connection lifecycle: connect, disconnect, reconnect
- Configuration validation before connection

## Key Features:
- **Lazy-loading**: connection() creates PDO only on first call
- **Caching**: same PDO instance returned for subsequent calls
- **Multiple connections**: independent named connections
- **Configuration**: array-based config or DatabaseConfig objects
- **Validation**: driver support, port range, required fields
- **Error handling**: clear exceptions with context

## Testing:
- 34 tests, 58 assertions - all passing
- SQLite in-memory for fast, isolated tests
- Coverage: config validation, lazy-loading, caching, multiple connections, errors
- Full test suite: 171 tests, 317 assertions - all passing

## Quality:
- PHPStan level 9: clean (0 errors)
- PHP-CS-Fixer: clean (0 files fixed)
- Type-safe with PHP 8.5 features (readonly, named parameters)

## Usage Example:
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
        'host' => 'analytics.db',
        'database' => 'stats',
    ],
]);

$pdo = $manager->connection(); // default
$analytics = $manager->connection('analytics');
```
<!-- SECTION:NOTES:END -->
