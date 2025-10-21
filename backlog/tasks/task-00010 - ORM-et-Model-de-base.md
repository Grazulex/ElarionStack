---
id: task-00010
title: ORM et Model de base
status: In Progress
assignee:
  - '@claude'
created_date: '2025-10-21 19:57'
updated_date: '2025-10-21 21:42'
labels:
  - database
  - orm
  - model
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Créer un système ORM simple avec une classe Model de base pour mapper les tables de base de données à des objets PHP, inspiré du pattern Active Record.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Une classe Model abstraite existe avec méthodes find(), all(), save(), delete()
- [ ] #2 Les models peuvent définir leur table, primary key, fillable
- [ ] #3 Les models utilisent le Query Builder en interne
- [ ] #4 Support des timestamps (created_at, updated_at) automatiques
- [ ] #5 Les attributs du model sont accessibles comme propriétés
- [ ] #6 Les tests démontrent CRUD complet sur un model
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
1. Analyze existing Query Builder and Database components
2. Design Model architecture (Active Record pattern)
3. Create abstract Model base class with:
   - Configuration properties (table, primaryKey, fillable, timestamps)
   - Query Builder integration
   - Magic property access (__get, __set, __isset, __unset)
4. Implement query methods:
   - find($id): Find by primary key
   - all(): Get all records
   - where(...): Delegate to Query Builder
5. Implement persistence methods:
   - save(): Insert or update based on existence
   - delete(): Delete current record
   - fill($attributes): Mass assignment with fillable guard
6. Implement timestamps support:
   - Auto-set created_at on insert
   - Auto-set updated_at on insert/update
   - Make timestamps optional via $timestamps property
7. Implement attribute management:
   - $attributes array to store data
   - $original array to track changes
   - isDirty(), getChanges() methods
   - toArray(), toJson() methods
8. Create comprehensive tests:
   - Model configuration tests
   - CRUD operations (Create, Read, Update, Delete)
   - Timestamps functionality
   - Magic property access
   - Fillable guard protection
   - Query delegation tests
9. Run quality checks (PHPStan, PHP-CS-Fixer, tests)
<!-- SECTION:PLAN:END -->
