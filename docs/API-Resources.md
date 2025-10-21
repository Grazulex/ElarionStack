# API Resources - Documentation Complète

## Vue d'ensemble

Les API Resources d'ElarionStack implémentent le pattern Transformer/Presenter pour convertir vos models et données en réponses API structurées. Elles offrent un contrôle total sur les données exposées et permettent de séparer la logique de transformation des controllers.

### Caractéristiques principales

- **Transformation flexible** : Contrôle complet sur la structure des réponses
- **Attributs conditionnels** : Inclusion basée sur conditions (permissions, contexte)
- **Resources imbriquées** : Support des relations et nested resources
- **Collections** : Gestion des listes avec pagination et métadonnées
- **Réutilisable** : Même resource utilisable dans plusieurs endpoints
- **Testable** : Tests indépendants des controllers
- **Type-safe** : PSR-7 ServerRequestInterface pour le contexte

## Architecture

### Pattern Transformer/Presenter

```
Controller → Resource → toArray() → JSON Response
    ↓           ↓
  Model     Transform + Filter
```

### Classes principales

```
Resource (abstract)
├── JsonResource (concrete)
├── Custom Resources (extend Resource)
└── ResourceCollection (for arrays)

MissingValue (marker for conditional exclusion)
```

## Installation et utilisation basique

### Création d'une Resource

```php
use Elarion\Http\Resources\Resource;
use Psr\Http\Message\ServerRequestInterface;

class UserResource extends Resource
{
    public function toArray(ServerRequestInterface $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at,
        ];
    }
}
```

### Utilisation dans un controller

```php
// Single resource
public function show(ServerRequestInterface $request, int $id)
{
    $user = User::find($id);

    return UserResource::make($user)->toResponse($request);
}

// Collection
public function index(ServerRequestInterface $request)
{
    $users = User::all();

    return UserResource::collection($users)->toResponse($request);
}
```

### Réponse JSON générée

```json
// Single resource
{
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "created_at": "2025-10-21 10:00:00"
}

// Collection
{
    "data": [
        {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "created_at": "2025-10-21 10:00:00"
        },
        {
            "id": 2,
            "name": "Jane Smith",
            "email": "jane@example.com",
            "created_at": "2025-10-21 11:00:00"
        }
    ]
}
```

## JsonResource - Usage générique

### Sans créer de classe personnalisée

```php
use Elarion\Http\Resources\JsonResource;

// Array
$data = ['name' => 'John', 'email' => 'john@example.com'];
return JsonResource::make($data)->toResponse($request);

// Object
$user = User::find(1);
return JsonResource::make($user)->toResponse($request);

// Collection
$users = User::all();
return JsonResource::collection($users)->toResponse($request);
```

### Transformation automatique

JsonResource transforme automatiquement:
- **Arrays** : Retourne tel quel
- **Models avec toArray()** : Appelle la méthode toArray()
- **Objects** : Convertit en array via get_object_vars()
- **Scalaires** : Wrap dans `['value' => $scalar]`

## Attributs conditionnels

### when() - Inclusion conditionnelle

```php
class UserResource extends Resource
{
    public function toArray(ServerRequestInterface $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,

            // Inclus seulement si utilisateur est admin
            'is_admin' => $this->when(
                $request->user()?->isAdmin(),
                $this->is_admin
            ),

            // Inclus seulement si c'est le propre profil
            'phone' => $this->when(
                $request->user()?->id === $this->id,
                $this->phone
            ),
        ];
    }
}
```

### Avec closure pour calcul lazy

```php
'expensive_data' => $this->when(
    $request->user()?->isAdmin(),
    fn() => $this->calculateExpensiveData()
),
```

### Valeur par défaut

```php
// Si condition false, retourne 'public'
'visibility' => $this->when(
    $this->is_private,
    'private',
    'public'
),
```

### mergeWhen() - Fusion conditionnelle

```php
public function toArray(ServerRequestInterface $request): array
{
    return array_merge([
        'id' => $this->id,
        'name' => $this->name,
    ], $this->mergeWhen($request->user()?->isAdmin(), [
        'internal_id' => $this->internal_id,
        'cost' => $this->cost,
        'margin' => $this->margin,
    ]));
}
```

## Resources imbriquées

### Simple nesting

```php
class PostResource extends Resource
{
    public function toArray(ServerRequestInterface $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'author' => UserResource::make($this->author)->toArray($request),
        ];
    }
}
```

Résultat:
```json
{
    "id": 1,
    "title": "My Post",
    "content": "Post content...",
    "author": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
    }
}
```

### Collection imbriquée

```php
class PostResource extends Resource
{
    public function toArray(ServerRequestInterface $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => UserResource::make($this->author)->toArray($request),
            'comments' => CommentResource::collection($this->comments)->toArray($request),
        ];
    }
}
```

Résultat:
```json
{
    "id": 1,
    "title": "My Post",
    "author": {...},
    "comments": {
        "data": [
            {"id": 1, "text": "Great post!"},
            {"id": 2, "text": "Thanks for sharing"}
        ]
    }
}
```

## Collections

### Collection basique

```php
$users = User::all();

return UserResource::collection($users)->toResponse($request);
```

Résultat:
```json
{
    "data": [
        {"id": 1, "name": "John"},
        {"id": 2, "name": "Jane"}
    ]
}
```

### Avec métadonnées

```php
return UserResource::collection($users)
    ->additional([
        'meta' => [
            'version' => '1.0',
            'generated_at' => date('Y-m-d H:i:s'),
        ]
    ])
    ->toResponse($request);
```

Résultat:
```json
{
    "data": [...],
    "meta": {
        "version": "1.0",
        "generated_at": "2025-10-21 10:00:00"
    }
}
```

### Avec pagination

```php
$total = 100;
$perPage = 10;
$currentPage = 1;

return UserResource::collection($users)
    ->withPagination($total, $perPage, $currentPage)
    ->toResponse($request);
```

Résultat:
```json
{
    "data": [...],
    "meta": {
        "total": 100,
        "per_page": 10,
        "current_page": 1,
        "last_page": 10,
        "from": 1,
        "to": 10
    }
}
```

### Métadonnées simples

```php
return UserResource::collection($users)
    ->withMeta(['count' => count($users)])
    ->toResponse($request);
```

## Métadonnées supplémentaires

### Méthode with()

Ajouter des données au même niveau que les attributs de la resource:

```php
class UserResource extends Resource
{
    public function toArray(ServerRequestInterface $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }

    protected function with(ServerRequestInterface $request): array
    {
        return [
            'meta' => [
                'object_type' => 'user',
            ],
        ];
    }
}
```

Résultat:
```json
{
    "id": 1,
    "name": "John",
    "meta": {
        "object_type": "user"
    }
}
```

### Méthode additional()

Ajouter des données au niveau racine (wraps resource dans 'data'):

```php
return UserResource::make($user)
    ->additional([
        'meta' => ['version' => '2.0'],
        'links' => ['self' => '/users/1'],
    ])
    ->toResponse($request);
```

Résultat:
```json
{
    "data": {
        "id": 1,
        "name": "John"
    },
    "meta": {
        "version": "2.0"
    },
    "links": {
        "self": "/users/1"
    }
}
```

## Accès aux propriétés

### Magic property access

```php
class UserResource extends Resource
{
    public function toArray(ServerRequestInterface $request): array
    {
        // Accès direct aux propriétés de l'underlying resource
        return [
            'id' => $this->id,                    // $user->id
            'name' => $this->name,                // $user->name
            'email' => $this->email,              // $user->email
            'upper_name' => $this->getUpperName(), // $user->getUpperName()
        ];
    }
}
```

### Support array et object

```php
// Avec array
$resource = new UserResource(['name' => 'John', 'email' => 'john@example.com']);
echo $resource->name; // 'John'

// Avec object
$user = User::find(1);
$resource = new UserResource($user);
echo $resource->name; // Accès à $user->name

// Avec Model
$user = User::find(1);
$resource = new UserResource($user);
echo $resource->toJson(); // Utilise $user->toJson() si disponible
```

### isset() support

```php
if (isset($resource->name)) {
    echo $resource->name;
}
```

### Appel de méthodes

```php
// Si underlying resource a une méthode, on peut l'appeler
$fullName = $resource->getFullName(); // Appelle $user->getFullName()
```

## Réponses HTTP

### toResponse() - Conversion en HTTP Response

```php
// Status 200 par défaut
return UserResource::make($user)->toResponse($request);

// Status personnalisé
return UserResource::make($user)->toResponse($request, 201);
```

### resolve() - Obtenir l'array final

```php
// Si vous voulez juste l'array sans Response
$data = UserResource::make($user)->resolve($request);
print_r($data);
```

### toArray() vs resolve()

```php
// toArray() - Raw transformation (peut contenir MissingValue)
$raw = $resource->toArray($request);

// resolve() - Final array (MissingValue filtrés, with() et additional() ajoutés)
$final = $resource->resolve($request);
```

## Patterns avancés

### Resource Factory

```php
class UserResource extends Resource
{
    public static function makeFromId(int $id): static
    {
        $user = User::find($id);
        return static::make($user);
    }
}

// Usage
return UserResource::makeFromId(1)->toResponse($request);
```

### Transformation différente selon contexte

```php
class UserResource extends Resource
{
    public function toArray(ServerRequestInterface $request): array
    {
        $user = $request->user();

        $data = [
            'id' => $this->id,
            'name' => $this->name,
        ];

        // Détails complets si admin ou soi-même
        if ($user?->isAdmin() || $user?->id === $this->id) {
            $data['email'] = $this->email;
            $data['phone'] = $this->phone;
            $data['address'] = $this->address;
        }

        return $data;
    }
}
```

### Resource avec relations optionnelles

```php
class PostResource extends Resource
{
    public function toArray(ServerRequestInterface $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,

            // Inclus author seulement si chargé
            'author' => $this->when(
                isset($this->author),
                fn() => UserResource::make($this->author)->toArray($request)
            ),
        ];
    }
}
```

### Wrapping personnalisé

```php
class CustomCollection extends ResourceCollection
{
    public function toArray(ServerRequestInterface $request): array
    {
        $data = parent::toArray($request);

        // Wrap dans 'items' au lieu de 'data'
        return [
            'items' => $data['data'],
            'count' => count($data['data']),
        ];
    }
}

// Usage dans Resource
public static function collection(iterable $resources): ResourceCollection
{
    return new CustomCollection($resources, static::class);
}
```

## Exemples complets

### API REST complète

```php
class UserController
{
    public function index(ServerRequestInterface $request)
    {
        $users = User::query()
            ->where('status', 'active')
            ->limit(20)
            ->get();

        return UserResource::collection($users)
            ->withPagination(100, 20, 1)
            ->withMeta(['cached' => false])
            ->toResponse($request);
    }

    public function show(ServerRequestInterface $request, int $id)
    {
        $user = User::find($id);

        if (!$user) {
            return Response::json(['error' => 'Not found'], 404);
        }

        return UserResource::make($user)
            ->additional(['links' => [
                'self' => "/users/{$id}",
                'posts' => "/users/{$id}/posts",
            ]])
            ->toResponse($request);
    }

    public function store(ServerRequestInterface $request)
    {
        $data = json_decode($request->getBody()->getContents(), true);

        $user = new User($data);
        $user->save();

        return UserResource::make($user)->toResponse($request, 201);
    }

    public function update(ServerRequestInterface $request, int $id)
    {
        $user = User::find($id);
        $data = json_decode($request->getBody()->getContents(), true);

        $user->fill($data);
        $user->save();

        return UserResource::make($user)->toResponse($request);
    }
}
```

### Resource avec permissions

```php
class UserResource extends Resource
{
    public function toArray(ServerRequestInterface $request): array
    {
        $currentUser = $request->user();
        $isOwn = $currentUser?->id === $this->id;
        $isAdmin = $currentUser?->isAdmin();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar' => $this->avatar_url,

            // Email visible par owner ou admin
            'email' => $this->when($isOwn || $isAdmin, $this->email),

            // Données sensibles seulement pour owner
            'phone' => $this->when($isOwn, $this->phone),
            'address' => $this->when($isOwn, $this->address),

            // Données admin
            'internal_notes' => $this->when($isAdmin, $this->internal_notes),
            'created_ip' => $this->when($isAdmin, $this->created_ip),

            // Relations conditionnelles
            'posts' => $this->when(
                $isOwn,
                fn() => PostResource::collection($this->posts)->toArray($request)
            ),
        ];
    }
}
```

### Resource avec calculs

```php
class ProductResource extends Resource
{
    public function toArray(ServerRequestInterface $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'stock' => $this->stock,

            // Calculs
            'price_with_tax' => round($this->price * 1.20, 2),
            'in_stock' => $this->stock > 0,
            'discount_percentage' => $this->calculateDiscount(),

            // Relations
            'category' => CategoryResource::make($this->category)->toArray($request),
            'images' => $this->images,

            // Métadonnées
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    protected function with(ServerRequestInterface $request): array
    {
        return [
            'meta' => [
                'currency' => 'EUR',
                'tax_rate' => 0.20,
            ],
        ];
    }
}
```

## Tests

### Test d'une Resource

```php
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class UserResourceTest extends TestCase
{
    private ServerRequestInterface $request;

    protected function setUp(): void
    {
        $this->request = $this->createMock(ServerRequestInterface::class);
    }

    public function test_resource_transforms_user(): void
    {
        $user = new User();
        $user->id = 1;
        $user->name = 'John Doe';
        $user->email = 'john@example.com';

        $resource = new UserResource($user);
        $result = $resource->toArray($this->request);

        $this->assertSame(1, $result['id']);
        $this->assertSame('John Doe', $result['name']);
        $this->assertSame('john@example.com', $result['email']);
    }

    public function test_conditional_attributes(): void
    {
        $user = new User();
        $user->is_admin = true;

        $resource = new UserResource($user);
        $result = $resource->resolve($this->request);

        // Vérifie que l'attribut conditionnel est présent/absent
        $this->assertArrayHasKey('admin_panel_url', $result);
    }

    public function test_collection(): void
    {
        $users = [
            new User(['id' => 1, 'name' => 'John']),
            new User(['id' => 2, 'name' => 'Jane']),
        ];

        $collection = UserResource::collection($users);
        $result = $collection->toArray($this->request);

        $this->assertCount(2, $result['data']);
        $this->assertSame('John', $result['data'][0]['name']);
    }
}
```

## Bonnes pratiques

### ✅ DO

```php
// Créer une resource par model principal
class UserResource extends Resource { }
class PostResource extends Resource { }

// Utiliser when() pour attributs conditionnels
'secret' => $this->when($condition, $value)

// Utiliser nested resources pour relations
'author' => UserResource::make($this->author)->toArray($request)

// Tester les resources indépendamment
public function test_user_resource() { }

// Utiliser collections pour listes
return UserResource::collection($users)->toResponse($request);

// Ajouter métadonnées utiles
->withPagination($total, $perPage, $page)
```

### ❌ DON'T

```php
// ❌ Ne pas inclure logique métier dans resources
public function toArray($request): array {
    $this->resource->updateLastAccess(); // NON!
    return [...];
}

// ❌ Ne pas faire de queries dans toArray()
public function toArray($request): array {
    'posts' => Post::where('user_id', $this->id)->get() // NON!
}

// ❌ Ne pas exposer données sensibles sans conditions
public function toArray($request): array {
    return [
        'password' => $this->password, // JAMAIS!
        'token' => $this->api_token,   // Dangereux!
    ];
}

// ❌ Ne pas créer trop de resources pour variations mineures
class UserListResource { }
class UserDetailResource { }
class UserAdminResource { }
// Préférer: une UserResource avec conditions
```

## Performance

### Éviter N+1 dans nested resources

```php
// ❌ Problème N+1
$users = User::all(); // 1 query
return UserResource::collection($users); // +N queries si relations dans toArray()

// ✅ Eager loading
$users = User::query()
    ->with(['posts', 'comments']) // 3 queries total
    ->get();
return UserResource::collection($users);
```

### Lazy loading conditionnel

```php
// Charger relation seulement si nécessaire
'posts' => $this->when(
    $includePost && isset($this->posts),
    fn() => PostResource::collection($this->posts)->toArray($request)
),
```

## API Reference

### Resource

| Méthode | Signature | Description |
|---------|-----------|-------------|
| `make()` | `make(mixed $resource): static` | Factory statique |
| `collection()` | `collection(iterable $resources): ResourceCollection` | Créer collection |
| `toArray()` | `toArray(ServerRequestInterface $request): array` | Transformation (abstract) |
| `toResponse()` | `toResponse(ServerRequestInterface $request, int $status = 200): Response` | HTTP Response |
| `resolve()` | `resolve(ServerRequestInterface $request): array` | Array final |
| `additional()` | `additional(array $data): self` | Données top-level |
| `when()` | `when(bool $condition, mixed $value, mixed $default = null): mixed` | Inclusion conditionnelle |
| `mergeWhen()` | `mergeWhen(bool $condition, array $data): array` | Fusion conditionnelle |
| `with()` | `with(ServerRequestInterface $request): array` | Données supplémentaires |

### ResourceCollection

| Méthode | Signature | Description |
|---------|-----------|-------------|
| `toArray()` | `toArray(ServerRequestInterface $request): array` | Transformer collection |
| `toResponse()` | `toResponse(ServerRequestInterface $request, int $status = 200): Response` | HTTP Response |
| `resolve()` | `resolve(ServerRequestInterface $request): array` | Array final |
| `additional()` | `additional(array $data): self` | Données top-level |
| `withPagination()` | `withPagination(int $total, int $perPage, int $currentPage, ?int $lastPage): self` | Pagination meta |
| `withMeta()` | `withMeta(array $meta): self` | Métadonnées simples |

## Roadmap

Améliorations futures possibles:
- [ ] Resource caching
- [ ] Conditional includes (include query parameter)
- [ ] Sparse fieldsets (fields query parameter)
- [ ] Automatic HATEOAS links
- [ ] Resource versioning
- [ ] OpenAPI schema generation

---

**Version:** 1.0.0
**Dernière mise à jour:** 2025-10-21
