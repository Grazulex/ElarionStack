# ElarionStack Framework

## Vision

ElarionStack est un framework PHP moderne conçu pour créer des API expressives, maintenables et élégantes. Inspiré par la philosophie artisanale de Laravel, il offre une architecture claire, une syntaxe fluide, et une intégration native avec les conventions modernes du développement d'API.

## Philosophie

- **Expressivité** : Le code doit être lisible et intuitif
- **Maintenabilité** : Architecture claire et découplée
- **Performance** : Optimisé pour PHP 8.5 avec typage strict
- **Standards** : Support natif REST, JSON:API, GraphQL
- **Developer Experience** : Outils et conventions qui facilitent le développement

## Architecture Prévue

### Structure du Framework

```
src/
├── Core/
│   ├── Application.php
│   ├── Container/
│   ├── ServiceProvider/
│   └── Pipeline/
├── Http/
│   ├── Router/
│   ├── Request/
│   ├── Response/
│   ├── Middleware/
│   └── Controllers/
├── Database/
│   ├── Query/
│   ├── ORM/
│   └── Migrations/
├── Validation/
├── Api/
│   ├── Resources/
│   ├── Transformers/
│   ├── JsonApi/
│   └── GraphQL/
└── Support/
    ├── Collection/
    ├── Helpers/
    └── Traits/
```

## Fonctionnalités Cibles

### Phase 1 : Core & Routing
- [ ] Container d'injection de dépendances
- [ ] Service Providers
- [ ] Router HTTP performant
- [ ] Request/Response avec PSR-7
- [ ] Middleware pipeline
- [ ] Configuration system

### Phase 2 : Database
- [ ] Query Builder fluent
- [ ] ORM (Active Record pattern)
- [ ] Migrations
- [ ] Seeders
- [ ] Connection pooling

### Phase 3 : API Features
- [ ] API Resources & Transformers
- [ ] JSON:API support complet
- [ ] GraphQL integration
- [ ] Rate limiting
- [ ] API Versioning
- [ ] CORS handling

### Phase 4 : Developer Tools
- [ ] CLI pour scaffolding
- [ ] Hot reload en développement
- [ ] API documentation generator
- [ ] Testing utilities
- [ ] Debugging tools

## Conventions de Code

### PHP Version
- **Minimum** : PHP 8.5
- Utilisation complète des features modernes (readonly classes, asymmetric visibility, etc.)

### Style Guide
- PSR-12 pour le code style
- PSR-4 pour l'autoloading
- Typage strict activé (`declare(strict_types=1)`)
- Return types obligatoires
- Property types obligatoires

### Naming Conventions
- Classes : PascalCase
- Methods : camelCase
- Constants : UPPER_SNAKE_CASE
- Variables : camelCase
- Interfaces : suffixe "Interface"
- Traits : suffixe "Trait"
- Abstract : préfixe "Abstract"

### Documentation
- PHPDoc pour toutes les méthodes publiques
- Type hints stricts
- Exemples d'utilisation dans les docblocks

## Exemples d'API Cible

### Routing Fluent
```php
use Elarion\Http\Router;

Router::get('/users', [UserController::class, 'index'])
    ->middleware('auth:api')
    ->name('users.index');

Router::group(['prefix' => 'api/v1'], function() {
    Router::resource('posts', PostController::class);
});
```

### API Resources
```php
use Elarion\Api\Resource;

class UserResource extends Resource
{
    public function toArray(Request $request): array
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

### Query Builder
```php
use Elarion\Database\DB;

$users = DB::table('users')
    ->where('active', true)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();
```

### ORM
```php
use Elarion\Database\Model;

class User extends Model
{
    protected array $fillable = ['name', 'email'];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}

$user = User::with('posts')
    ->where('email', 'user@example.com')
    ->first();
```

## Technologies

- **PHP** : 8.5
- **PSR** : PSR-4, PSR-7, PSR-11, PSR-12, PSR-15
- **Testing** : PHPUnit, Pest
- **Documentation** : PHPDocumentor
- **Quality** : PHPStan (level 9), PHP CS Fixer

## Roadmap

### Q4 2024
- Setup projet et architecture de base
- Container et Service Providers
- Router HTTP
- Request/Response handling

### Q1 2025
- Database Query Builder
- ORM de base
- Migrations system
- API Resources

### Q2 2025
- JSON:API support
- GraphQL integration
- CLI tools
- Documentation

### Q3 2025
- Performance optimization
- Advanced features
- Testing suite complète
- Version 1.0 Release

## Principes de Développement

1. **Test-Driven Development** : Tests unitaires et d'intégration
2. **SOLID Principles** : Code découplé et maintenable
3. **Design Patterns** : Utilisation appropriée des patterns éprouvés
4. **Performance First** : Optimisation dès la conception
5. **Developer Joy** : API intuitive et documentation claire

## Contribution

### Workflow
1. Feature branches depuis `develop`
2. Pull requests avec tests
3. Code review obligatoire
4. Merge vers `develop` puis `main`

### Commit Messages
Format : `type(scope): message`

Types :
- `feat`: Nouvelle fonctionnalité
- `fix`: Correction de bug
- `docs`: Documentation
- `refactor`: Refactoring
- `test`: Tests
- `perf`: Performance
- `chore`: Maintenance

### Testing
- Coverage minimum : 80%
- Tests unitaires pour toute logique métier
- Tests d'intégration pour les features
- Tests de performance pour les opérations critiques

## Resources

- [PHP 8.5 Documentation](https://www.php.net/manual/en/)
- [PSR Standards](https://www.php-fig.org/psr/)
- [Laravel Concepts](https://laravel.com/docs) (inspiration)
- [JSON:API Specification](https://jsonapi.org/)
- [GraphQL](https://graphql.org/)

## License

MIT License - À définir

---

**Status** : En développement initial
**Version** : 0.1.0-dev
**PHP** : ^8.5
**Maintainer** : [À définir]
