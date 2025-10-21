---
id: task-00009
title: Query Builder
status: To Do
assignee: []
created_date: '2025-10-21 19:57'
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
