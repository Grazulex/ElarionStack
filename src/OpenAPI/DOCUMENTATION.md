# Documentation Complète - OpenAPI/Swagger Generator

Guide complet avec exemples pratiques pour la génération automatique de documentation OpenAPI 3.1 dans ElarionStack.

## Table des Matières

1. [Installation et Configuration](#installation-et-configuration)
2. [Guide de Démarrage Rapide](#guide-de-démarrage-rapide)
3. [Annotations PHP Attributes](#annotations-php-attributes)
4. [Exemples Complets de Controllers](#exemples-complets-de-controllers)
5. [Validation et Schémas de Requête](#validation-et-schémas-de-requête)
6. [API Resources et Schémas de Réponse](#api-resources-et-schémas-de-réponse)
7. [Support JSON:API](#support-jsonapi)
8. [Configuration Avancée](#configuration-avancée)
9. [Génération Programmatique](#génération-programmatique)
10. [Bonnes Pratiques](#bonnes-pratiques)

---

## Installation et Configuration

### 1. Enregistrer le Service Provider

```php
// config/app.php ou bootstrap/providers.php
return [
    'providers' => [
        // ... autres providers
        \Elarion\OpenAPI\OpenAPIServiceProvider::class,
    ],
];
```

### 2. Configuration de Base

Créez ou modifiez `config/openapi.php`:

```php
<?php

return [
    // Informations de base sur votre API
    'title' => env('API_TITLE', 'Mon Application API'),
    'version' => env('API_VERSION', '1.0.0'),
    'description' => env('API_DESCRIPTION', 'Documentation API de mon application'),

    // Serveurs où l'API est hébergée
    'servers' => [
        [
            'url' => env('API_URL', 'http://localhost:8000'),
            'description' => 'Serveur de développement',
        ],
        [
            'url' => 'https://api.production.com',
            'description' => 'Serveur de production',
        ],
    ],

    // Routes de documentation (personnalisables)
    'routes' => [
        'ui' => '/api/documentation',      // Swagger UI
        'redoc' => '/api/redoc',           // ReDoc UI
        'json' => '/api/documentation.json', // Export JSON
        'yaml' => '/api/documentation.yaml', // Export YAML
    ],
];
```

### 3. Variables d'Environnement

Ajoutez à votre `.env`:

```env
API_TITLE="Mon Application API"
API_VERSION="1.0.0"
API_DESCRIPTION="Documentation complète de l'API"
API_URL="http://localhost:8000"
```

---

## Guide de Démarrage Rapide

### Exemple Minimal

**1. Créez un contrôleur simple:**

```php
<?php

namespace App\Http\Controllers;

use Elarion\Http\Message\Response;
use Elarion\OpenAPI\Attributes\Get;
use Psr\Http\Message\ServerRequestInterface;

class HelloController
{
    #[Get(
        path: '/hello',
        summary: 'Dire bonjour',
        tags: ['Greetings']
    )]
    public function hello(ServerRequestInterface $request): Response
    {
        return Response::json(['message' => 'Hello World!']);
    }
}
```

**2. Enregistrez la route:**

```php
// routes/api.php
$router->get('/hello', [HelloController::class, 'hello']);
```

**3. Accédez à la documentation:**

- Swagger UI: http://localhost:8000/api/documentation
- ReDoc: http://localhost:8000/api/redoc
- JSON: http://localhost:8000/api/documentation.json

---

## Annotations PHP Attributes

### Attributs de Méthodes HTTP

#### Get - Récupérer des ressources

```php
use Elarion\OpenAPI\Attributes\Get;

#[Get(
    path: '/users',
    summary: 'Liste tous les utilisateurs',
    description: 'Retourne une liste paginée de tous les utilisateurs du système',
    tags: ['Users'],
    operationId: 'listUsers',
    deprecated: false
)]
public function index(): Response
{
    // ...
}
```

#### Post - Créer une ressource

```php
use Elarion\OpenAPI\Attributes\Post;

#[Post(
    path: '/users',
    summary: 'Créer un utilisateur',
    description: 'Crée un nouvel utilisateur avec les données fournies',
    tags: ['Users'],
    operationId: 'createUser'
)]
public function store(ServerRequestInterface $request): Response
{
    // ...
}
```

#### Put - Remplacer une ressource

```php
use Elarion\OpenAPI\Attributes\Put;

#[Put(
    path: '/users/{id}',
    summary: 'Remplacer un utilisateur',
    description: 'Remplace complètement les données d\'un utilisateur',
    tags: ['Users']
)]
public function replace(int $id, ServerRequestInterface $request): Response
{
    // ...
}
```

#### Patch - Modifier partiellement

```php
use Elarion\OpenAPI\Attributes\Patch;

#[Patch(
    path: '/users/{id}',
    summary: 'Modifier un utilisateur',
    description: 'Modifie partiellement les données d\'un utilisateur',
    tags: ['Users']
)]
public function update(int $id, ServerRequestInterface $request): Response
{
    // ...
}
```

#### Delete - Supprimer une ressource

```php
use Elarion\OpenAPI\Attributes\Delete;

#[Delete(
    path: '/users/{id}',
    summary: 'Supprimer un utilisateur',
    description: 'Supprime définitivement un utilisateur du système',
    tags: ['Users']
)]
public function destroy(int $id): Response
{
    // ...
}
```

### Attributs de Paramètres

#### PathParameter - Paramètres d'URL

```php
use Elarion\OpenAPI\Attributes\{Get, PathParameter};

#[Get(path: '/users/{id}', summary: 'Obtenir un utilisateur')]
#[PathParameter(
    name: 'id',
    type: 'integer',
    description: 'Identifiant unique de l\'utilisateur',
    format: 'int64'
)]
public function show(int $id): Response
{
    // ...
}
```

**Paramètres multiples:**

```php
#[Get(path: '/posts/{postId}/comments/{commentId}')]
#[PathParameter('postId', 'integer', 'ID du post')]
#[PathParameter('commentId', 'integer', 'ID du commentaire')]
public function showComment(int $postId, int $commentId): Response
{
    // ...
}
```

#### QueryParameter - Paramètres de requête

```php
use Elarion\OpenAPI\Attributes\{Get, QueryParameter};

#[Get(path: '/users', summary: 'Liste des utilisateurs')]
#[QueryParameter(
    name: 'page',
    type: 'integer',
    description: 'Numéro de page (défaut: 1)',
    required: false
)]
#[QueryParameter(
    name: 'limit',
    type: 'integer',
    description: 'Nombre d\'éléments par page (défaut: 20, max: 100)',
    required: false
)]
#[QueryParameter(
    name: 'search',
    type: 'string',
    description: 'Recherche par nom ou email',
    required: false
)]
#[QueryParameter(
    name: 'status',
    type: 'string',
    description: 'Filtrer par statut (active, inactive)',
    required: false
)]
public function index(ServerRequestInterface $request): Response
{
    $params = $request->getQueryParams();
    $page = (int) ($params['page'] ?? 1);
    $limit = min((int) ($params['limit'] ?? 20), 100);
    $search = $params['search'] ?? null;
    $status = $params['status'] ?? null;

    // Logique de pagination et filtrage...

    return Response::json([
        'data' => $users,
        'meta' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
        ],
    ]);
}
```

### Attributs de Requête et Réponse

#### RequestBody - Corps de requête

```php
use Elarion\OpenAPI\Attributes\{Post, RequestBody};

#[Post(path: '/users', summary: 'Créer un utilisateur')]
#[RequestBody(
    description: 'Données de l\'utilisateur à créer',
    required: true,
    contentType: 'application/json'
)]
public function store(ServerRequestInterface $request): Response
{
    // La validation définit automatiquement le schéma
    $validator = new Validator($request->getParsedBody(), [
        'name' => 'required|string|min:3|max:255',
        'email' => 'required|email',
        'password' => 'required|string|min:8',
        'age' => 'integer|min:18|max:120',
    ]);

    // ...
}
```

#### Response - Réponses

```php
use Elarion\OpenAPI\Attributes\{Get, Response as ResponseAttr};

#[Get(path: '/users/{id}', summary: 'Obtenir un utilisateur')]
#[ResponseAttr(
    statusCode: '200',
    description: 'Utilisateur trouvé avec succès',
    contentType: 'application/json'
)]
#[ResponseAttr(
    statusCode: '404',
    description: 'Utilisateur non trouvé'
)]
#[ResponseAttr(
    statusCode: '401',
    description: 'Non authentifié'
)]
public function show(int $id): Response
{
    $user = $this->userRepository->find($id);

    if (!$user) {
        return Response::json(['error' => 'User not found'], 404);
    }

    return Response::json($user);
}
```

### Attributs d'Organisation

#### Tag - Grouper les endpoints

```php
use Elarion\OpenAPI\Attributes\Tag;

#[Tag('Users')]
class UserController
{
    #[Get(path: '/users', tags: ['Users'])]
    public function index(): Response { }

    #[Post(path: '/users', tags: ['Users'])]
    public function store(): Response { }
}
```

---

## Exemples Complets de Controllers

### Exemple 1: Controller CRUD Complet

```php
<?php

namespace App\Http\Controllers;

use Elarion\Http\Message\Response;
use Elarion\OpenAPI\Attributes\{
    Get,
    Post,
    Put,
    Delete,
    PathParameter,
    QueryParameter,
    RequestBody,
    Response as ResponseAttr,
    Tag
};
use Elarion\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;

#[Tag('Users', 'Gestion des utilisateurs')]
class UserController
{
    /**
     * Liste tous les utilisateurs avec pagination et filtres
     */
    #[Get(
        path: '/api/users',
        summary: 'Liste des utilisateurs',
        description: 'Retourne une liste paginée d\'utilisateurs avec options de filtrage et recherche',
        tags: ['Users'],
        operationId: 'listUsers'
    )]
    #[QueryParameter('page', 'integer', 'Numéro de page (défaut: 1)')]
    #[QueryParameter('limit', 'integer', 'Éléments par page (défaut: 20, max: 100)')]
    #[QueryParameter('search', 'string', 'Recherche par nom ou email')]
    #[QueryParameter('role', 'string', 'Filtrer par rôle (admin, user, guest)')]
    #[QueryParameter('status', 'string', 'Filtrer par statut (active, inactive)')]
    #[QueryParameter('sort', 'string', 'Tri (name, email, created_at)')]
    #[QueryParameter('order', 'string', 'Ordre (asc, desc)')]
    #[ResponseAttr('200', 'Liste des utilisateurs retournée')]
    #[ResponseAttr('400', 'Paramètres de requête invalides')]
    public function index(ServerRequestInterface $request): Response
    {
        $params = $request->getQueryParams();

        // Validation des paramètres
        $validator = new Validator($params, [
            'page' => 'integer|min:1',
            'limit' => 'integer|min:1|max:100',
            'role' => 'string',
            'status' => 'string',
            'sort' => 'string',
            'order' => 'string',
        ]);

        if (!$validator->validate()) {
            return Response::json([
                'error' => 'Invalid parameters',
                'details' => $validator->errors()
            ], 400);
        }

        $page = (int) ($params['page'] ?? 1);
        $limit = min((int) ($params['limit'] ?? 20), 100);
        $search = $params['search'] ?? null;
        $role = $params['role'] ?? null;
        $status = $params['status'] ?? null;
        $sort = $params['sort'] ?? 'created_at';
        $order = $params['order'] ?? 'desc';

        // Logique de récupération (exemple simplifié)
        $users = $this->getUsersWithFilters($search, $role, $status, $sort, $order, $page, $limit);
        $total = $this->getUsersCount($search, $role, $status);

        return Response::json([
            'data' => $users,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit),
            ],
            'links' => [
                'self' => "/api/users?page={$page}",
                'first' => '/api/users?page=1',
                'last' => '/api/users?page=' . ceil($total / $limit),
                'prev' => $page > 1 ? "/api/users?page=" . ($page - 1) : null,
                'next' => $page < ceil($total / $limit) ? "/api/users?page=" . ($page + 1) : null,
            ],
        ]);
    }

    /**
     * Créer un nouvel utilisateur
     */
    #[Post(
        path: '/api/users',
        summary: 'Créer un utilisateur',
        description: 'Crée un nouvel utilisateur avec les données fournies',
        tags: ['Users'],
        operationId: 'createUser'
    )]
    #[RequestBody('Données du nouvel utilisateur', required: true)]
    #[ResponseAttr('201', 'Utilisateur créé avec succès')]
    #[ResponseAttr('422', 'Données de validation invalides')]
    #[ResponseAttr('409', 'Email déjà utilisé')]
    public function store(ServerRequestInterface $request): Response
    {
        $data = $request->getParsedBody();

        // Validation complète
        $validator = new Validator($data, [
            'name' => 'required|string|min:3|max:255',
            'email' => 'required|email',
            'password' => 'required|string|min:8',
            'age' => 'integer|min:18|max:120',
            'role' => 'string',
            'bio' => 'string|max:1000',
            'phone' => 'string',
            'address' => 'string|max:500',
        ]);

        if (!$validator->validate()) {
            return Response::json([
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 422);
        }

        $validatedData = $validator->validated();

        // Vérifier si l'email existe déjà
        if ($this->emailExists($validatedData['email'])) {
            return Response::json([
                'error' => 'Email already in use'
            ], 409);
        }

        // Hasher le mot de passe
        $validatedData['password'] = password_hash($validatedData['password'], PASSWORD_BCRYPT);

        // Créer l'utilisateur
        $user = $this->createUser($validatedData);

        return Response::json($user, 201);
    }

    /**
     * Obtenir un utilisateur spécifique
     */
    #[Get(
        path: '/api/users/{id}',
        summary: 'Obtenir un utilisateur',
        description: 'Retourne les détails complets d\'un utilisateur spécifique',
        tags: ['Users'],
        operationId: 'getUser'
    )]
    #[PathParameter('id', 'integer', 'ID de l\'utilisateur', format: 'int64')]
    #[ResponseAttr('200', 'Utilisateur trouvé')]
    #[ResponseAttr('404', 'Utilisateur non trouvé')]
    public function show(int $id): Response
    {
        $user = $this->findUser($id);

        if (!$user) {
            return Response::json([
                'error' => 'User not found'
            ], 404);
        }

        return Response::json($user);
    }

    /**
     * Mettre à jour un utilisateur
     */
    #[Put(
        path: '/api/users/{id}',
        summary: 'Mettre à jour un utilisateur',
        description: 'Met à jour les informations d\'un utilisateur existant',
        tags: ['Users'],
        operationId: 'updateUser'
    )]
    #[PathParameter('id', 'integer', 'ID de l\'utilisateur')]
    #[RequestBody('Données de mise à jour', required: true)]
    #[ResponseAttr('200', 'Utilisateur mis à jour')]
    #[ResponseAttr('404', 'Utilisateur non trouvé')]
    #[ResponseAttr('422', 'Données invalides')]
    public function update(int $id, ServerRequestInterface $request): Response
    {
        $user = $this->findUser($id);

        if (!$user) {
            return Response::json(['error' => 'User not found'], 404);
        }

        $data = $request->getParsedBody();

        $validator = new Validator($data, [
            'name' => 'string|min:3|max:255',
            'email' => 'email',
            'age' => 'integer|min:18|max:120',
            'role' => 'string',
            'bio' => 'string|max:1000',
        ]);

        if (!$validator->validate()) {
            return Response::json([
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 422);
        }

        $updatedUser = $this->updateUser($id, $validator->validated());

        return Response::json($updatedUser);
    }

    /**
     * Supprimer un utilisateur
     */
    #[Delete(
        path: '/api/users/{id}',
        summary: 'Supprimer un utilisateur',
        description: 'Supprime définitivement un utilisateur du système',
        tags: ['Users'],
        operationId: 'deleteUser'
    )]
    #[PathParameter('id', 'integer', 'ID de l\'utilisateur')]
    #[ResponseAttr('204', 'Utilisateur supprimé')]
    #[ResponseAttr('404', 'Utilisateur non trouvé')]
    #[ResponseAttr('409', 'Impossible de supprimer (relations existantes)')]
    public function destroy(int $id): Response
    {
        $user = $this->findUser($id);

        if (!$user) {
            return Response::json(['error' => 'User not found'], 404);
        }

        // Vérifier les dépendances
        if ($this->userHasRelations($id)) {
            return Response::json([
                'error' => 'Cannot delete user with existing relations'
            ], 409);
        }

        $this->deleteUser($id);

        return Response::noContent();
    }

    // Méthodes privées (exemples simplifiés)
    private function getUsersWithFilters($search, $role, $status, $sort, $order, $page, $limit): array
    {
        // Implémentation de la logique de base de données
        return [];
    }

    private function getUsersCount($search, $role, $status): int
    {
        return 0;
    }

    private function emailExists(string $email): bool
    {
        return false;
    }

    private function createUser(array $data): array
    {
        return $data;
    }

    private function findUser(int $id): ?array
    {
        return null;
    }

    private function updateUser(int $id, array $data): array
    {
        return $data;
    }

    private function userHasRelations(int $id): bool
    {
        return false;
    }

    private function deleteUser(int $id): void
    {
        // Suppression
    }
}
```

### Exemple 2: Controller avec Relations

```php
<?php

namespace App\Http\Controllers;

use Elarion\Http\Message\Response;
use Elarion\OpenAPI\Attributes\{Get, Post, PathParameter, QueryParameter, Tag};
use Psr\Http\Message\ServerRequestInterface;

#[Tag('Posts', 'Gestion des articles de blog')]
class PostController
{
    /**
     * Obtenir tous les articles d'un utilisateur
     */
    #[Get(
        path: '/api/users/{userId}/posts',
        summary: 'Articles d\'un utilisateur',
        description: 'Retourne tous les articles publiés par un utilisateur spécifique',
        tags: ['Posts', 'Users']
    )]
    #[PathParameter('userId', 'integer', 'ID de l\'utilisateur')]
    #[QueryParameter('status', 'string', 'Filtrer par statut (draft, published)')]
    public function userPosts(int $userId, ServerRequestInterface $request): Response
    {
        $params = $request->getQueryParams();
        $status = $params['status'] ?? null;

        $posts = $this->getPostsByUser($userId, $status);

        return Response::json([
            'data' => $posts,
            'meta' => [
                'user_id' => $userId,
                'count' => count($posts),
            ],
        ]);
    }

    /**
     * Ajouter un commentaire à un article
     */
    #[Post(
        path: '/api/posts/{postId}/comments',
        summary: 'Ajouter un commentaire',
        description: 'Ajoute un nouveau commentaire à un article',
        tags: ['Posts', 'Comments']
    )]
    #[PathParameter('postId', 'integer', 'ID de l\'article')]
    public function addComment(int $postId, ServerRequestInterface $request): Response
    {
        $data = $request->getParsedBody();

        $validator = new \Elarion\Validation\Validator($data, [
            'content' => 'required|string|min:10|max:2000',
            'author_name' => 'required|string|max:255',
            'author_email' => 'required|email',
        ]);

        if (!$validator->validate()) {
            return Response::json([
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 422);
        }

        $comment = $this->createComment($postId, $validator->validated());

        return Response::json($comment, 201);
    }

    private function getPostsByUser(int $userId, ?string $status): array
    {
        return [];
    }

    private function createComment(int $postId, array $data): array
    {
        return $data;
    }
}
```

---

## Validation et Schémas de Requête

### Conversion Automatique des Règles de Validation

Le générateur convertit automatiquement vos règles de validation en schémas OpenAPI:

```php
#[Post(path: '/api/users', summary: 'Créer un utilisateur')]
#[RequestBody('Données utilisateur', required: true)]
public function store(ServerRequestInterface $request): Response
{
    $validator = new Validator($request->getParsedBody(), [
        // Règles basiques
        'name' => 'required|string|min:3|max:255',
        'email' => 'required|email',
        'age' => 'integer|min:18|max:120',
        'website' => 'url',
        'bio' => 'string|max:1000',

        // Tableaux
        'tags' => 'array',
        'roles' => 'array',

        // Booléens
        'is_active' => 'boolean',
        'newsletter' => 'boolean',

        // Numériques
        'salary' => 'numeric|min:0',
        'rating' => 'numeric|min:0|max:5',

        // Dates
        'birth_date' => 'date',
        'registered_at' => 'date',
    ]);

    if (!$validator->validate()) {
        return Response::json(['errors' => $validator->errors()], 422);
    }

    return Response::json($validator->validated(), 201);
}
```

**Schéma OpenAPI généré automatiquement:**

```json
{
  "type": "object",
  "required": ["name", "email"],
  "properties": {
    "name": {
      "type": "string",
      "minLength": 3,
      "maxLength": 255
    },
    "email": {
      "type": "string",
      "format": "email"
    },
    "age": {
      "type": "integer",
      "minimum": 18,
      "maximum": 120
    },
    "website": {
      "type": "string",
      "format": "uri"
    },
    "bio": {
      "type": "string",
      "maxLength": 1000
    },
    "tags": {
      "type": "array"
    },
    "is_active": {
      "type": "boolean"
    },
    "salary": {
      "type": "number",
      "minimum": 0
    },
    "birth_date": {
      "type": "string",
      "format": "date"
    }
  }
}
```

### Tableau de Correspondance des Règles

| Règle de Validation | Type OpenAPI | Format | Contraintes |
|---------------------|--------------|---------|-------------|
| `required` | - | - | Ajouté à `required[]` |
| `string` | `string` | - | - |
| `integer` | `integer` | - | - |
| `numeric` | `number` | - | - |
| `boolean` | `boolean` | - | - |
| `array` | `array` | - | - |
| `email` | `string` | `email` | - |
| `url` | `string` | `uri` | - |
| `date` | `string` | `date` | - |
| `min:N` (string) | `string` | - | `minLength: N` |
| `min:N` (number) | `number` | - | `minimum: N` |
| `max:N` (string) | `string` | - | `maxLength: N` |
| `max:N` (number) | `number` | - | `maximum: N` |

---

## API Resources et Schémas de Réponse

### Utilisation de ResourceScanner

Le `ResourceScanner` génère automatiquement des schémas OpenAPI à partir de vos classes Resource.

#### Exemple 1: Resource Simple

```php
<?php

namespace App\Http\Resources;

use Elarion\Http\Resources\JsonResource;
use Psr\Http\Message\ServerRequestInterface;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array
     *
     * @return array{id: int, name: string, email: string, created_at: string}
     */
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

**Utilisation dans un contrôleur:**

```php
#[Get(path: '/api/users/{id}')]
public function show(int $id): Response
{
    $user = $this->findUser($id);

    if (!$user) {
        return Response::json(['error' => 'Not found'], 404);
    }

    return UserResource::make($user)->toResponse($request);
}
```

**Génération programmatique du schéma:**

```php
use Elarion\OpenAPI\Generator\ResourceScanner;

$scanner = new ResourceScanner();

// Avec PHPDoc (recommandé)
$schema = $scanner->scan(UserResource::class);

// Avec données d'exemple pour meilleure inférence
$sampleData = [
    'id' => 1,
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'created_at' => '2024-01-01T12:00:00Z',
];
$schema = $scanner->scan(UserResource::class, $sampleData);

// Depuis une instance avec requête
$resource = UserResource::make($user);
$schema = $scanner->scanFromInstance($resource, $request);
```

#### Exemple 2: Resource avec Champs Conditionnels

```php
<?php

namespace App\Http\Resources;

use Elarion\Http\Resources\JsonResource;
use Psr\Http\Message\ServerRequestInterface;

class UserResource extends JsonResource
{
    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     email: string,
     *     role?: string,
     *     permissions?: array<string>,
     *     last_login?: string,
     *     created_at: string
     * }
     */
    public function toArray(ServerRequestInterface $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,

            // Champs conditionnels
            'role' => $this->when($this->isAdmin(), $this->role),
            'permissions' => $this->when($this->isAdmin(), $this->permissions),
            'last_login' => $this->when(isset($this->last_login), $this->last_login),

            'created_at' => $this->created_at,
        ];
    }

    private function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
```

#### Exemple 3: Resource avec Relations

```php
<?php

namespace App\Http\Resources;

use Elarion\Http\Resources\JsonResource;
use Psr\Http\Message\ServerRequestInterface;

class PostResource extends JsonResource
{
    /**
     * @return array{
     *     id: int,
     *     title: string,
     *     content: string,
     *     author: array{id: int, name: string, email: string},
     *     comments: array<array{id: int, content: string, author: string}>,
     *     tags: array<string>,
     *     published_at: string,
     *     created_at: string
     * }
     */
    public function toArray(ServerRequestInterface $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,

            // Relation author
            'author' => [
                'id' => $this->author->id,
                'name' => $this->author->name,
                'email' => $this->author->email,
            ],

            // Collection comments
            'comments' => array_map(fn($comment) => [
                'id' => $comment->id,
                'content' => $comment->content,
                'author' => $comment->author_name,
            ], $this->comments),

            // Simple array
            'tags' => $this->tags,

            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
        ];
    }
}
```

### Collection Resources

```php
use Elarion\Http\Resources\ResourceCollection;

class UserCollection extends ResourceCollection
{
    public function toArray(ServerRequestInterface $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => count($this->collection),
            ],
        ];
    }
}

// Utilisation
#[Get(path: '/api/users')]
public function index(): Response
{
    $users = $this->getAllUsers();
    return UserResource::collection($users)->toResponse($request);
}
```

---

## Support JSON:API

Le `JsonApiScanner` génère des schémas conformes à la spécification JSON:API v1.1.

### Exemple 1: Resource JSON:API Simple

```php
<?php

namespace App\Http\Resources;

use Elarion\Http\Resources\JsonApi\JsonApiResource;
use Psr\Http\Message\ServerRequestInterface;

class UserJsonApiResource extends JsonApiResource
{
    public function type(): string
    {
        return 'users';
    }

    public function id(): string|int
    {
        return $this->resource->id;
    }

    public function attributes(ServerRequestInterface $request): array
    {
        return [
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'age' => $this->resource->age,
            'created_at' => $this->resource->created_at,
        ];
    }

    public function links(ServerRequestInterface $request): array
    {
        return [
            'self' => "/api/users/{$this->id()}",
        ];
    }
}
```

**Contrôleur:**

```php
#[Get(
    path: '/api/users/{id}',
    summary: 'Get user (JSON:API format)'
)]
public function show(int $id, ServerRequestInterface $request): Response
{
    $user = $this->findUser($id);

    if (!$user) {
        return Response::json(['errors' => [
            ['status' => '404', 'title' => 'Not Found']
        ]], 404);
    }

    return UserJsonApiResource::make($user)->toResponse($request);
}
```

**Réponse JSON:API générée:**

```json
{
  "data": {
    "type": "users",
    "id": "123",
    "attributes": {
      "name": "John Doe",
      "email": "john@example.com",
      "age": 30,
      "created_at": "2024-01-01T12:00:00Z"
    },
    "links": {
      "self": "/api/users/123"
    }
  },
  "jsonapi": {
    "version": "1.1"
  }
}
```

### Exemple 2: Resource avec Relations

```php
class PostJsonApiResource extends JsonApiResource
{
    public function type(): string
    {
        return 'posts';
    }

    public function id(): string|int
    {
        return $this->resource->id;
    }

    public function attributes(ServerRequestInterface $request): array
    {
        return [
            'title' => $this->resource->title,
            'content' => $this->resource->content,
            'published_at' => $this->resource->published_at,
        ];
    }

    public function relationships(ServerRequestInterface $request): array
    {
        return [
            'author' => $this->relationship(
                'users',
                UserJsonApiResource::make($this->resource->author),
                links: [
                    'self' => "/api/posts/{$this->id()}/relationships/author",
                    'related' => "/api/posts/{$this->id()}/author",
                ]
            ),
            'comments' => $this->relationship(
                'comments',
                array_map(
                    fn($c) => CommentJsonApiResource::make($c),
                    $this->resource->comments
                ),
                links: [
                    'self' => "/api/posts/{$this->id()}/relationships/comments",
                    'related' => "/api/posts/{$this->id()}/comments",
                ]
            ),
        ];
    }

    public function links(ServerRequestInterface $request): array
    {
        return [
            'self' => "/api/posts/{$this->id()}",
        ];
    }
}
```

### Exemple 3: Collection avec Pagination

```php
class PostJsonApiCollection extends JsonApiCollection
{
    public function toArray(ServerRequestInterface $request): array
    {
        $params = $request->getQueryParams();
        $page = (int) ($params['page'] ?? 1);
        $limit = (int) ($params['limit'] ?? 20);

        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->total,
                'count' => count($this->collection),
                'per_page' => $limit,
                'current_page' => $page,
                'total_pages' => ceil($this->total / $limit),
            ],
            'links' => [
                'self' => "/api/posts?page={$page}",
                'first' => '/api/posts?page=1',
                'last' => '/api/posts?page=' . ceil($this->total / $limit),
                'prev' => $page > 1 ? "/api/posts?page=" . ($page - 1) : null,
                'next' => $page < ceil($this->total / $limit) ? "/api/posts?page=" . ($page + 1) : null,
            ],
        ];
    }
}
```

### Génération de Schémas JSON:API

```php
use Elarion\OpenAPI\Generator\JsonApiScanner;

$scanner = new JsonApiScanner();

// Schéma de document JSON:API
$documentSchema = $scanner->generateDocumentSchema();

// Schéma de ressource
$resourceSchema = $scanner->generateResourceObjectSchema();

// Schéma de collection
$collectionSchema = $scanner->generateCollectionSchema();

// Schéma d'erreur
$errorSchema = $scanner->generateErrorResponseSchema();

// Schéma de pagination
$paginationMeta = $scanner->generatePaginationMetaSchema();
$paginationLinks = $scanner->generatePaginationLinksSchema();
```

---

## Configuration Avancée

### Personnalisation des Routes de Documentation

```php
// config/openapi.php
return [
    'routes' => [
        // Routes personnalisées
        'ui' => '/docs',                  // Swagger UI
        'redoc' => '/docs/redoc',         // ReDoc UI
        'json' => '/openapi.json',        // Export JSON
        'yaml' => '/openapi.yaml',        // Export YAML
    ],
];
```

### Configuration Multi-Environnements

```php
// .env.development
API_TITLE="Mon API - Développement"
API_VERSION="1.0.0-dev"
API_URL="http://localhost:8000"

// .env.staging
API_TITLE="Mon API - Staging"
API_VERSION="1.0.0-rc"
API_URL="https://staging.api.example.com"

// .env.production
API_TITLE="Mon API - Production"
API_VERSION="1.0.0"
API_URL="https://api.example.com"
```

### Multiples Serveurs

```php
// config/openapi.php
return [
    'servers' => [
        [
            'url' => 'http://localhost:8000',
            'description' => 'Développement local',
        ],
        [
            'url' => 'https://staging.api.example.com',
            'description' => 'Serveur de staging',
        ],
        [
            'url' => 'https://api.example.com',
            'description' => 'Production',
        ],
        [
            'url' => 'https://{environment}.api.example.com',
            'description' => 'Environnement paramétrable',
            'variables' => [
                'environment' => [
                    'default' => 'staging',
                    'enum' => ['staging', 'prod', 'dev'],
                ],
            ],
        ],
    ],
];
```

---

## Génération Programmatique

### Générer la Documentation depuis du Code

```php
<?php

use Elarion\OpenAPI\Generator\OpenApiGenerator;
use Elarion\Routing\Router;

// Dans un script ou une commande
$router = $container->make(Router::class);

$generator = new OpenApiGenerator($router, [
    'title' => 'Mon API',
    'version' => '2.0.0',
    'description' => 'Documentation générée programmatiquement',
    'servers' => [
        ['url' => 'https://api.example.com'],
    ],
]);

// Générer le document
$document = $generator->generate();

// Exporter en JSON
$json = $document->toJson();
file_put_contents('openapi.json', $json);

// Exporter en YAML
$yaml = $document->toYaml();
file_put_contents('openapi.yaml', $yaml);

// Obtenir le tableau PHP
$array = $document->jsonSerialize();

echo "Documentation générée avec succès!\n";
echo "Routes documentées: " . count($array['paths']) . "\n";
```

### Commande CLI pour Générer la Documentation

```php
<?php

namespace App\Console\Commands;

class GenerateOpenApiCommand
{
    public function __construct(
        private OpenApiGenerator $generator
    ) {}

    public function execute(array $options): void
    {
        $format = $options['format'] ?? 'json'; // json, yaml, both
        $output = $options['output'] ?? 'openapi';

        $document = $this->generator->generate();

        if (in_array($format, ['json', 'both'])) {
            $json = $document->toJson();
            file_put_contents("{$output}.json", $json);
            echo "✓ Generated {$output}.json\n";
        }

        if (in_array($format, ['yaml', 'both'])) {
            $yaml = $document->toYaml();
            file_put_contents("{$output}.yaml", $yaml);
            echo "✓ Generated {$output}.yaml\n";
        }

        $data = $document->jsonSerialize();
        echo "Documented " . count($data['paths']) . " endpoints\n";
    }
}

// Utilisation:
// php console generate:openapi --format=both --output=docs/api
```

---

## Bonnes Pratiques

### 1. Organisation des Tags

Groupez logiquement vos endpoints:

```php
// ✅ BON
#[Tag('Users', 'Gestion des utilisateurs')]
#[Tag('Auth', 'Authentification et autorisation')]
#[Tag('Posts', 'Gestion des articles')]
#[Tag('Admin', 'Administration système')]

// ❌ MAUVAIS - Tags trop génériques
#[Tag('API')]
#[Tag('Endpoints')]
```

### 2. Nommage des Opérations

Utilisez des IDs d'opération uniques et descriptifs:

```php
// ✅ BON
#[Get(path: '/users', operationId: 'listUsers')]
#[Post(path: '/users', operationId: 'createUser')]
#[Get(path: '/users/{id}', operationId: 'getUser')]
#[Put(path: '/users/{id}', operationId: 'updateUser')]
#[Delete(path: '/users/{id}', operationId: 'deleteUser')]

// ❌ MAUVAIS
#[Get(path: '/users', operationId: 'users1')]
#[Post(path: '/users', operationId: 'users2')]
```

### 3. Descriptions Claires

Soyez précis et complet:

```php
// ✅ BON
#[Get(
    path: '/users',
    summary: 'Liste des utilisateurs',
    description: 'Retourne une liste paginée de tous les utilisateurs avec options de filtrage par rôle, statut et recherche textuelle. Supporte le tri par nom, email ou date de création.'
)]

// ❌ MAUVAIS
#[Get(path: '/users', summary: 'Get users')]
```

### 4. Documentation des Codes de Réponse

Documentez tous les cas possibles:

```php
// ✅ BON
#[ResponseAttr('200', 'Succès - Utilisateur créé')]
#[ResponseAttr('400', 'Requête invalide - Paramètres manquants ou incorrects')]
#[ResponseAttr('401', 'Non authentifié - Token manquant ou invalide')]
#[ResponseAttr('403', 'Accès refusé - Permissions insuffisantes')]
#[ResponseAttr('422', 'Validation échouée - Voir details pour les erreurs de champ')]
#[ResponseAttr('500', 'Erreur serveur - Erreur interne du système')]

// ❌ MAUVAIS
#[ResponseAttr('200', 'OK')]
#[ResponseAttr('400', 'Error')]
```

### 5. Validation Complète

Utilisez toutes les règles nécessaires:

```php
// ✅ BON
$validator = new Validator($data, [
    'email' => 'required|email|max:255',
    'name' => 'required|string|min:2|max:100',
    'age' => 'integer|min:18|max:120',
    'website' => 'url|max:255',
    'bio' => 'string|max:500',
]);

// ❌ MAUVAIS
$validator = new Validator($data, [
    'email' => 'email',
    'name' => 'string',
]);
```

### 6. Utilisation Cohérente des Resources

```php
// ✅ BON - Resource dédiée pour chaque type
class UserResource extends JsonResource { }
class UserCollection extends ResourceCollection { }
class AdminUserResource extends JsonResource { }

// Dans le contrôleur
public function show(int $id): Response
{
    return UserResource::make($user)->toResponse($request);
}

public function index(): Response
{
    return UserResource::collection($users)->toResponse($request);
}

// ❌ MAUVAIS - Retour direct sans Resource
public function show(int $id): Response
{
    return Response::json($user);
}
```

### 7. Versioning de l'API

```php
// config/openapi.php
return [
    'version' => '2.1.0',  // Semantic versioning
];

// Dans les routes
$router->group(['prefix' => '/api/v2'], function($router) {
    $router->get('/users', [UserV2Controller::class, 'index']);
});
```

### 8. PHPDoc pour Meilleure Inférence

```php
// ✅ BON - PHPDoc explicite
/**
 * @return array{
 *     id: int,
 *     name: string,
 *     email: string,
 *     roles: array<string>,
 *     metadata: array{created_at: string, updated_at: string}
 * }
 */
public function toArray(ServerRequestInterface $request): array
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'email' => $this->email,
        'roles' => $this->roles,
        'metadata' => [
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ],
    ];
}
```

---

## Exemples Complets de Projets

### Projet E-Commerce

```php
<?php

namespace App\Http\Controllers;

use Elarion\OpenAPI\Attributes\{Get, Post, Put, Delete, PathParameter, QueryParameter, Tag};

#[Tag('Products', 'Catalogue de produits')]
class ProductController
{
    #[Get(
        path: '/api/products',
        summary: 'Liste des produits',
        description: 'Catalogue complet avec filtres et pagination'
    )]
    #[QueryParameter('category', 'string', 'Filtrer par catégorie')]
    #[QueryParameter('min_price', 'number', 'Prix minimum')]
    #[QueryParameter('max_price', 'number', 'Prix maximum')]
    #[QueryParameter('in_stock', 'boolean', 'Seulement les produits en stock')]
    #[QueryParameter('sort', 'string', 'Tri (price, name, rating)')]
    public function index(ServerRequestInterface $request): Response { }

    #[Get(
        path: '/api/products/{id}',
        summary: 'Détails d\'un produit'
    )]
    #[PathParameter('id', 'integer', 'ID du produit')]
    public function show(int $id): Response { }
}

#[Tag('Orders', 'Gestion des commandes')]
class OrderController
{
    #[Post(
        path: '/api/orders',
        summary: 'Créer une commande'
    )]
    public function store(ServerRequestInterface $request): Response { }

    #[Get(
        path: '/api/orders/{id}',
        summary: 'Détails d\'une commande'
    )]
    #[PathParameter('id', 'integer', 'ID de la commande')]
    public function show(int $id): Response { }
}

#[Tag('Cart', 'Panier d\'achat')]
class CartController
{
    #[Get(path: '/api/cart', summary: 'Voir le panier')]
    public function show(): Response { }

    #[Post(path: '/api/cart/items', summary: 'Ajouter au panier')]
    public function addItem(ServerRequestInterface $request): Response { }

    #[Delete(path: '/api/cart/items/{id}', summary: 'Retirer du panier')]
    #[PathParameter('id', 'integer', 'ID de l\'article')]
    public function removeItem(int $id): Response { }
}
```

---

## Conclusion

Cette documentation complète vous permet de:

1. ✅ Installer et configurer le générateur OpenAPI
2. ✅ Annoter vos controllers avec des Attributes PHP
3. ✅ Générer automatiquement des schémas depuis la validation
4. ✅ Utiliser des Resources pour documenter les réponses
5. ✅ Implémenter le support JSON:API complet
6. ✅ Personnaliser la configuration selon vos besoins
7. ✅ Générer la documentation programmatiquement
8. ✅ Suivre les meilleures pratiques

### Ressources Supplémentaires

- **README**: `/src/OpenAPI/README.md` - Vue d'ensemble et référence rapide
- **Spécification OpenAPI**: https://spec.openapis.org/oas/v3.1.0
- **Spécification JSON:API**: https://jsonapi.org/format/
- **Tests**: `/tests/Unit/OpenAPI/` - Exemples de tests

### Support et Contributions

Pour toute question ou contribution, consultez le dépôt GitHub du projet.

---

**Documentation générée pour ElarionStack OpenAPI Generator v1.0.0**
