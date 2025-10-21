---
id: task-00011
title: API Resources
status: In Progress
assignee:
  - '@claude'
created_date: '2025-10-21 19:58'
updated_date: '2025-10-21 21:57'
labels:
  - api
  - resources
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Implémenter un système de transformation de données pour convertir les models en réponses API structurées. Les Resources permettent de contrôler exactement quelles données sont exposées par l'API.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Une classe Resource abstraite existe avec méthode toArray()
- [x] #2 Les resources peuvent transformer des models individuels
- [x] #3 Les resources peuvent transformer des collections
- [x] #4 Support des relations imbriquées (nested resources)
- [x] #5 Les resources peuvent inclure des métadonnées
- [x] #6 Les tests démontrent la transformation de models en JSON
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
1. Design Resource architecture (transformation layer pattern)
2. Create abstract Resource base class with:
   - toArray($request) method for transformation
   - toResponse($request) for HTTP Response
   - Static collection() for handling arrays
   - Static make() factory method
3. Implement single resource transformation:
   - Accept Model instance
   - Transform to array structure
   - Control which attributes to expose
4. Implement collection transformation:
   - Accept array of Models
   - Transform each item
   - Wrap in data envelope
5. Implement nested resources:
   - Support loading related resources
   - Conditional includes
   - whenLoaded() helper
6. Implement metadata support:
   - with() method for extra data
   - additional() for top-level meta
   - Pagination meta helpers
7. Implement conditional attributes:
   - when() for conditional inclusion
   - merge() for merging arrays
   - Attribute helpers
8. Create comprehensive tests:
   - Single resource transformation
   - Collection transformation
   - Nested resources
   - Metadata and pagination
   - Conditional attributes
9. Run quality checks (PHPStan, PHP-CS-Fixer, tests)
<!-- SECTION:PLAN:END -->
