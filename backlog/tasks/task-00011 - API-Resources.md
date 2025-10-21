---
id: task-00011
title: API Resources
status: In Progress
assignee:
  - '@claude'
created_date: '2025-10-21 19:58'
updated_date: '2025-10-21 21:52'
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
- [ ] #1 Une classe Resource abstraite existe avec méthode toArray()
- [ ] #2 Les resources peuvent transformer des models individuels
- [ ] #3 Les resources peuvent transformer des collections
- [ ] #4 Support des relations imbriquées (nested resources)
- [ ] #5 Les resources peuvent inclure des métadonnées
- [ ] #6 Les tests démontrent la transformation de models en JSON
<!-- AC:END -->
