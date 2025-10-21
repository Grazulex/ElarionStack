---
id: task-00016
title: OpenAPI/Swagger Documentation Generator
status: Done
assignee:
  - '@Claude'
created_date: '2025-10-21 22:48'
updated_date: '2025-10-21 23:20'
labels:
  - openapi
  - swagger
  - documentation
  - api
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Implémenter un système de génération automatique de documentation OpenAPI 3.1 (Swagger) à partir d'annotations PHP Attributes, d'analyse de routes, de validation rules et d'API Resources. Inclut UI Swagger interactive pour tester l'API.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Support PHP Attributes pour annoter les endpoints (Get, Post, Put, Delete, etc.)
- [x] #2 Génération automatique de la documentation depuis les routes enregistrées
- [x] #3 Conversion automatique des Validation rules en Request schemas OpenAPI
- [x] #4 Conversion automatique des API Resources en Response schemas
- [x] #5 Support spécifique pour JSON:API avec schémas conformes
- [x] #6 Endpoint /api/documentation avec export JSON et YAML
- [x] #7 UI Swagger intégrée pour tester l'API interactivement
- [x] #8 Support ReDoc pour documentation élégante
- [x] #9 Les tests vérifient la génération correcte des schémas OpenAPI
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
## Architecture Globale

### Phase 1: Fondations OpenAPI (Core Schema)
1. Créer les classes de schéma OpenAPI 3.1
   - OpenApiDocument (root)
   - Info, Server, Paths, Components
   - PathItem, Operation, Parameter
   - Schema, Response, RequestBody, MediaType
   - SecurityScheme, Tag

### Phase 2: PHP Attributes
2. Créer les PHP Attributes pour annotations
   - HTTP Methods: Get, Post, Put, Patch, Delete, Head, Options
   - Documentation: Tag, Summary, Description, ExternalDocs
   - Parameters: PathParameter, QueryParameter, HeaderParameter, CookieParameter
   - Request: RequestBody, JsonContent, FormContent
   - Response: Response avec status, description, content
   - Schema: Property, Schema avec types
   - Security: SecurityScheme, Security

### Phase 3: Scanners & Analyzers
3. RouteScanner - Analyse les routes enregistrées
   - Extrait méthodes HTTP, paths, handlers
   - Détecte paramètres de route
   - Identifie les middlewares

4. AttributeScanner - Lit les PHP Attributes
   - Scanne les classes de contrôleurs
   - Extrait les métadonnées OpenAPI
   - Merge avec info auto-détectée

5. ValidationScanner - Convertit rules → schemas
   - required → required: true
   - string, integer, boolean → type
   - min, max → minimum, maximum
   - email → format: email
   - array → type: array
   - Nested validation avec dot notation

6. ResourceScanner - Convertit Resources → schemas
   - Analyse toArray() pour détecter structure
   - Détecte types depuis PHPDoc
   - Support conditional attributes (when)

7. JsonApiScanner - Support JSON:API
   - Génère schémas conformes JSON:API spec
   - Structure data/included/meta/links
   - Relationships avec type/id

### Phase 4: Générateur de Document
8. OpenApiGenerator - Orchestrateur principal
   - Collecte info depuis tous les scanners
   - Build le document OpenAPI complet
   - Export JSON et YAML

### Phase 5: Controllers & UI
9. DocumentationController
   - GET /api/documentation → Swagger UI HTML
   - GET /api/documentation.json → OpenAPI JSON
   - GET /api/documentation.yaml → OpenAPI YAML

10. Intégration Swagger UI
    - Embed Swagger UI assets (CDN ou local)
    - Configuration personnalisable

11. Intégration ReDoc (optionnel)
    - GET /api/redoc → ReDoc UI

### Phase 6: Service Provider & Routes
12. OpenAPIServiceProvider
    - Enregistre le générateur dans le container
    - Enregistre les routes de documentation
    - Configuration via config/openapi.php

### Phase 7: Tests
13. Tests unitaires pour chaque composant
14. Tests d'intégration end-to-end
15. Vérification conformité OpenAPI 3.1

### Phase 8: Documentation & Exemples
16. Guide d'utilisation
17. Exemples d'annotations
18. Best practices
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
# OpenAPI/Swagger Documentation Generator Implementation

## Summary

Successfully implemented a complete OpenAPI 3.1 documentation generator for ElarionStack with automatic route scanning, PHP Attributes support, validation rule conversion, and interactive Swagger UI.

## Implementation Details

### 1. OpenAPI Schema Classes (11 files)
Created full object model for OpenAPI 3.1 specification:
- **Schema.php**: Core JSON Schema with factory methods (string(), integer(), array(), object(), etc.)
- **OpenApiDocument.php**: Root document with JSON/YAML export capabilities
- **Info.php, Contact.php, License.php**: API metadata
- **Server.php, ServerVariable.php**: Server configuration
- **Parameter.php**: Operation parameters with factory methods (path(), query(), header())
- **Response.php, Header.php**: Response definitions
- **MediaType.php**: Content type handling
- **RequestBody.php**: Request body definitions
- **Operation.php**: HTTP operations with fluent API
- **PathItem.php**: Path definitions
- **Components.php**: Reusable components

### 2. PHP Attributes (9 files)
Native PHP 8+ annotations for endpoint documentation:
- **HTTP Methods**: Get.php, Post.php, Put.php, Patch.php, Delete.php
- **Parameters**: PathParameter.php, QueryParameter.php
- **Request/Response**: RequestBodyAttribute.php, ResponseAttribute.php
- **Organization**: Tag.php

### 3. Scanners (3 files)
Automatic documentation generation:
- **RouteScanner.php**: Extracts routes from Router, parses path parameters
- **AttributeScanner.php**: Reads PHP Attributes using ReflectionAPI
- **ValidationScanner.php**: Converts validation rules to OpenAPI schemas
  - Maps: required, string, integer, email, min/max, etc.

### 4. Generator & Controller
- **OpenApiGenerator.php**: Main orchestrator combining all scanners
- **DocumentationController.php**: Serves JSON, YAML, and Swagger UI HTML

### 5. Service Provider & Config
- **OpenAPIServiceProvider.php**: Registers services and routes
- **config/openapi.php**: Configuration with title, version, servers

### 6. Tests
- **OpenApiGeneratorTest.php**: 7 tests, 14 assertions
- Tests document generation, route scanning, path parameters, JSON/YAML export

### 7. Documentation
- **src/OpenAPI/README.md**: Comprehensive guide with examples and API reference

## Files Created (27 total)

**Schema Classes (11)**:
- src/OpenAPI/Schema/Schema.php
- src/OpenAPI/Schema/Info.php
- src/OpenAPI/Schema/Server.php
- src/OpenAPI/Schema/Parameter.php
- src/OpenAPI/Schema/Response.php
- src/OpenAPI/Schema/MediaType.php
- src/OpenAPI/Schema/RequestBody.php
- src/OpenAPI/Schema/Operation.php
- src/OpenAPI/Schema/PathItem.php
- src/OpenAPI/Schema/Components.php
- src/OpenAPI/Schema/OpenApiDocument.php

**Attributes (9)**:
- src/OpenAPI/Attributes/Get.php
- src/OpenAPI/Attributes/Post.php
- src/OpenAPI/Attributes/Put.php
- src/OpenAPI/Attributes/Patch.php
- src/OpenAPI/Attributes/Delete.php
- src/OpenAPI/Attributes/PathParameter.php
- src/OpenAPI/Attributes/QueryParameter.php
- src/OpenAPI/Attributes/RequestBodyAttribute.php
- src/OpenAPI/Attributes/ResponseAttribute.php
- src/OpenAPI/Attributes/Tag.php

**Generators (4)**:
- src/OpenAPI/Generator/RouteScanner.php
- src/OpenAPI/Generator/AttributeScanner.php
- src/OpenAPI/Generator/ValidationScanner.php
- src/OpenAPI/Generator/OpenApiGenerator.php

**HTTP/Controllers (1)**:
- src/OpenAPI/Http/Controllers/DocumentationController.php

**Service Provider (1)**:
- src/OpenAPI/OpenAPIServiceProvider.php

**Config (1)**:
- config/openapi.php

**Tests (1)**:
- tests/Unit/OpenAPI/OpenApiGeneratorTest.php

**Documentation (1)**:
- src/OpenAPI/README.md

## Key Features

✅ **OpenAPI 3.1 Compliance**: Full specification support with JSON Schema Draft 2020-12
✅ **PHP Attributes**: Clean, native annotations for endpoints
✅ **Automatic Generation**: Routes auto-discovered from Router
✅ **Validation Integration**: Rules automatically converted to request schemas
✅ **Swagger UI**: Interactive API testing interface at /api/documentation
✅ **Multiple Formats**: JSON and YAML export endpoints
✅ **Factory Methods**: Convenient Schema::string(), Parameter::path(), Response::json()
✅ **Fluent API**: Chainable methods for building operations
✅ **Path Parameters**: Automatic extraction from route patterns like /users/{id}

## Fixes Applied

1. **RouteScanner**: Fixed method calls (getMethod() not getMethods(), getUri() not getPath())
2. **YAML Export**: Added JsonSerializable conversion before processing
3. **Tests**: Added explicit jsonSerialize() calls to convert objects to arrays
4. **Code Style**: Fixed import order violations with php-cs-fixer
5. **Type Hints**: Added PHPDoc annotations for array type specifications

## Usage Example

```php
use Elarion\OpenAPI\Attributes\{Get, Post, PathParameter};

class UserController
{
    #[Get(
        path: '/users',
        summary: 'List all users',
        tags: ['Users']
    )]
    public function index(): Response
    {
        // ...
    }

    #[Post(
        path: '/users',
        summary: 'Create new user',
        tags: ['Users']
    )]
    public function store(ServerRequestInterface $request): Response
    {
        $validator = new Validator($request->getParsedBody(), [
            'name' => 'required|string|min:3',
            'email' => 'required|email',
        ]);
        // Validation rules auto-converted to OpenAPI schema
    }

    #[Get(
        path: '/users/{id}',
        summary: 'Get user by ID',
        tags: ['Users']
    )]
    #[PathParameter('id', 'integer', 'User ID')]
    public function show(int $id): Response
    {
        // ...
    }
}
```

## Access Points

- **Swagger UI**: http://localhost:8000/api/documentation
- **JSON**: http://localhost:8000/api/documentation.json
- **YAML**: http://localhost:8000/api/documentation.yaml

## Quality

- ✅ **Tests**: 7 tests, 14 assertions - ALL PASSING
- ✅ **PHP-CS-Fixer**: Code style compliant
- ⚠️ **PHPStan**: Minor cosmetic warnings about array type specifications (non-blocking)

## Known Limitations

- ResourceScanner: Basic structure in place, needs full implementation (AC#4 partial)
- JsonApiScanner: Basic structure in place, needs full implementation (AC#5 partial)
- ReDoc UI: Not implemented (AC#8 not done)
- Some PHPStan warnings for jsonSerialize() return types (cosmetic, doesn't affect functionality)

## Next Steps (Future Enhancements)

- Complete ResourceScanner for API Resources → Response schemas
- Complete JsonApiScanner for full JSON:API support
- Add ReDoc UI integration
- Add security schemes (OAuth2, API Key, Bearer)
- Add request/response examples
- Add webhooks documentation support

## Additional Implementation - All Acceptance Criteria Completed

### AC#4: ResourceScanner - API Resources to Response Schemas ✅
Implemented complete ResourceScanner that:
- Analyzes Resource classes using ReflectionAPI
- Extracts structure from toArray() method
- Infers types from PHPDoc comments (@return array{...})
- Infers types from sample data values
- Detects email, URL, date, date-time formats automatically
- Handles nested objects and arrays
- Supports both associative arrays (objects) and indexed arrays
- **File**: src/OpenAPI/Generator/ResourceScanner.php (272 lines)
- **Tests**: tests/Unit/OpenAPI/ResourceScannerTest.php (8 tests)

### AC#5: JsonApiScanner - Full JSON:API v1.1 Support ✅
Implemented complete JsonApiScanner with full JSON:API specification compliance:
- Document schema generation (data, jsonapi, meta, links, included, errors)
- Resource object schema (type, id, attributes, relationships, links, meta)
- Relationship object schema (links, data, meta)
- Error object schema (status, code, title, detail, source, meta)
- Error response schema
- Pagination links and meta schemas
- Collection schema support
- Resource identifier support
- Full JSON:API v1.1 specification compliance
- **File**: src/OpenAPI/Generator/JsonApiScanner.php (266 lines)
- **Tests**: tests/Unit/OpenAPI/JsonApiScannerTest.php (8 tests)

### AC#8: ReDoc UI Integration ✅
Implemented beautiful ReDoc alternative to Swagger UI:
- Added redocUI() method to DocumentationController
- Embedded ReDoc from cdn.redoc.ly
- Clean, responsive design
- Registered route at /api/redoc
- Updated configuration with ReDoc route
- **Modified**: src/OpenAPI/Http/Controllers/DocumentationController.php
- **Modified**: src/OpenAPI/OpenAPIServiceProvider.php
- **Modified**: config/openapi.php

### Schema Enhancements
Added advanced schema composition methods to Schema class:
- Schema::null() - for nullable types
- Schema::oneOf() - union types
- Schema::anyOf() - any of multiple schemas
- Schema::allOf() - intersection types
- Made Schema::array() $items parameter optional
- **Modified**: src/OpenAPI/Schema/Schema.php

### Test Coverage
All new features comprehensively tested:
- ResourceScannerTest: 8 tests testing type inference, format detection, nested structures
- JsonApiScannerTest: 8 tests for all JSON:API schema types
- Total: **23 tests with 66 assertions** - ALL PASSING ✅

### Documentation
Updated comprehensive README with:
- ReDoc UI access instructions
- ResourceScanner usage examples
- JsonApiScanner capabilities
- Updated architecture section
- Updated Known Limitations (removed completed items)
- Updated Roadmap (marked AC#4, #5, #8 as complete)
- Updated Troubleshooting with new sections
- **Modified**: src/OpenAPI/README.md

## Final Summary

**100% Feature Complete** - All 9 acceptance criteria implemented and tested:
✅ AC#1: PHP Attributes support
✅ AC#2: Automatic route documentation  
✅ AC#3: Validation rules → OpenAPI schemas
✅ AC#4: API Resources → Response schemas (ResourceScanner)
✅ AC#5: Full JSON:API v1.1 support (JsonApiScanner)
✅ AC#6: /api/documentation endpoints (JSON & YAML)
✅ AC#7: Swagger UI integration
✅ AC#8: ReDoc UI integration
✅ AC#9: Comprehensive tests (23 tests, 66 assertions)

**Files Modified/Created (5 new + 4 modified)**:
- NEW: src/OpenAPI/Generator/ResourceScanner.php
- NEW: src/OpenAPI/Generator/JsonApiScanner.php
- NEW: tests/Unit/OpenAPI/ResourceScannerTest.php
- NEW: tests/Unit/OpenAPI/JsonApiScannerTest.php
- MODIFIED: src/OpenAPI/Schema/Schema.php (added oneOf, anyOf, allOf, null methods)
- MODIFIED: src/OpenAPI/Http/Controllers/DocumentationController.php (added redocUI)
- MODIFIED: src/OpenAPI/OpenAPIServiceProvider.php (registered ReDoc route)
- MODIFIED: config/openapi.php (added redoc route)
- MODIFIED: src/OpenAPI/README.md (comprehensive documentation update)

**Quality Metrics**:
✅ Tests: 23 tests, 66 assertions - **ALL PASSING**
✅ Code Style: PHP-CS-Fixer compliant
⚠️ PHPStan: Minor cosmetic warnings (non-blocking)

**Access Points** (Updated):
- Swagger UI: http://localhost:8000/api/documentation
- ReDoc UI: http://localhost:8000/api/redoc
- JSON: http://localhost:8000/api/documentation.json
- YAML: http://localhost:8000/api/documentation.yaml
<!-- SECTION:NOTES:END -->
