---
id: task-00012
title: 'JSON:API Support'
status: Done
assignee:
  - '@Claude'
created_date: '2025-10-21 19:58'
updated_date: '2025-10-21 22:33'
labels:
  - api
  - json-api
  - standard
dependencies: []
priority: low
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Ajouter le support de la spécification JSON:API pour standardiser le format des réponses API avec structure data/included/meta/links conforme à la spec.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Les réponses suivent la structure JSON:API (data, included, meta, links)
- [x] #2 Support du type et id pour chaque resource
- [x] #3 Support des relationships et included resources
- [x] #4 Support de la pagination avec links
- [x] #5 Les erreurs suivent le format JSON:API
- [x] #6 Les tests vérifient la conformité avec la spec JSON:API
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
1. Analyser la spécification JSON:API (structure data/included/meta/links/errors)
2. Créer JsonApiResource class pour resources individuelles (type, id, attributes, relationships)
3. Créer JsonApiCollection class pour collections avec pagination
4. Implémenter support des relationships (links, data avec type/id)
5. Implémenter included resources (compound documents)
6. Créer JsonApiErrorResponse pour format d'erreurs conforme
7. Ajouter formatage de pagination avec links (first, last, prev, next)
8. Écrire tests de conformité avec la spec JSON:API
9. Vérifier PHPStan level 8 et PHP-CS-Fixer
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implemented full JSON:API v1.1 specification support with three core classes:

**JsonApiResource** (abstract base class):
- Enforces type() and id() methods for resource identification
- Supports attributes, relationships, links, and meta per spec
- Implements compound documents with included resources and automatic deduplication
- Provides helper methods: relationship(), resourceIdentifier(), getIncluded()
- Sets correct Content-Type: application/vnd.api+json

**JsonApiCollection**:
- Extends ResourceCollection for JSON:API formatted collections
- Implements withJsonApiPagination() with first/last/prev/next links
- Builds pagination URLs using page[number] and page[size] parameters per spec
- Collects and deduplicates included resources from all items
- Adds pagination meta (total, count, per_page, current_page, total_pages)

**JsonApiErrorResponse**:
- Formats errors with status, title, detail, code, source, and meta
- Provides factory methods: validationErrors(), notFound(), unauthorized(), forbidden(), serverError()
- Uses first error status as HTTP status code
- Maintains jsonapi version object in all responses

**Quality improvements**:
- Fixed Response::json() to preserve custom Content-Type headers when provided
- Added getIncluded() getter to properly expose included resources
- Changed static to self in factory methods for PHPStan compliance
- Added proper type handling for iterable resources in count operations
- Added assertion for json_encode with JSON_THROW_ON_ERROR flag

**Testing**:
- 21 comprehensive tests covering all JSON:API features
- Tests verify spec compliance for resources, collections, pagination, errors
- All tests passing (21/21, 60 assertions)
- PHPStan level 8: ✓ (0 errors)
- PHP-CS-Fixer: ✓ (0 issues)

**Files modified**:
- src/Http/Message/Response.php (Content-Type header fix)

**Files created**:
- src/Http/Resources/JsonApi/JsonApiResource.php (~295 lines)
- src/Http/Resources/JsonApi/JsonApiCollection.php (~220 lines)
- src/Http/Resources/JsonApi/JsonApiErrorResponse.php (~250 lines)
- tests/Http/Resources/JsonApi/JsonApiTest.php (~410 lines)
<!-- SECTION:NOTES:END -->
