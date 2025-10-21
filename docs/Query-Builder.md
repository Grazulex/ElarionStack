# Query Builder - Documentation Complète

## Vue d'ensemble

Le Query Builder d'ElarionStack offre une interface fluide et expressive pour construire des requêtes SQL de manière programmatique. Il supporte MySQL, PostgreSQL et SQLite avec un système de Grammar qui génère le SQL approprié pour chaque driver.

### Caractéristiques principales

- **Interface fluide** : Chaînage de méthodes pour construire des requêtes complexes
- **Multi-driver** : Support MySQL, PostgreSQL, SQLite via système Grammar
- **Sécurité** : Toutes les valeurs utilisent des prepared statements (protection SQL injection)
- **Type-safe** : Utilise les union types PHP 8.5 et typage strict
- **Complet** : Support SELECT, INSERT, UPDATE, DELETE avec toutes les clauses

## Architecture

### Pattern Strategy - Grammar System

Le Query Builder utilise le pattern Strategy pour générer le SQL spécifique à chaque base de données:

```
Grammar (abstract)
├── MySqlGrammar     → Backticks pour identifiants (`table`)
├── PostgresGrammar  → Double-quotes pour identifiants ("table")
└── SqliteGrammar    → Double-quotes pour identifiants ("table")
```

### Pattern Builder

Le Builder accumule les composants de requête et délègue la compilation au Grammar:

```php
$builder
    ->select('users.id', 'users.name')
    ->from('users')
    ->where('status', 'active')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();
```

## Installation et Configuration

### Création d'une instance

```php
use Elarion\Database\Query\Builder;
use Elarion\Database\Query\Grammar\MySqlGrammar;
use PDO;

$pdo = new PDO('mysql:host=localhost;dbname=mydb', 'user', 'pass');
$grammar = new MySqlGrammar();
$builder = new Builder($pdo, $grammar);
```

### Auto-sélection du Grammar

```php
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
$grammar = match ($driver) {
    'mysql' => new MySqlGrammar(),
    'pgsql' => new PostgresGrammar(),
    'sqlite' => new SqliteGrammar(),
};
```

## Requêtes SELECT

### SELECT basique

```php
// SELECT * FROM users
$users = $builder->from('users')->get();

// SELECT id, name FROM users
$users = $builder
    ->select('id', 'name')
    ->from('users')
    ->get();

// SELECT DISTINCT status FROM users
$statuses = $builder
    ->select('status')
    ->distinct()
    ->from('users')
    ->get();
```

### Colonnes multiples

```php
// Variadic arguments
$builder->select('id', 'name', 'email');

// Array
$builder->select(['id', 'name', 'email']);

// Mixte
$builder->select('id', ['name', 'email']);

// Ajout progressif
$builder->select('id')
    ->addSelect('name')
    ->addSelect('email');
```

### Alias de table

```php
// from() et table() sont identiques
$builder->from('users');
$builder->table('users');
```

## WHERE Clauses

### WHERE basique

```php
// WHERE status = 'active'
$builder->where('status', '=', 'active');

// Shorthand (opérateur = par défaut)
$builder->where('status', 'active');

// Multiples WHERE (AND)
$builder
    ->where('status', 'active')
    ->where('age', '>', 18);
// WHERE status = ? AND age > ?
```

### OR WHERE

```php
$builder
    ->where('status', 'active')
    ->orWhere('role', 'admin');
// WHERE status = ? OR role = ?
```

### WHERE IN

```php
$builder->whereIn('id', [1, 2, 3]);
// WHERE id IN (?, ?, ?)

$builder->whereNotIn('status', ['banned', 'deleted']);
// WHERE status NOT IN (?, ?)
```

### WHERE NULL

```php
$builder->whereNull('deleted_at');
// WHERE deleted_at IS NULL

$builder->whereNotNull('email_verified_at');
// WHERE email_verified_at IS NOT NULL
```

### WHERE BETWEEN

```php
$builder->whereBetween('price', 10, 100);
// WHERE price BETWEEN ? AND ?
```

### Combinaisons complexes

```php
$results = $builder
    ->from('products')
    ->where('category', 'electronics')
    ->whereIn('brand', ['Apple', 'Samsung'])
    ->whereBetween('price', 100, 1000)
    ->whereNotNull('stock')
    ->get();
```

## JOIN Clauses

### INNER JOIN

```php
$builder
    ->from('users')
    ->join('posts', 'users.id', '=', 'posts.user_id');
// INNER JOIN posts ON users.id = posts.user_id
```

### LEFT JOIN

```php
$builder
    ->from('users')
    ->leftJoin('posts', 'users.id', '=', 'posts.user_id');
// LEFT JOIN posts ON users.id = posts.user_id
```

### RIGHT JOIN

```php
$builder
    ->from('users')
    ->rightJoin('posts', 'users.id', '=', 'posts.user_id');
// RIGHT JOIN posts ON users.id = posts.user_id
```

### Multiples JOINs

```php
$builder
    ->from('users')
    ->join('posts', 'users.id', '=', 'posts.user_id')
    ->leftJoin('comments', 'posts.id', '=', 'comments.post_id');
```

## ORDER BY, GROUP BY, HAVING

### ORDER BY

```php
// ASC (par défaut)
$builder->orderBy('name');
$builder->orderBy('name', 'asc');

// DESC
$builder->orderBy('created_at', 'desc');

// Multiples
$builder
    ->orderBy('status', 'asc')
    ->orderBy('created_at', 'desc');
```

### GROUP BY

```php
// Simple
$builder->groupBy('user_id');

// Multiples
$builder->groupBy('user_id', 'status');

// Array
$builder->groupBy(['user_id', 'status']);
```

### HAVING

```php
$builder
    ->from('orders')
    ->select('user_id', 'COUNT(*) as total')
    ->groupBy('user_id')
    ->having('total', '>', 10);
```

## LIMIT et OFFSET

### LIMIT

```php
$builder->limit(10);
// LIMIT 10

// Alias: take()
$builder->take(10);
```

### OFFSET

```php
$builder->offset(20);
// OFFSET 20

// Alias: skip()
$builder->skip(20);
```

### Pagination

```php
// Page 3, 10 résultats par page
$builder->limit(10)->offset(20);
// LIMIT 10 OFFSET 20
```

## Fonctions d'agrégation

### COUNT

```php
$count = $builder->from('users')->count();
// SELECT COUNT(*) as aggregate FROM users

// COUNT DISTINCT
$count = $builder
    ->from('users')
    ->distinct()
    ->count();
```

### MAX, MIN, AVG, SUM

```php
$maxPrice = $builder->from('products')->max('price');
$minPrice = $builder->from('products')->min('price');
$avgPrice = $builder->from('products')->avg('price');
$totalSales = $builder->from('orders')->sum('amount');
```

## INSERT Operations

### INSERT simple

```php
$builder
    ->from('users')
    ->insert([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'age' => 30,
    ]);
```

### INSERT et récupérer l'ID

```php
$id = $builder
    ->from('users')
    ->insertGetId([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);

echo $id; // "123" (string ou int selon driver)
```

## UPDATE Operations

### UPDATE basique

```php
$affected = $builder
    ->from('users')
    ->where('id', 1)
    ->update([
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);

echo $affected; // Nombre de lignes affectées
```

### INCREMENT et DECREMENT

```php
// Incrémenter une colonne
$builder
    ->from('users')
    ->where('id', 1)
    ->increment('login_count');

// Incrémenter d'un montant
$builder
    ->from('products')
    ->where('id', 5)
    ->increment('views', 10);

// Décrémenter
$builder
    ->from('products')
    ->where('id', 5)
    ->decrement('stock', 1);
```

## DELETE Operations

### DELETE basique

```php
$affected = $builder
    ->from('users')
    ->where('status', 'banned')
    ->delete();
```

### DELETE avec conditions multiples

```php
$affected = $builder
    ->from('users')
    ->where('status', 'inactive')
    ->where('last_login', '<', '2020-01-01')
    ->delete();
```

## Récupération des résultats

### get() - Tous les résultats

```php
$users = $builder->from('users')->get();
// Retourne array<array<string, mixed>>
```

### first() - Premier résultat

```php
$user = $builder->from('users')->where('id', 1)->first();
// Retourne array<string, mixed>|null
```

### find() - Recherche par ID

```php
// Utilise 'id' par défaut
$user = $builder->from('users')->find(1);

// Colonne personnalisée
$user = $builder->from('users')->find('abc-123', 'uuid');
```

### pluck() - Extraire une colonne

```php
$emails = $builder->from('users')->pluck('email');
// Retourne ['john@example.com', 'jane@example.com', ...]
```

## Debugging

### toSql() - Voir le SQL généré

```php
$sql = $builder
    ->select('id', 'name')
    ->from('users')
    ->where('status', 'active')
    ->toSql();

echo $sql;
// "select `id`, `name` from `users` where `status` = ?"
```

### getBindings() - Voir les paramètres

```php
$builder
    ->from('users')
    ->where('name', 'John')
    ->whereIn('status', ['active', 'pending']);

print_r($builder->getBindings());
// ['John', 'active', 'pending']
```

## Requêtes complexes - Exemples

### Rapport utilisateurs avec statistiques

```php
$report = $builder
    ->select(
        'users.id',
        'users.name',
        'COUNT(posts.id) as post_count',
        'MAX(posts.created_at) as last_post'
    )
    ->from('users')
    ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
    ->where('users.status', 'active')
    ->whereNotNull('users.email_verified_at')
    ->groupBy('users.id', 'users.name')
    ->having('post_count', '>', 5)
    ->orderBy('post_count', 'desc')
    ->limit(10)
    ->get();
```

### Recherche produits avec filtres

```php
$products = $builder
    ->select('products.*', 'categories.name as category_name')
    ->from('products')
    ->join('categories', 'products.category_id', '=', 'categories.id')
    ->where('products.status', 'published')
    ->whereIn('categories.slug', ['electronics', 'computers'])
    ->whereBetween('products.price', 100, 1000)
    ->whereNotNull('products.stock')
    ->orderBy('products.created_at', 'desc')
    ->limit(20)
    ->get();
```

## Sécurité

### Protection SQL Injection

**Toutes les valeurs utilisent des prepared statements automatiquement:**

```php
// ✅ SÉCURISÉ - Valeur bindée
$builder->where('email', $userInput);

// ❌ JAMAIS FAIRE - Interpolation directe
$sql = "SELECT * FROM users WHERE email = '{$userInput}'";
```

### Bindings automatiques

```php
$builder
    ->where('name', 'John')           // Binding automatique
    ->whereIn('id', [1, 2, 3])        // Bindings multiples
    ->whereBetween('age', 18, 65);    // Deux bindings

// SQL: "... WHERE name = ? AND id IN (?, ?, ?) AND age BETWEEN ? AND ?"
// Bindings: ['John', 1, 2, 3, 18, 65]
```

## Différences entre Grammars

### Identifiants

```php
// MySQL
`users`.`id` = ?

// PostgreSQL
"users"."id" = ?

// SQLite
"users"."id" = ?
```

### Compilation automatique

Le Builder sélectionne automatiquement le bon Grammar:

```php
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

// Même code, SQL différent selon driver
$builder->from('users')->where('id', 1);

// MySQL:    SELECT * FROM `users` WHERE `id` = ?
// Postgres: SELECT * FROM "users" WHERE "id" = ?
// SQLite:   SELECT * FROM "users" WHERE "id" = ?
```

## Patterns et bonnes pratiques

### Réutilisation du Builder

```php
// Le builder maintient son état
$activeUsers = $builder->from('users');

$activeUsers->where('status', 'active');
$count = $activeUsers->count(); // WHERE status = ?

$activeUsers->where('role', 'admin');
$admins = $activeUsers->get(); // WHERE status = ? AND role = ?
```

### Construction progressive

```php
$query = $builder->from('users');

if ($status) {
    $query->where('status', $status);
}

if ($minAge) {
    $query->where('age', '>=', $minAge);
}

$results = $query->get();
```

### Interface fluide

```php
// Chaînage complet
$results = $builder
    ->select('id', 'name')
    ->from('users')
    ->where('status', 'active')
    ->orderBy('created_at')
    ->limit(10)
    ->get();
```

## Performance

### Éviter N+1 queries

```php
// ❌ N+1 problème
$users = $builder->from('users')->get();
foreach ($users as $user) {
    $posts = $builder
        ->from('posts')
        ->where('user_id', $user['id'])
        ->get();
}

// ✅ Utiliser JOIN
$results = $builder
    ->select('users.*', 'posts.title')
    ->from('users')
    ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
    ->get();
```

### Sélectionner uniquement les colonnes nécessaires

```php
// ❌ Charge toutes les colonnes
$users = $builder->from('users')->get();

// ✅ Charge uniquement ce dont on a besoin
$users = $builder
    ->select('id', 'name', 'email')
    ->from('users')
    ->get();
```

## Tests

### Configuration de test

```php
use Elarion\Database\Query\Builder;
use Elarion\Database\Query\Grammar\MySqlGrammar;
use PHPUnit\Framework\TestCase;

class QueryTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(PDO::class);
    }

    public function test_basic_select(): void
    {
        $builder = new Builder($this->pdo, new MySqlGrammar());
        $sql = $builder->from('users')->toSql();

        $this->assertSame('select * from `users`', $sql);
    }
}
```

## API Reference

### Méthodes principales

| Méthode | Signature | Description |
|---------|-----------|-------------|
| `select()` | `select(string\|array ...$columns): self` | Définir les colonnes SELECT |
| `addSelect()` | `addSelect(string\|array ...$columns): self` | Ajouter des colonnes |
| `distinct()` | `distinct(): self` | Ajouter DISTINCT |
| `from()` | `from(string $table): self` | Définir la table FROM |
| `where()` | `where(string $column, mixed $operator, mixed $value, string $boolean = 'and'): self` | Ajouter WHERE clause |
| `orWhere()` | `orWhere(string $column, mixed $operator, mixed $value): self` | Ajouter OR WHERE |
| `whereIn()` | `whereIn(string $column, array $values): self` | WHERE IN clause |
| `whereNotIn()` | `whereNotIn(string $column, array $values): self` | WHERE NOT IN |
| `whereNull()` | `whereNull(string $column): self` | WHERE NULL |
| `whereNotNull()` | `whereNotNull(string $column): self` | WHERE NOT NULL |
| `whereBetween()` | `whereBetween(string $column, mixed $min, mixed $max): self` | WHERE BETWEEN |
| `join()` | `join(string $table, string $first, string $operator, string $second): self` | INNER JOIN |
| `leftJoin()` | `leftJoin(string $table, string $first, string $operator, string $second): self` | LEFT JOIN |
| `rightJoin()` | `rightJoin(string $table, string $first, string $operator, string $second): self` | RIGHT JOIN |
| `orderBy()` | `orderBy(string $column, string $direction = 'asc'): self` | ORDER BY |
| `groupBy()` | `groupBy(string\|array ...$groups): self` | GROUP BY |
| `having()` | `having(string $column, string $operator, mixed $value): self` | HAVING clause |
| `limit()` | `limit(int $limit): self` | LIMIT |
| `offset()` | `offset(int $offset): self` | OFFSET |
| `get()` | `get(): array` | Exécuter et récupérer résultats |
| `first()` | `first(): ?array` | Premier résultat |
| `find()` | `find(int\|string $id, string $column = 'id'): ?array` | Trouver par ID |
| `pluck()` | `pluck(string $column): array` | Extraire colonne |
| `count()` | `count(): int` | Compter résultats |
| `max()` | `max(string $column): mixed` | Valeur MAX |
| `min()` | `min(string $column): mixed` | Valeur MIN |
| `avg()` | `avg(string $column): mixed` | Valeur AVG |
| `sum()` | `sum(string $column): mixed` | Valeur SUM |
| `insert()` | `insert(array $values): bool` | INSERT |
| `insertGetId()` | `insertGetId(array $values): int\|string` | INSERT et retourner ID |
| `update()` | `update(array $values): int` | UPDATE |
| `increment()` | `increment(string $column, int $amount = 1): int` | Incrémenter |
| `decrement()` | `decrement(string $column, int $amount = 1): int` | Décrémenter |
| `delete()` | `delete(): int` | DELETE |
| `toSql()` | `toSql(): string` | Obtenir SQL (debug) |
| `getBindings()` | `getBindings(): array` | Obtenir bindings (debug) |

## Roadmap

Fonctionnalités futures possibles:
- [ ] Support des sous-requêtes
- [ ] Transactions
- [ ] UNION queries
- [ ] Raw expressions
- [ ] Query scopes
- [ ] Soft deletes support

---

**Version:** 1.0.0
**Dernière mise à jour:** 2025-10-21
