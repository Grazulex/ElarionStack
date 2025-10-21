---
id: task-00009
title: Query Builder
status: In Progress
assignee:
  - '@claude'
created_date: '2025-10-21 19:57'
updated_date: '2025-10-21 21:30'
labels:
  - database
  - query-builder
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Implémenter un Query Builder fluent pour construire et exécuter des requêtes SQL de manière programmatique et sécurisée, sans écrire de SQL brut.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Le builder supporte SELECT, INSERT, UPDATE, DELETE
- [ ] #2 Le builder supporte WHERE, JOIN, ORDER BY, GROUP BY, LIMIT
- [ ] #3 Toutes les valeurs utilisent des paramètres préparés (protection SQL injection)
- [ ] #4 Le builder peut retourner des tableaux ou des objets
- [ ] #5 API fluente chainable (ex: ->where()->orderBy()->get())
- [ ] #6 Les tests couvrent tous les types de requêtes et clauses
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
1. Create QueryBuilder base class with fluent interface
2. Implement SELECT builder (columns, from, distinct)
3. Implement WHERE clauses (where, orWhere, whereIn, whereNull, whereBetween)
4. Implement JOIN clauses (join, leftJoin, rightJoin, crossJoin)
5. Implement ORDER BY, GROUP BY, HAVING, LIMIT, OFFSET
6. Implement INSERT builder (insert, insertGetId)
7. Implement UPDATE builder (update, increment, decrement)
8. Implement DELETE builder
9. Add parameter binding system (prepared statements)
10. Add result fetching methods (get, first, find, count, pluck)
11. Support for both array and object results
12. Create comprehensive tests for all query types
13. Run quality checks (PHPStan level 9, PHP-CS-Fixer, tests)
<!-- SECTION:PLAN:END -->
