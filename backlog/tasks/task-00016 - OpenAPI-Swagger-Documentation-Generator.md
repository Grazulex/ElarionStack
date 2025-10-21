---
id: task-00016
title: OpenAPI/Swagger Documentation Generator
status: In Progress
assignee:
  - '@Claude'
created_date: '2025-10-21 22:48'
updated_date: '2025-10-21 23:08'
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
- [ ] #4 Conversion automatique des API Resources en Response schemas
- [ ] #5 Support spécifique pour JSON:API avec schémas conformes
- [x] #6 Endpoint /api/documentation avec export JSON et YAML
- [x] #7 UI Swagger intégrée pour tester l'API interactivement
- [ ] #8 Support ReDoc pour documentation élégante
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
