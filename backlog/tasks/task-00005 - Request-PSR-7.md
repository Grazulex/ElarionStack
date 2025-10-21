---
id: task-00005
title: Request PSR-7
status: To Do
assignee: []
created_date: '2025-10-21 19:57'
labels:
  - http
  - psr-7
dependencies: []
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Implémenter une classe ServerRequest conforme PSR-7 pour représenter les requêtes HTTP entrantes de manière immutable et standardisée.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 La classe implémente PSR-7 ServerRequestInterface
- [ ] #2 On peut accéder aux query parameters, body, headers
- [ ] #3 On peut accéder aux cookies et uploaded files
- [ ] #4 Les objets Request sont immutables
- [ ] #5 La classe peut être créée depuis les superglobales PHP
- [ ] #6 Les tests vérifient la conformité PSR-7
<!-- AC:END -->
