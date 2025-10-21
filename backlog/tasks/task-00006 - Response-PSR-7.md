---
id: task-00006
title: Response PSR-7
status: In Progress
assignee:
  - '@ai-assistant'
created_date: '2025-10-21 19:57'
updated_date: '2025-10-21 21:00'
labels:
  - http
  - psr-7
dependencies: []
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Implémenter une classe Response conforme PSR-7 pour créer et manipuler les réponses HTTP de manière standardisée et immutable.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 La classe implémente PSR-7 ResponseInterface
- [x] #2 On peut créer des réponses avec status code, headers, body
- [x] #3 On peut créer facilement des réponses JSON
- [x] #4 Les objets Response sont immutables
- [ ] #5 La classe peut émettre la réponse au client
- [x] #6 Les tests vérifient la conformité PSR-7
<!-- AC:END -->
