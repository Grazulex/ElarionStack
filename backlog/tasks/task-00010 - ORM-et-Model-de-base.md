---
id: task-00010
title: ORM et Model de base
status: In Progress
assignee:
  - '@claude'
created_date: '2025-10-21 19:57'
updated_date: '2025-10-21 21:41'
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
