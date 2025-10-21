# ORM Model - Documentation Complète

## Vue d'ensemble

Le Model ORM d'ElarionStack implémente le pattern Active Record, permettant de mapper les tables de base de données à des objets PHP. Chaque instance de Model représente une ligne de la base de données avec des méthodes pour persister, récupérer et manipuler les données.

### Caractéristiques principales

- **Active Record Pattern** : Logique métier et persistence dans la même classe
- **Interface fluide** : Méthodes statiques pour queries, méthodes d'instance pour operations
- **Magic properties** : Accès aux attributs comme propriétés (`$user->name`)
- **Timestamps automatiques** : Gestion de `created_at` et `updated_at`
- **Fillable guard** : Protection contre mass assignment
- **Change tracking** : Détection des modifications (dirty checking)
- **Query Builder integration** : Utilise le Query Builder en interne

## Architecture

### Pattern Active Record

```
Model (abstract)
├── Configuration (table, primaryKey, fillable, timestamps)
├── Static Methods (find, all, where, query)
├── Instance Methods (save, delete, fill)
├── Magic Methods (__get, __set, __isset, __unset)
└── Attributes Management (isDirty, getChanges, toArray)
```

### Cycle de vie

```
1. Création: new User(['name' => 'John'])
2. Hydratation: fill() avec fillable guard
3. Persistence: save() → INSERT ou UPDATE
4. Tracking: syncOriginal() pour change detection
5. Suppression: delete()
```

## Création d'un Model

### Model basique

```php
use Elarion\Database\Model;

class User extends Model
{
    // Configuration
    protected string $table = 'users';              // Optionnel
    protected string $primaryKey = 'id';            // Défaut: 'id'
    protected array $fillable = ['name', 'email'];  // Mass assignment
    protected bool $timestamps = true;              // Défaut: true
}
```

### Configuration globale

```php
// Définir la connexion PDO (une fois)
Model::setConnection($pdo);

// Tous les models utiliseront cette connexion
```

### Table name automatique

Si `$table` n'est pas défini, le nom est déduit du nom de classe:

```php
class User extends Model {}        // → 'users'
class BlogPost extends Model {}    // → 'blog_posts'
class Category extends Model {}    // → 'categorys' (simple +s)
```

## Configuration

### Propriétés de configuration

```php
class User extends Model
{
    // Nom de la table
    protected string $table = 'users';

    // Clé primaire
    protected string $primaryKey = 'id';

    // Attributs mass-assignable
    protected array $fillable = ['name', 'email', 'bio'];

    // Activer/désactiver timestamps
    protected bool $timestamps = true;

    // Nom des colonnes timestamps (personnalisables)
    protected string $createdAtColumn = 'created_at';
    protected string $updatedAtColumn = 'updated_at';
}
```

### Fillable vs Guarded

```php
// ✅ Whitelist (recommandé)
protected array $fillable = ['name', 'email'];

// ✅ Tous les attributs fillable (vide = tout autorisé)
protected array $fillable = [];

// ❌ Blacklist not supported (utiliser fillable vide si besoin)
```

## Requêtes (Query Methods)

### find() - Recherche par ID

```php
// Trouver par clé primaire
$user = User::find(1);

if ($user) {
    echo $user->name;
} else {
    echo "Not found";
}
```

### all() - Tous les enregistrements

```php
$users = User::all();

foreach ($users as $user) {
    echo $user->name;
}
```

### where() - Délégation au Query Builder

```php
// Retourne un Query Builder, pas des Models
$results = User::where('status', 'active')->get();

// Pour obtenir des Model instances, utiliser newFromBuilder()
$users = array_map(
    fn($attrs) => User::newFromBuilder($attrs),
    $results
);
```

### query() - Query Builder complet

```php
$users = User::query()
    ->select('id', 'name', 'email')
    ->where('status', 'active')
    ->where('age', '>', 18)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();
```

## Persistence (Save, Delete)

### Créer un enregistrement

```php
// Méthode 1: Constructeur + save()
$user = new User([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);
$user->save(); // INSERT

echo $user->id; // Auto-increment ID
```

```php
// Méthode 2: fill() + save()
$user = new User();
$user->fill([
    'name' => 'Jane Doe',
    'email' => 'jane@example.com',
]);
$user->save();
```

```php
// Méthode 3: Properties + save()
$user = new User();
$user->name = 'Bob Smith';
$user->email = 'bob@example.com';
$user->save();
```

### Mettre à jour un enregistrement

```php
// Récupérer puis modifier
$user = User::find(1);
$user->name = 'Updated Name';
$user->email = 'new@example.com';
$user->save(); // UPDATE seulement les attributs modifiés

// save() détecte automatiquement si UPDATE ou INSERT
```

### Smart save()

```php
$user = new User(['name' => 'John']);
$user->save(); // INSERT (exists = false)

$user->name = 'Jane';
$user->save(); // UPDATE (exists = true)

$user->save(); // NO-OP (pas de changements)
```

### Supprimer un enregistrement

```php
$user = User::find(1);
$result = $user->delete(); // Retourne true/false

// delete() retourne false si le model n'existe pas
$newUser = new User();
$newUser->delete(); // false
```

## Magic Property Access

### __get() - Lecture

```php
$user = new User(['name' => 'John', 'email' => 'john@example.com']);

echo $user->name;   // 'John'
echo $user->email;  // 'john@example.com'
echo $user->bio;    // null (n'existe pas)
```

### __set() - Écriture

```php
$user = new User();

$user->name = 'John';
$user->email = 'john@example.com';
$user->age = 30;
```

### __isset() - Vérification

```php
$user = new User(['name' => 'John']);

if (isset($user->name)) {
    echo "Name is set";
}

if (!isset($user->email)) {
    echo "Email not set";
}
```

### __unset() - Suppression

```php
$user = new User(['name' => 'John', 'email' => 'john@example.com']);

unset($user->email);

isset($user->email); // false
```

## Timestamps

### Fonctionnement automatique

```php
class User extends Model
{
    protected bool $timestamps = true; // Par défaut
}

$user = new User(['name' => 'John']);
$user->save();

echo $user->created_at; // '2025-10-21 23:45:00'
echo $user->updated_at; // '2025-10-21 23:45:00'

// Mise à jour
$user->name = 'Jane';
$user->save();

echo $user->created_at; // '2025-10-21 23:45:00' (inchangé)
echo $user->updated_at; // '2025-10-21 23:46:15' (mis à jour)
```

### Désactiver les timestamps

```php
class Product extends Model
{
    protected bool $timestamps = false;
}

$product = new Product(['name' => 'Widget']);
$product->save();

// created_at et updated_at ne sont pas définis
```

### Colonnes personnalisées

```php
class Post extends Model
{
    protected string $createdAtColumn = 'published_at';
    protected string $updatedAtColumn = 'modified_at';
}
```

## Change Tracking

### isDirty() - Vérifier les modifications

```php
$user = User::find(1);
$user->isDirty(); // false (vient d'être chargé)

$user->name = 'New Name';
$user->isDirty(); // true (modifié)
$user->isDirty('name'); // true
$user->isDirty('email'); // false

$user->isDirty(['name', 'email']); // true (au moins un modifié)
```

### getDirty() - Attributs modifiés

```php
$user = User::find(1);
// {id: 1, name: 'John', email: 'john@example.com'}

$user->name = 'Jane';
$user->email = 'jane@example.com';

$dirty = $user->getDirty();
// ['name' => 'Jane', 'email' => 'jane@example.com']
```

### getChanges() - Anciennes et nouvelles valeurs

```php
$user = User::find(1);
// {id: 1, name: 'John', email: 'john@example.com'}

$user->name = 'Jane';
$user->email = 'jane@example.com';

$changes = $user->getChanges();
// [
//     'name' => ['John', 'Jane'],
//     'email' => ['john@example.com', 'jane@example.com']
// ]
```

### Workflow avec change tracking

```php
$user = User::find(1);

if ($user->isDirty()) {
    echo "Modifications détectées:";
    foreach ($user->getChanges() as $key => $change) {
        [$old, $new] = $change;
        echo "{$key}: {$old} → {$new}";
    }
}
```

## Mass Assignment

### fill() avec fillable guard

```php
class User extends Model
{
    protected array $fillable = ['name', 'email'];
}

$user = new User();

// ✅ Seulement name et email seront définis
$user->fill([
    'name' => 'John',
    'email' => 'john@example.com',
    'password' => 'secret',      // Ignoré (pas fillable)
    'is_admin' => true,          // Ignoré (pas fillable)
]);

echo $user->name;     // 'John'
echo $user->email;    // 'john@example.com'
echo $user->password; // null (pas défini)
```

### Protection contre mass assignment

```php
// ❌ RISQUE: Tous les attributs de la requête
$user->fill($_POST);

// ✅ SÉCURISÉ: Seulement fillable attributes
class User extends Model
{
    protected array $fillable = ['name', 'email'];
}
$user->fill($_POST); // is_admin dans $_POST sera ignoré
```

### Bypass du fillable guard

```php
// Utiliser les properties directement (pas de guard)
$user->name = 'John';
$user->is_admin = true; // OK (pas de fillable check)

// Ou setRawAttributes() (protected, pour usage interne)
```

## Sérialisation

### toArray() - Convertir en array

```php
$user = new User([
    'name' => 'John',
    'email' => 'john@example.com',
]);

$array = $user->toArray();
// [
//     'name' => 'John',
//     'email' => 'john@example.com'
// ]
```

### toJson() - Convertir en JSON

```php
$user = new User([
    'name' => 'John',
    'email' => 'john@example.com',
]);

$json = $user->toJson();
// '{"name":"John","email":"john@example.com"}'
```

### Usage API

```php
// Response JSON
header('Content-Type: application/json');
echo User::find(1)->toJson();

// Collection
$users = User::all();
$json = json_encode(array_map(fn($u) => $u->toArray(), $users));
```

## Patterns et exemples

### Repository Pattern

```php
class UserRepository
{
    public function findActive(): array
    {
        return User::where('status', 'active')->get();
    }

    public function findByEmail(string $email): ?User
    {
        $result = User::where('email', $email)->first();
        return $result ? User::newFromBuilder($result) : null;
    }

    public function create(array $data): User
    {
        $user = new User($data);
        $user->save();
        return $user;
    }
}
```

### Service Layer

```php
class UserService
{
    public function register(array $data): User
    {
        $user = new User([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        $user->password = password_hash($data['password'], PASSWORD_DEFAULT);
        $user->save();

        return $user;
    }

    public function updateProfile(User $user, array $data): void
    {
        $user->fill($data);

        if ($user->isDirty()) {
            $user->save();
        }
    }
}
```

### Form handling

```php
// Formulaire de création
$user = new User();
$user->fill($_POST);

if ($user->save()) {
    redirect('/users/' . $user->id);
} else {
    show_errors();
}

// Formulaire de mise à jour
$user = User::find($_POST['id']);
$user->fill($_POST);

if ($user->isDirty()) {
    $user->save();
    echo "Updated";
} else {
    echo "No changes";
}
```

## Relations (Future)

Actuellement non implémentées, mais architecture prête pour:

```php
// Exemple futur
class User extends Model
{
    public function posts()
    {
        return $this->hasMany(Post::class, 'user_id');
    }
}

class Post extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

// Usage
$user = User::find(1);
$posts = $user->posts()->get();
```

## Scopes (Future)

```php
// Exemple futur
class User extends Model
{
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

// Usage
$users = User::active()->get();
```

## Tests

### Configuration de test

```php
use Elarion\Database\Model;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(PDO::class);
        Model::setConnection($this->pdo);
    }

    public function test_model_can_be_created(): void
    {
        $user = new User(['name' => 'John']);

        $this->assertSame('John', $user->name);
    }
}
```

### Test avec base de données réelle

```php
class UserIntegrationTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        // SQLite en mémoire pour tests
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->exec('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT,
                email TEXT,
                created_at TEXT,
                updated_at TEXT
            )
        ');

        Model::setConnection($this->pdo);
    }

    public function test_user_can_be_saved(): void
    {
        $user = new User(['name' => 'John', 'email' => 'john@example.com']);
        $result = $user->save();

        $this->assertTrue($result);
        $this->assertNotNull($user->id);
    }
}
```

## Performance

### Éviter N+1

```php
// ❌ N+1 problème
$users = User::all();
foreach ($users as $user) {
    $posts = Post::where('user_id', $user->id)->get(); // N queries
}

// ✅ Utiliser JOIN (pour l'instant)
$results = User::query()
    ->select('users.*', 'posts.title')
    ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
    ->get();
```

### Sélectionner colonnes spécifiques

```php
// ❌ Charge toutes les colonnes
$users = User::all();

// ✅ Charge uniquement nécessaire
$users = User::query()
    ->select('id', 'name', 'email')
    ->get();
```

### Pagination manuelle

```php
$page = 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$users = User::query()
    ->limit($perPage)
    ->offset($offset)
    ->get();
```

## Bonnes pratiques

### ✅ DO

```php
// Utiliser fillable pour protection
protected array $fillable = ['name', 'email'];

// Vérifier isDirty avant save
if ($user->isDirty()) {
    $user->save();
}

// Utiliser transactions pour opérations multiples
$pdo->beginTransaction();
try {
    $user->save();
    $profile->save();
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
}

// Toujours vérifier résultats find()
$user = User::find($id);
if (!$user) {
    throw new NotFoundException();
}
```

### ❌ DON'T

```php
// ❌ Ne pas bypasser fillable sans raison
$user->fill($_POST); // Unsafe si fillable pas défini

// ❌ Ne pas oublier save()
$user->name = 'John'; // Modifie en mémoire
// Oublié $user->save(); ← Non persisté!

// ❌ Ne pas modifier après delete
$user->delete();
$user->name = 'John'; // Illogique

// ❌ Ne pas accéder attributs sans vérifier
echo $user->email; // Peut être null
```

## Debugging

### Voir les attributs

```php
$user = User::find(1);

var_dump($user->toArray());
print_r($user->getChanges());
```

### Vérifier l'état

```php
$user = new User();
echo $user->exists ? 'Exists in DB' : 'New model';

echo $user->isDirty() ? 'Has changes' : 'Clean';
```

## API Reference

### Méthodes statiques

| Méthode | Signature | Description |
|---------|-----------|-------------|
| `setConnection()` | `setConnection(PDO $connection): void` | Définir connexion PDO |
| `query()` | `query(): Builder` | Nouveau Query Builder |
| `find()` | `find(int\|string $id): ?static` | Trouver par ID |
| `all()` | `all(): array<static>` | Tous les enregistrements |
| `where()` | `where(string $column, mixed $operator, mixed $value): Builder` | WHERE query |

### Méthodes d'instance

| Méthode | Signature | Description |
|---------|-----------|-------------|
| `fill()` | `fill(array $attributes): self` | Mass assignment |
| `save()` | `save(): bool` | INSERT ou UPDATE |
| `delete()` | `delete(): bool` | Supprimer |
| `isDirty()` | `isDirty(string\|array\|null $attributes): bool` | Vérifier modifications |
| `getDirty()` | `getDirty(): array` | Attributs modifiés |
| `getChanges()` | `getChanges(): array` | Anciennes/nouvelles valeurs |
| `toArray()` | `toArray(): array` | Convertir en array |
| `toJson()` | `toJson(): string` | Convertir en JSON |

### Propriétés magiques

| Méthode | Description |
|---------|-------------|
| `__get($key)` | Accès lecture attributs |
| `__set($key, $value)` | Accès écriture attributs |
| `__isset($key)` | Vérifier attribut |
| `__unset($key)` | Supprimer attribut |

## Roadmap

Fonctionnalités futures:
- [ ] Relations (hasMany, belongsTo, hasOne, belongsToMany)
- [ ] Eager loading (with, load)
- [ ] Query scopes
- [ ] Events (creating, created, updating, updated, deleting, deleted)
- [ ] Soft deletes
- [ ] Observers
- [ ] Accessors & Mutators
- [ ] Casting (dates, JSON, custom)
- [ ] Collection class pour résultats

---

**Version:** 1.0.0
**Dernière mise à jour:** 2025-10-21
