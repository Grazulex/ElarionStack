---
id: task-00008
title: Database Connection Manager
status: In Progress
assignee:
  - '@claude'
created_date: '2025-10-21 19:57'
updated_date: '2025-10-21 21:16'
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
- [x] #1 Le gestionnaire peut créer des connexions PDO depuis la configuration
- [x] #2 Support de MySQL, PostgreSQL, SQLite
- [x] #3 Les connexions sont lazy-loaded
- [x] #4 Support de plusieurs connexions nommées
- [x] #5 Les erreurs de connexion sont gérées avec des exceptions claires
- [x] #6 Les tests utilisent SQLite en mémoire
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
1. Create DatabaseConfig value object for connection configuration
2. Create ConnectionFactory to build PDO instances from config
3. Create ConnectionManager with lazy-loading and named connections
4. Support MySQL, PostgreSQL, SQLite drivers
5. Add comprehensive error handling with custom exceptions
6. Create tests using SQLite in-memory database
7. Integration with existing Container (PSR-11)
8. Run quality checks (PHPStan level 9, PHP-CS-Fixer, tests)
<!-- SECTION:PLAN:END -->
