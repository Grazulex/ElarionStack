---
id: task-00008
title: Database Connection Manager
status: In Progress
assignee:
  - '@claude'
created_date: '2025-10-21 19:57'
updated_date: '2025-10-21 21:11'
labels:
  - database
  - pdo
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Créer un gestionnaire de connexions PDO pour supporter plusieurs connexions bases de données avec configuration centralisée. Fondation pour le Query Builder et l'ORM.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Le gestionnaire peut créer des connexions PDO depuis la configuration
- [ ] #2 Support de MySQL, PostgreSQL, SQLite
- [ ] #3 Les connexions sont lazy-loaded
- [ ] #4 Support de plusieurs connexions nommées
- [ ] #5 Les erreurs de connexion sont gérées avec des exceptions claires
- [ ] #6 Les tests utilisent SQLite en mémoire
<!-- AC:END -->
