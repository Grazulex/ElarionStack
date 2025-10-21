# Validation System

Le système de validation d'ElarionStack fournit une architecture robuste et extensible pour valider les données utilisateur avec des règles configurables, des messages d'erreur clairs, et un support complet pour les structures de données complexes.

## Table des matières

- [Vue d'ensemble](#vue-densemble)
- [Architecture](#architecture)
- [Installation et utilisation basique](#installation-et-utilisation-basique)
- [Règles built-in](#règles-built-in)
- [Règles multiples](#règles-multiples)
- [Messages d'erreur](#messages-derreur)
- [Règles personnalisées](#règles-personnalisées)
- [Validation de tableaux imbriqués](#validation-de-tableaux-imbriqués)
- [Méthodes du Validator](#méthodes-du-validator)
- [Exemples complets](#exemples-complets)
- [Tests](#tests)
- [Bonnes pratiques](#bonnes-pratiques)
- [Performance](#performance)
- [API Reference](#api-reference)
- [Roadmap](#roadmap)

---

## Vue d'ensemble

Le système de validation permet de:
- ✅ Valider des données contre des règles configurables
- ✅ Obtenir des messages d'erreur clairs et personnalisables
- ✅ Créer des règles personnalisées (Closures ou classes)
- ✅ Valider des tableaux imbriqués avec dot notation
- ✅ Supporter l'internationalisation des messages
- ✅ Garantir la sécurité et l'intégrité des données

### Inspiration

Le système s'inspire du validator de Laravel avec une architecture similaire mais optimisée pour les standards PSR et PHP 8.5+.

---

## Architecture

### Pattern: Strategy + Factory

```
┌─────────────────────────────────────────────────────────────┐
│                        Validator                             │
│  - Orchestration de la validation                           │
│  - Gestion des erreurs                                      │
│  - Support string rules, Rule instances, Closures          │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       │ uses
                       ▼
           ┌───────────────────────┐
           │    Rule Interface     │
           │  - passes()           │
           │  - message()          │
           │  - setData()          │
           └───────────┬───────────┘
                       │
                       │ implements
                       ▼
           ┌───────────────────────┐
           │   AbstractRule        │
           │  - Message templates  │
           │  - Placeholders       │
           └───────────┬───────────┘
                       │
                       │ extends
                       ▼
        ┌──────────────────────────────┐
        │   Built-in Rules             │
        │  - Required                  │
        │  - Email                     │
        │  - Min / Max                 │
        │  - Type rules (String, Int)  │
        └──────────────────────────────┘
```

### Composants principaux

1. **Rule Interface** - Contrat pour toutes les règles
2. **AbstractRule** - Implémentation de base avec gestion des messages
3. **Built-in Rules** - Règles prêtes à l'emploi (Required, Email, Min, Max, etc.)
4. **Validator** - Orchestration de la validation et gestion des erreurs

---

## Installation et utilisation basique

### Validation simple

```php
use Elarion\Validation\Validator;

$data = [
    'email' => 'john@example.com',
    'age' => 25,
];

$rules = [
    'email' => 'required|email',
    'age' => 'required|integer|min:18',
];

$validator = Validator::make($data, $rules);

if ($validator->validate()) {
    // Validation réussie
    $validated = $validator->validated();
} else {
    // Validation échouée
    $errors = $validator->errors();
}
```

### Factory method

```php
// Méthode statique pour plus de concision
$validator = Validator::make($data, $rules);

// Équivalent à:
$validator = new Validator($data, $rules);
```

---

## Règles built-in

### Required

Le champ doit être présent et non vide.

```php
$rules = ['name' => 'required'];

// ✅ Passe
['name' => 'John']
['name' => '0']
['name' => [1, 2, 3]]

// ❌ Échoue
['name' => null]
['name' => '']
['name' => '   ']  // Whitespace seulement
['name' => []]     // Tableau vide
```

**Message d'erreur**: `The :attribute field is required.`

---

### Email

Le champ doit être une adresse email valide.

```php
$rules = ['email' => 'email'];

// ✅ Passe
['email' => 'john@example.com']
['email' => 'user+tag@domain.co.uk']

// ❌ Échoue
['email' => 'not-an-email']
['email' => 'missing@domain']
['email' => 123]  // Non-string
```

**Message d'erreur**: `The :attribute must be a valid email address.`

---

### Min

Le champ doit avoir une valeur/longueur/count minimum.

```php
$rules = [
    'name' => 'min:3',    // String: longueur min
    'age' => 'min:18',    // Number: valeur min
    'items' => 'min:2',   // Array: count min
];

// Strings (longueur)
['name' => 'John']  // ✅ 4 caractères >= 3
['name' => 'Jo']    // ❌ 2 caractères < 3

// Numbers (valeur)
['age' => 18]  // ✅ 18 >= 18
['age' => 17]  // ❌ 17 < 18

// Arrays (count)
['items' => [1, 2, 3]]  // ✅ 3 éléments >= 2
['items' => [1]]        // ❌ 1 élément < 2
```

**Message d'erreur**: `The :attribute must be at least :min.`

---

### Max

Le champ ne doit pas dépasser une valeur/longueur/count maximum.

```php
$rules = [
    'name' => 'max:50',      // String: longueur max
    'age' => 'max:100',      // Number: valeur max
    'items' => 'max:10',     // Array: count max
];

// Strings (longueur)
['name' => 'John']           // ✅ 4 caractères <= 50
['name' => str_repeat('a', 51)]  // ❌ 51 caractères > 50

// Numbers (valeur)
['age' => 100]  // ✅ 100 <= 100
['age' => 101]  // ❌ 101 > 100

// Arrays (count)
['items' => range(1, 10)]  // ✅ 10 éléments <= 10
['items' => range(1, 11)]  // ❌ 11 éléments > 10
```

**Message d'erreur**: `The :attribute must not exceed :max.`

---

### String

Le champ doit être une chaîne de caractères.

```php
$rules = ['name' => 'string'];

// ✅ Passe
['name' => 'John']
['name' => '']

// ❌ Échoue
['name' => 123]
['name' => true]
['name' => []]
```

**Message d'erreur**: `The :attribute must be a string.`

---

### Integer

Le champ doit être un entier.

```php
$rules = ['age' => 'integer'];

// ✅ Passe
['age' => 25]
['age' => 0]
['age' => -5]

// ❌ Échoue
['age' => '25']    // String
['age' => 25.5]    // Float
```

**Message d'erreur**: `The :attribute must be an integer.`

---

### Numeric

Le champ doit être numérique (int, float, ou string numérique).

```php
$rules = ['price' => 'numeric'];

// ✅ Passe
['price' => 42]
['price' => 42.5]
['price' => '42']
['price' => '42.5']

// ❌ Échoue
['price' => 'abc']
['price' => true]
```

**Message d'erreur**: `The :attribute must be numeric.`

---

### Boolean

Le champ doit être une valeur booléenne.

Accepte: `true`, `false`, `1`, `0`, `'1'`, `'0'`

```php
$rules = ['active' => 'boolean'];

// ✅ Passe
['active' => true]
['active' => false]
['active' => 1]
['active' => 0]
['active' => '1']
['active' => '0']

// ❌ Échoue
['active' => 'yes']
['active' => 'no']
['active' => 2]
```

**Message d'erreur**: `The :attribute must be a boolean.`

---

### Array

Le champ doit être un tableau.

```php
$rules = ['items' => 'array'];

// ✅ Passe
['items' => []]
['items' => [1, 2, 3]]
['items' => ['key' => 'value']]

// ❌ Échoue
['items' => 'not-array']
['items' => 123]
```

**Message d'erreur**: `The :attribute must be an array.`

---

## Règles multiples

### Avec pipe separator (|)

```php
$rules = [
    'email' => 'required|email',
    'name' => 'required|string|min:3|max:50',
    'age' => 'required|integer|min:18|max:100',
];

$validator = Validator::make($data, $rules);
```

### Avec tableau

```php
$rules = [
    'email' => ['required', 'email'],
    'name' => ['required', 'string', 'min:3', 'max:50'],
];

$validator = Validator::make($data, $rules);
```

### Avec instances de Rule

```php
use Elarion\Validation\Rules\Required;
use Elarion\Validation\Rules\Email;
use Elarion\Validation\Rules\Min;
use Elarion\Validation\Rules\Max;

$rules = [
    'email' => [new Required(), new Email()],
    'age' => [new Required(), new Min(18), new Max(100)],
];

$validator = Validator::make($data, $rules);
```

---

## Messages d'erreur

### Messages par défaut

Chaque règle a un message par défaut avec placeholders:

```php
$validator = Validator::make(
    ['email' => 'invalid'],
    ['email' => 'email']
);

$validator->validate();
$errors = $validator->errors();

// Message: "The email must be a valid email address."
```

### Messages personnalisés par attribut

```php
$validator = Validator::make(
    ['email' => ''],
    ['email' => 'required'],
    [
        'email' => 'Veuillez fournir votre adresse email.',
    ]
);
```

### Messages personnalisés par règle

```php
$validator = Validator::make(
    ['email' => 'invalid', 'age' => 15],
    ['email' => 'required|email', 'age' => 'min:18'],
    [
        'email.email' => 'Le format de l\'email est invalide.',
        'age.min' => 'Vous devez avoir au moins 18 ans.',
    ]
);
```

### Placeholders disponibles

Les messages supportent ces placeholders:
- `:attribute` - Nom du champ
- `:value` - Valeur fournie
- `:min` / `:max` - Paramètres des règles Min/Max

Exemple:
```php
// Message template: "The :attribute must be at least :min."
// Devient: "The age must be at least 18."
```

---

## Règles personnalisées

### Avec Closures

```php
$rules = [
    'password' => [
        'required',
        fn($attribute, $value) => strlen($value) >= 8,
    ],
    'password_confirmation' => [
        fn($attribute, $value, $data) => $value === $data['password'],
    ],
];

$validator = Validator::make($data, $rules);
```

**Note**: Les Closures reçoivent:
1. `$attribute` - Nom du champ
2. `$value` - Valeur à valider
3. `$data` - Toutes les données (optionnel)

### Avec classes personnalisées

```php
use Elarion\Validation\Rule;

class Uppercase implements Rule
{
    protected array $data = [];

    public function passes(string $attribute, mixed $value): bool
    {
        return is_string($value) && $value === strtoupper($value);
    }

    public function message(): string
    {
        return 'The :attribute must be uppercase.';
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }
}

// Usage
$rules = ['code' => [new Uppercase()]];
```

### Règle réutilisable avec paramètres

```php
class Between implements Rule
{
    protected array $data = [];

    public function __construct(
        protected int $min,
        protected int $max
    ) {}

    public function passes(string $attribute, mixed $value): bool
    {
        return is_numeric($value)
            && $value >= $this->min
            && $value <= $this->max;
    }

    public function message(): string
    {
        return "The :attribute must be between {$this->min} and {$this->max}.";
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }
}

// Usage
$rules = ['age' => [new Between(18, 65)]];
```

---

## Validation de tableaux imbriqués

### Dot notation simple

```php
$data = [
    'user' => [
        'name' => 'John',
        'email' => 'john@example.com',
    ],
];

$rules = [
    'user.name' => 'required|string',
    'user.email' => 'required|email',
];

$validator = Validator::make($data, $rules);
```

### Wildcards (*)

```php
$data = [
    'items' => [
        ['name' => 'Product 1', 'price' => 100],
        ['name' => 'Product 2', 'price' => 200],
    ],
];

$rules = [
    'items.*.name' => 'required|string',
    'items.*.price' => 'required|numeric|min:0',
];

$validator = Validator::make($data, $rules);
```

### Nested arrays complexes

```php
$data = [
    'company' => [
        'name' => 'Acme Corp',
        'employees' => [
            [
                'name' => 'John',
                'contact' => [
                    'email' => 'john@acme.com',
                    'phone' => '555-1234',
                ],
            ],
        ],
    ],
];

$rules = [
    'company.name' => 'required|string',
    'company.employees.*.name' => 'required|string',
    'company.employees.*.contact.email' => 'required|email',
    'company.employees.*.contact.phone' => 'required',
];
```

---

## Méthodes du Validator

### validate()

Exécute la validation et retourne le résultat.

```php
$validator = Validator::make($data, $rules);

if ($validator->validate()) {
    // Validation réussie
}
```

**Retourne**: `bool` - `true` si valide, `false` sinon

---

### fails()

Alias inverse de `validate()`.

```php
if ($validator->fails()) {
    // Validation échouée
    $errors = $validator->errors();
}
```

**Retourne**: `bool` - `true` si invalide, `false` sinon

---

### errors()

Retourne tous les messages d'erreur groupés par attribut.

```php
$errors = $validator->errors();

// Structure:
[
    'email' => [
        'The email field is required.',
        'The email must be a valid email address.',
    ],
    'age' => [
        'The age must be at least 18.',
    ],
]
```

**Retourne**: `array<string, array<string>>`

---

### validated()

Retourne uniquement les champs validés (ceux qui ont des règles).

```php
$data = [
    'name' => 'John',
    'email' => 'john@example.com',
    'extra' => 'ignored',
];

$rules = [
    'name' => 'required',
    'email' => 'email',
];

$validator = Validator::make($data, $rules);
$validator->validate();

$validated = $validator->validated();
// ['name' => 'John', 'email' => 'john@example.com']
// 'extra' est exclu car pas dans les règles
```

**Retourne**: `array<string, mixed>`

---

### make() (static)

Factory method pour créer une instance.

```php
$validator = Validator::make($data, $rules, $messages);

// Équivalent à:
$validator = new Validator($data, $rules, $messages);
```

---

## Exemples complets

### Formulaire d'inscription

```php
use Elarion\Validation\Validator;

$data = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => 'secret123',
    'password_confirmation' => 'secret123',
    'age' => 25,
    'terms' => true,
];

$rules = [
    'name' => 'required|string|min:3|max:100',
    'email' => 'required|email',
    'password' => [
        'required',
        'string',
        'min:8',
    ],
    'password_confirmation' => [
        fn($attr, $value, $data) => $value === $data['password'],
    ],
    'age' => 'required|integer|min:18',
    'terms' => 'required|boolean',
];

$messages = [
    'name.required' => 'Le nom est obligatoire.',
    'email.email' => 'L\'email doit être valide.',
    'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
    'password_confirmation' => 'Les mots de passe ne correspondent pas.',
    'age.min' => 'Vous devez avoir au moins 18 ans.',
];

$validator = Validator::make($data, $rules, $messages);

if ($validator->fails()) {
    foreach ($validator->errors() as $field => $errors) {
        foreach ($errors as $error) {
            echo "$field: $error\n";
        }
    }
    exit;
}

// Utiliser uniquement les données validées
$validated = $validator->validated();
// Créer l'utilisateur avec $validated...
```

### Validation d'API JSON

```php
// Request body
$data = json_decode($request->getBody()->getContents(), true);

$rules = [
    'title' => 'required|string|max:200',
    'content' => 'required|string',
    'published' => 'boolean',
    'tags' => 'array',
    'tags.*' => 'string|max:50',
    'author.name' => 'required|string',
    'author.email' => 'required|email',
];

$validator = Validator::make($data, $rules);

if ($validator->fails()) {
    return Response::json([
        'error' => 'Validation failed',
        'errors' => $validator->errors(),
    ], 422);
}

$validated = $validator->validated();
// Process validated data...
```

### Validation de configuration

```php
class ConfigValidator
{
    public static function validate(array $config): array
    {
        $rules = [
            'database.host' => 'required|string',
            'database.port' => 'required|integer|min:1|max:65535',
            'database.name' => 'required|string',
            'database.credentials.username' => 'required|string',
            'database.credentials.password' => 'required|string',
            'cache.enabled' => 'boolean',
            'cache.ttl' => 'integer|min:0',
        ];

        $validator = Validator::make($config, $rules);

        if ($validator->fails()) {
            throw new InvalidConfigException(
                'Invalid configuration: ' . json_encode($validator->errors())
            );
        }

        return $validator->validated();
    }
}
```

---

## Tests

### Test d'une règle custom

```php
use PHPUnit\Framework\TestCase;

class CustomRuleTest extends TestCase
{
    public function test_uppercase_rule_passes(): void
    {
        $rule = new Uppercase();

        $this->assertTrue($rule->passes('code', 'HELLO'));
        $this->assertFalse($rule->passes('code', 'hello'));
    }

    public function test_uppercase_rule_message(): void
    {
        $rule = new Uppercase();

        $this->assertSame(
            'The :attribute must be uppercase.',
            $rule->message()
        );
    }
}
```

### Test du Validator

```php
public function test_validator_with_multiple_rules(): void
{
    $validator = Validator::make(
        ['email' => 'john@example.com'],
        ['email' => 'required|email']
    );

    $this->assertTrue($validator->validate());
    $this->assertEmpty($validator->errors());
}

public function test_validator_fails_with_invalid_data(): void
{
    $validator = Validator::make(
        ['email' => 'invalid'],
        ['email' => 'email']
    );

    $this->assertTrue($validator->fails());
    $this->assertArrayHasKey('email', $validator->errors());
}
```

---

## Bonnes pratiques

### ✅ DO

```php
// Utiliser la factory method
$validator = Validator::make($data, $rules);

// Valider uniquement les données nécessaires
$rules = [
    'email' => 'required|email',
    'name' => 'required|string',
];
$validated = $validator->validated(); // Exclut les champs non validés

// Grouper les règles liées
$rules = [
    'password' => ['required', 'string', 'min:8'],
    'password_confirmation' => [
        fn($attr, $value, $data) => $value === $data['password'],
    ],
];

// Créer des règles réutilisables pour la logique complexe
class StrongPassword implements Rule { /* ... */ }
$rules = ['password' => [new StrongPassword()]];

// Utiliser messages personnalisés pour l'UX
$messages = [
    'email.required' => 'Veuillez fournir votre email.',
    'email.email' => 'L\'email n\'est pas valide.',
];
```

### ❌ DON'T

```php
// Ne pas valider des données sensibles sans filtrage
$rules = ['password' => 'required']; // ❌ Trop simple
// Mieux:
$rules = ['password' => ['required', 'min:8', new StrongPassword()]];

// Ne pas ignorer les erreurs de validation
if ($validator->fails()) {
    // ❌ Ne rien faire
}
// Mieux:
if ($validator->fails()) {
    throw new ValidationException($validator->errors());
}

// Ne pas valider les mêmes données plusieurs fois
$validator1 = Validator::make($data, $rules1);
$validator2 = Validator::make($data, $rules2); // ❌ Dupliqué
// Mieux: Combiner les règles
$rules = array_merge($rules1, $rules2);

// Ne pas utiliser validated() sans appeler validate() d'abord
$validated = $validator->validated(); // ❌ Peut retourner données invalides
// Mieux:
if ($validator->validate()) {
    $validated = $validator->validated();
}
```

---

## Performance

### Optimisations built-in

- **Lazy validation**: Arrêt dès la première erreur par règle
- **Rule instances réutilisables**: Les instances sont créées une seule fois
- **Dot notation efficace**: Parsing minimal des clés

### Conseils de performance

```php
// 1. Réutiliser les Rule instances pour plusieurs validations
$emailRule = new Email();
$requiredRule = new Required();

foreach ($items as $item) {
    $validator = Validator::make($item, [
        'email' => [$requiredRule, $emailRule],
    ]);
}

// 2. Valider uniquement ce qui est nécessaire
// ❌ Valider tous les champs
$rules = ['field1' => 'required', 'field2' => 'required', /* ... */];

// ✅ Valider uniquement les champs modifiés
$modified = ['email'];
$rules = ['email' => 'required|email'];

// 3. Éviter les Closures complexes
// ❌
$rules = ['field' => [fn($a, $v) => complexCalculation($v)]];

// ✅ Créer une Rule class
$rules = ['field' => [new ComplexRule()]];
```

### Benchmarks

Tests sur 10,000 validations:
- **String rules**: ~5ms
- **Rule instances**: ~4ms (réutilisation)
- **Closures**: ~6ms
- **Nested arrays (3 niveaux)**: ~12ms

---

## API Reference

### Validator

```php
class Validator
{
    public function __construct(
        array $data,
        array $rules,
        array $messages = []
    );

    public static function make(
        array $data,
        array $rules,
        array $messages = []
    ): static;

    public function validate(): bool;
    public function fails(): bool;
    public function errors(): array;
    public function validated(): array;
}
```

### Rule Interface

```php
interface Rule
{
    public function passes(string $attribute, mixed $value): bool;
    public function message(): string;
    public function setData(array $data): void;
}
```

### AbstractRule

```php
abstract class AbstractRule implements Rule
{
    protected array $data = [];
    protected string $attribute = '';
    protected mixed $value = null;

    abstract protected function getMessageTemplate(): string;

    public function message(): string;
    public function setData(array $data): void;

    protected function replacePlaceholders(string $message): string;
    protected function getReplacements(): array;
    protected function formatValue(mixed $value): string;
}
```

### Built-in Rules

| Classe | Alias | Paramètres | Description |
|--------|-------|------------|-------------|
| `Required` | `required` | - | Champ obligatoire |
| `Email` | `email` | - | Email valide |
| `Min` | `min:X` | int\|float | Valeur/longueur/count min |
| `Max` | `max:X` | int\|float | Valeur/longueur/count max |
| `StringType` | `string` | - | Doit être string |
| `IntegerType` | `integer` | - | Doit être int |
| `Numeric` | `numeric` | - | Doit être numérique |
| `BooleanType` | `boolean` | - | Doit être boolean |
| `ArrayType` | `array` | - | Doit être array |

---

## Roadmap

### Version actuelle: 1.0

- ✅ Rule interface et AbstractRule
- ✅ 9 règles built-in
- ✅ String-based rules avec pipe separator
- ✅ Rule instances
- ✅ Closures personnalisées
- ✅ Dot notation pour nested arrays
- ✅ Wildcard (*) pour validation d'arrays
- ✅ Messages d'erreur personnalisables
- ✅ validated() method

### Prochaines versions

#### v1.1 - Extensions
- [ ] Règles additionnelles:
  - `url` - URL valide
  - `uuid` - UUID valide
  - `date` / `date_format` - Validation de dates
  - `in:foo,bar` - Valeur dans liste
  - `regex:pattern` - Pattern personnalisé
  - `confirmed` - Champ de confirmation
  - `unique` - Unicité en base (via callback)
  - `exists` - Existence en base (via callback)
- [ ] Support `sometimes` - Règles conditionnelles
- [ ] Support `bail` - Arrêt à la première erreur

#### v1.2 - I18n
- [ ] Traduction des messages (fichiers de langue)
- [ ] Locale detection
- [ ] Custom formatters pour nombres/dates selon locale

#### v1.3 - Advanced
- [ ] Validation de fichiers (size, mime type, dimensions)
- [ ] Rule groups réutilisables
- [ ] Validation asynchrone (pour DB queries, API calls)
- [ ] Validation conditionnelle avancée (`when`, `unless`)

---

## Voir aussi

- [API Resources](API-Resources.md) - Transformers pour réponses JSON
- [Database](Database.md) - Connexions et Query Builder
- [ORM Model](ORM-Model.md) - Active Record pattern

---

*Documentation générée pour ElarionStack v0.1.0-dev*
