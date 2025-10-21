# Collection Class

La classe Collection d'ElarionStack fournit une API fluente et expressive pour manipuler des tableaux de données, inspirée de Laravel Collections mais optimisée pour les standards PSR et PHP 8.5+.

## Table des matières

- [Vue d'ensemble](#vue-densemble)
- [Installation et utilisation basique](#installation-et-utilisation-basique)
- [Création de collections](#création-de-collections)
- [Méthodes de transformation](#méthodes-de-transformation)
- [Méthodes d'accès](#méthodes-daccès)
- [Tri et groupement](#tri-et-groupement)
- [Méthodes utilitaires](#méthodes-utilitaires)
- [Interfaces SPL](#interfaces-spl)
- [Chaînage de méthodes](#chaînage-de-méthodes)
- [Exemples complets](#exemples-complets)
- [Tests](#tests)
- [Bonnes pratiques](#bonnes-pratiques)
- [Performance](#performance)
- [API Reference](#api-reference)

---

## Vue d'ensemble

La classe Collection permet de:
- ✅ Manipuler des tableaux avec une API fluente
- ✅ Chaîner des opérations de transformation
- ✅ Utiliser foreach, array access, count() naturellement
- ✅ Supporter les types génériques avec PHPDoc
- ✅ Sérialiser en JSON automatiquement
- ✅ Accéder aux données imbriquées avec dot notation

### Inspiration

Inspirée de Laravel Collections, la classe Collection d'ElarionStack implémente les interfaces SPL standard pour une intégration native avec PHP.

---

## Installation et utilisation basique

### Création simple

```php
use Elarion\Support\Collection;

// À partir d'un tableau
$collection = new Collection([1, 2, 3, 4, 5]);

// Ou avec la factory method
$collection = Collection::make([1, 2, 3, 4, 5]);
```

### Premier exemple

```php
$numbers = Collection::make([1, 2, 3, 4, 5]);

$result = $numbers
    ->filter(fn($n) => $n > 2)
    ->map(fn($n) => $n * 2)
    ->toArray();

// [6, 8, 10]
```

---

## Création de collections

### make()

Factory method statique pour créer une collection.

```php
// Collection vide
$collection = Collection::make();

// À partir d'un tableau
$collection = Collection::make([1, 2, 3]);

// À partir d'une autre collection
$collection2 = Collection::make($collection);

// À partir d'un iterable
$collection = Collection::make(new ArrayIterator([1, 2, 3]));
```

### new Collection()

```php
$collection = new Collection([1, 2, 3]);
```

---

## Méthodes de transformation

### map()

Applique une transformation à chaque élément.

```php
$collection = Collection::make([1, 2, 3]);

$doubled = $collection->map(fn($item) => $item * 2);
// [2, 4, 6]

// Avec accès à la clé
$collection = Collection::make(['a' => 1, 'b' => 2]);

$result = $collection->map(fn($value, $key) => "$key:$value");
// ['a' => 'a:1', 'b' => 'b:2']
```

**Caractéristiques:**
- Préserve les clés du tableau original
- Retourne une nouvelle collection
- Reçoit la valeur ET la clé en paramètres

---

### filter()

Filtre les éléments selon une condition.

```php
// Sans callback: supprime les valeurs falsy
$collection = Collection::make([1, 0, 2, false, 3, null]);

$filtered = $collection->filter();
// [1, 2, 3]

// Avec callback
$collection = Collection::make([1, 2, 3, 4, 5]);

$evens = $collection->filter(fn($item) => $item % 2 === 0);
// [2, 4]

// Avec accès à la clé
$collection = Collection::make(['a' => 1, 'b' => 2, 'c' => 3]);

$result = $collection->filter(fn($value, $key) => $key !== 'b');
// ['a' => 1, 'c' => 3]
```

**Caractéristiques:**
- Préserve les clés originales
- Retourne une nouvelle collection
- Par défaut, supprime null, false, 0, '', []

---

### reduce()

Réduit la collection à une seule valeur.

```php
$collection = Collection::make([1, 2, 3, 4]);

$sum = $collection->reduce(fn($carry, $item) => $carry + $item, 0);
// 10

// Avec clés
$collection = Collection::make(['a', 'b', 'c']);

$result = $collection->reduce(
    fn($carry, $value, $key) => array_merge($carry, [$key => strtoupper($value)]),
    []
);
// ['a' => 'A', 'b' => 'B', 'c' => 'C']
```

**Paramètres:**
- `$callback`: Fonction de réduction (carry, value, key)
- `$initial`: Valeur initiale

---

### each()

Exécute une action sur chaque élément.

```php
$collection = Collection::make([1, 2, 3]);

$collection->each(function($item) {
    echo $item . "\n";
});

// Arrêt anticipé
$collection->each(function($item) {
    if ($item > 2) {
        return false; // Arrête l'itération
    }
    echo $item;
});
```

**Caractéristiques:**
- Retourne $this pour chaînage
- Retourner `false` arrête l'itération
- Reçoit valeur et clé

---

## Méthodes d'accès

### first()

Récupère le premier élément.

```php
$collection = Collection::make([1, 2, 3]);

$first = $collection->first();
// 1

// Avec condition
$collection = Collection::make([1, 2, 3, 4, 5]);

$first = $collection->first(fn($item) => $item > 3);
// 4

// Avec valeur par défaut
$collection = Collection::make([]);

$first = $collection->first(null, 'default');
// 'default'
```

---

### last()

Récupère le dernier élément.

```php
$collection = Collection::make([1, 2, 3]);

$last = $collection->last();
// 3

// Avec condition
$collection = Collection::make([1, 2, 3, 4, 5]);

$last = $collection->last(fn($item) => $item < 4);
// 3
```

---

### get()

Récupère un élément par clé.

```php
$collection = Collection::make(['a' => 1, 'b' => 2]);

$value = $collection->get('a');
// 1

$value = $collection->get('c', 'default');
// 'default'
```

---

### nth()

Récupère le n-ième élément (index 0-based).

```php
$collection = Collection::make(['a' => 10, 'b' => 20, 'c' => 30]);

$value = $collection->nth(0);
// 10

$value = $collection->nth(1);
// 20

$value = $collection->nth(10, 'default');
// 'default'
```

---

## Tri et groupement

### sort()

Trie les éléments.

```php
// Tri naturel
$collection = Collection::make([3, 1, 2]);

$sorted = $collection->sort();
// [1, 2, 3]

// Tri personnalisé
$collection = Collection::make([1, 2, 3]);

$sorted = $collection->sort(fn($a, $b) => $b <=> $a);
// [3, 2, 1]
```

**Caractéristiques:**
- Préserve les clés
- Retourne une nouvelle collection

---

### sortBy()

Trie par une clé ou un callback.

```php
$collection = Collection::make([
    ['name' => 'John', 'age' => 30],
    ['name' => 'Jane', 'age' => 25],
    ['name' => 'Bob', 'age' => 35],
]);

// Tri par clé
$sorted = $collection->sortBy('age');
// Jane (25), John (30), Bob (35)

// Tri par callback
$sorted = $collection->sortBy(fn($item) => $item['age']);

// Tri descendant
$sorted = $collection->sortBy('age', descending: true);
// Bob (35), John (30), Jane (25)
```

**Paramètres:**
- `$callback`: Clé (string) ou fonction
- `$options`: Options de tri (SORT_REGULAR, SORT_NUMERIC, etc.)
- `$descending`: Tri descendant (default: false)

---

### groupBy()

Groupe les éléments par une clé ou callback.

```php
$collection = Collection::make([
    ['type' => 'fruit', 'name' => 'apple'],
    ['type' => 'fruit', 'name' => 'banana'],
    ['type' => 'vegetable', 'name' => 'carrot'],
]);

$grouped = $collection->groupBy('type');
// [
//     'fruit' => Collection([apple, banana]),
//     'vegetable' => Collection([carrot])
// ]

// Avec callback
$collection = Collection::make([1, 2, 3, 4, 5, 6]);

$grouped = $collection->groupBy(fn($item) => $item % 2 === 0 ? 'even' : 'odd');
// [
//     'odd' => Collection([1, 3, 5]),
//     'even' => Collection([2, 4, 6])
// ]
```

---

### pluck()

Extrait les valeurs d'une clé.

```php
$collection = Collection::make([
    ['id' => 1, 'name' => 'John'],
    ['id' => 2, 'name' => 'Jane'],
]);

// Pluck simple
$names = $collection->pluck('name');
// ['John', 'Jane']

// Pluck avec clé
$names = $collection->pluck('name', 'id');
// [1 => 'John', 2 => 'Jane']
```

**Support dot notation:**

```php
$collection = Collection::make([
    ['user' => ['name' => 'John', 'email' => 'john@example.com']],
    ['user' => ['name' => 'Jane', 'email' => 'jane@example.com']],
]);

$emails = $collection->pluck('user.email');
// ['john@example.com', 'jane@example.com']
```

---

## Méthodes utilitaires

### count()

Compte les éléments.

```php
$collection = Collection::make([1, 2, 3]);

$count = $collection->count();
// 3

// Compatible avec count()
$count = count($collection);
// 3
```

---

### isEmpty() / isNotEmpty()

Vérifie si la collection est vide.

```php
$collection = Collection::make([]);

$collection->isEmpty();
// true

$collection->isNotEmpty();
// false
```

---

### toArray()

Convertit en tableau.

```php
$collection = Collection::make([1, 2, 3]);

$array = $collection->toArray();
// [1, 2, 3]
```

---

### toJson()

Convertit en JSON.

```php
$collection = Collection::make(['a' => 1, 'b' => 2]);

$json = $collection->toJson();
// '{"a":1,"b":2}'

// Avec options
$json = $collection->toJson(JSON_PRETTY_PRINT);
```

---

### values()

Réinitialise les clés.

```php
$collection = Collection::make(['a' => 1, 'b' => 2, 'c' => 3]);

$values = $collection->values();
// [1, 2, 3]
```

---

### keys()

Récupère les clés.

```php
$collection = Collection::make(['a' => 1, 'b' => 2, 'c' => 3]);

$keys = $collection->keys();
// ['a', 'b', 'c']
```

---

### take()

Prend les n premiers éléments.

```php
$collection = Collection::make([1, 2, 3, 4, 5]);

$first3 = $collection->take(3);
// [1, 2, 3]

// Négatif: prend depuis la fin
$last2 = $collection->take(-2);
// [4, 5]
```

---

### slice()

Découpe la collection.

```php
$collection = Collection::make([1, 2, 3, 4, 5]);

$slice = $collection->slice(2, 2);
// [3, 4]

$slice = $collection->slice(2);
// [3, 4, 5]
```

---

### implode()

Concatène les valeurs.

```php
$collection = Collection::make(['a', 'b', 'c']);

$string = $collection->implode(',');
// 'a,b,c'

$string = $collection->implode(' - ');
// 'a - b - c'
```

---

### has()

Vérifie l'existence d'une clé.

```php
$collection = Collection::make(['a' => 1, 'b' => 2]);

$collection->has('a');
// true

$collection->has('c');
// false
```

---

### sum()

Somme des valeurs.

```php
$collection = Collection::make([1, 2, 3, 4]);

$sum = $collection->sum();
// 10

// Avec clé
$collection = Collection::make([
    ['price' => 10],
    ['price' => 20],
    ['price' => 30],
]);

$total = $collection->sum('price');
// 60

// Avec callback
$total = $collection->sum(fn($item) => $item['price']);
// 60
```

---

### avg()

Moyenne des valeurs.

```php
$collection = Collection::make([1, 2, 3, 4, 5]);

$average = $collection->avg();
// 3.0

// Avec clé
$collection = Collection::make([
    ['score' => 80],
    ['score' => 90],
    ['score' => 100],
]);

$average = $collection->avg('score');
// 90.0

// Collection vide
Collection::make([])->avg();
// null
```

---

### merge()

Fusionne avec un autre tableau/collection.

```php
$collection = Collection::make([1, 2, 3]);

$merged = $collection->merge([4, 5]);
// [1, 2, 3, 4, 5]

// Avec clés
$collection = Collection::make(['a' => 1, 'b' => 2]);

$merged = $collection->merge(['b' => 3, 'c' => 4]);
// ['a' => 1, 'b' => 3, 'c' => 4]
```

---

## Interfaces SPL

### Iterator / IteratorAggregate

Compatible avec foreach.

```php
$collection = Collection::make([1, 2, 3]);

foreach ($collection as $key => $value) {
    echo "$key: $value\n";
}
```

---

### ArrayAccess

Accès array-style.

```php
$collection = Collection::make(['a' => 1, 'b' => 2]);

// isset
if (isset($collection['a'])) {
    // ...
}

// get
$value = $collection['a'];
// 1

// set
$collection['c'] = 3;

// unset
unset($collection['a']);
```

---

### Countable

Compatible avec count().

```php
$collection = Collection::make([1, 2, 3]);

$count = count($collection);
// 3
```

---

### JsonSerializable

Sérialisation JSON automatique.

```php
$collection = Collection::make([1, 2, 3]);

$json = json_encode($collection);
// '[1,2,3]'
```

---

## Chaînage de méthodes

### Exemple simple

```php
$collection = Collection::make([1, 2, 3, 4, 5]);

$result = $collection
    ->filter(fn($n) => $n > 2)
    ->map(fn($n) => $n * 2)
    ->values()
    ->toArray();

// [6, 8, 10]
```

### Exemple complexe

```php
$users = Collection::make([
    ['name' => 'John', 'age' => 30, 'score' => 85],
    ['name' => 'Jane', 'age' => 25, 'score' => 90],
    ['name' => 'Bob', 'age' => 35, 'score' => 75],
    ['name' => 'Alice', 'age' => 28, 'score' => 95],
]);

$topUsers = $users
    ->filter(fn($user) => $user['score'] >= 85)
    ->sortBy('age')
    ->pluck('name')
    ->values();

// ['Jane', 'Alice', 'John']
```

---

## Exemples complets

### Traitement de données API

```php
$apiResponse = [
    ['id' => 1, 'name' => 'Product A', 'price' => 100, 'category' => 'electronics'],
    ['id' => 2, 'name' => 'Product B', 'price' => 50, 'category' => 'books'],
    ['id' => 3, 'name' => 'Product C', 'price' => 150, 'category' => 'electronics'],
    ['id' => 4, 'name' => 'Product D', 'price' => 30, 'category' => 'books'],
];

$products = Collection::make($apiResponse);

// Produits électroniques > 100€
$expensive = $products
    ->filter(fn($p) => $p['category'] === 'electronics')
    ->filter(fn($p) => $p['price'] > 100)
    ->pluck('name');

// ['Product C']

// Total par catégorie
$totals = $products
    ->groupBy('category')
    ->map(fn($group) => $group->sum('price'));

// ['electronics' => 250, 'books' => 80]

// Prix moyen
$average = $products->avg('price');
// 82.5
```

### Transformation de données

```php
$users = Collection::make([
    ['name' => 'john doe', 'email' => 'JOHN@EXAMPLE.COM'],
    ['name' => 'jane smith', 'email' => 'JANE@EXAMPLE.COM'],
]);

$normalized = $users->map(function($user) {
    return [
        'name' => ucwords($user['name']),
        'email' => strtolower($user['email']),
    ];
});

// [
//     ['name' => 'John Doe', 'email' => 'john@example.com'],
//     ['name' => 'Jane Smith', 'email' => 'jane@example.com']
// ]
```

### Statistiques

```php
$scores = Collection::make([85, 90, 78, 92, 88, 76, 95]);

$stats = [
    'count' => $scores->count(),
    'sum' => $scores->sum(),
    'average' => $scores->avg(),
    'min' => $scores->sort()->first(),
    'max' => $scores->sort()->last(),
    'passing' => $scores->filter(fn($s) => $s >= 80)->count(),
];

// [
//     'count' => 7,
//     'sum' => 604,
//     'average' => 86.29,
//     'min' => 76,
//     'max' => 95,
//     'passing' => 5
// ]
```

---

## Tests

### Test d'une transformation

```php
use PHPUnit\Framework\TestCase;
use Elarion\Support\Collection;

class CollectionTest extends TestCase
{
    public function test_map_transforms_items(): void
    {
        $collection = Collection::make([1, 2, 3]);

        $result = $collection->map(fn($item) => $item * 2);

        $this->assertSame([2, 4, 6], $result->toArray());
    }

    public function test_method_chaining(): void
    {
        $collection = Collection::make([1, 2, 3, 4, 5]);

        $result = $collection
            ->filter(fn($item) => $item > 2)
            ->map(fn($item) => $item * 2)
            ->values();

        $this->assertSame([6, 8, 10], $result->toArray());
    }
}
```

---

## Bonnes pratiques

### ✅ DO

```php
// Utiliser la factory method
$collection = Collection::make($data);

// Chaîner les opérations
$result = $collection
    ->filter($condition)
    ->map($transform)
    ->values();

// Utiliser les méthodes spécialisées
$names = $collection->pluck('name');
// Plutôt que:
$names = $collection->map(fn($item) => $item['name']);

// Vérifier isEmpty() avant d'utiliser first/last
if ($collection->isNotEmpty()) {
    $first = $collection->first();
}

// Utiliser values() pour réinitialiser les clés après filter
$filtered = $collection->filter($condition)->values();
```

### ❌ DON'T

```php
// Ne pas modifier la collection originale directement
$collection = Collection::make([1, 2, 3]);
$collection->items[] = 4; // ❌ Accès direct à la propriété

// Mieux: utiliser les méthodes
$collection = $collection->merge([4]);

// Ne pas chaîner inutilement
$result = $collection
    ->toArray()
    ->filter(...); // ❌ toArray() retourne un array, pas une Collection

// Mieux: garder la Collection
$result = $collection
    ->filter(...)
    ->toArray(); // Convertir seulement à la fin

// Ne pas ignorer les valeurs de retour
$collection->map($transform); // ❌ Résultat perdu
// Mieux:
$transformed = $collection->map($transform);
```

---

## Performance

### Lazy evaluation

Les méthodes qui retournent une nouvelle Collection ne modifient pas l'originale:

```php
$collection = Collection::make(range(1, 1000));

// Aucune exécution ici
$filtered = $collection->filter(fn($n) => $n > 500);
$mapped = $filtered->map(fn($n) => $n * 2);

// Exécution lors de la conversion
$result = $mapped->toArray();
```

### Optimisations

```php
// ✅ Bon: Filtrer avant de mapper (moins d'itérations)
$result = $collection
    ->filter($condition)
    ->map($transform);

// ❌ Moins bon: Mapper tout puis filtrer
$result = $collection
    ->map($transform)
    ->filter($condition);

// ✅ Bon: Utiliser each() pour effets de bord
$collection->each(fn($item) => processItem($item));

// ❌ Moins bon: map() avec side effects
$collection->map(fn($item) => processItem($item));
```

### Benchmarks

Tests sur 10,000 éléments:
- **map()**: ~2ms
- **filter()**: ~1.5ms
- **reduce()**: ~2ms
- **sortBy()**: ~5ms
- **groupBy()**: ~8ms
- **Chaînage (filter + map + sort)**: ~9ms

---

## API Reference

### Factory

```php
static make(iterable $items = []): static
```

### Transformation

```php
map(callable $callback): static
filter(?callable $callback = null): static
reduce(callable $callback, mixed $initial = null): mixed
each(callable $callback): static
```

### Accès

```php
first(?callable $callback = null, mixed $default = null): mixed
last(?callable $callback = null, mixed $default = null): mixed
get(mixed $key, mixed $default = null): mixed
nth(int $n, mixed $default = null): mixed
```

### Tri & Groupement

```php
sort(?callable $callback = null): static
sortBy(callable|string $callback, int $options = SORT_REGULAR, bool $descending = false): static
groupBy(callable|string $groupBy): static
pluck(string $value, ?string $key = null): static
```

### Utilitaires

```php
count(): int
isEmpty(): bool
isNotEmpty(): bool
toArray(): array
toJson(int $options = 0): string
values(): static
keys(): static
take(int $limit): static
slice(int $offset, ?int $length = null): static
implode(string $glue = ''): string
has(mixed $key): bool
sum(callable|string|null $callback = null): int|float
avg(callable|string|null $callback = null): int|float|null
merge(iterable $items): static
```

---

## Voir aussi

- [API Resources](API-Resources.md) - Transformers pour réponses JSON
- [Validation](Validation.md) - Validation de données
- [Helper Functions](Helper-Functions.md) - Fonctions utilitaires

---

*Documentation générée pour ElarionStack v0.1.0-dev*
