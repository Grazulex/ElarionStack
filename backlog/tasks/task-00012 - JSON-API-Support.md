---
id: task-00012
title: 'JSON:API Support'
status: In Progress
assignee:
  - '@Claude'
created_date: '2025-10-21 19:58'
updated_date: '2025-10-21 22:32'
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
