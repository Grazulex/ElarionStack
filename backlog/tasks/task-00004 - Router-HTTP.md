---
id: task-00004
title: Router HTTP
status: To Do
assignee: []
created_date: '2025-10-21 19:57'
labels:
  - http
  - routing
dependencies: []
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Implémenter le système de routing HTTP en utilisant FastRoute pour mapper les URLs aux contrôleurs. Le router est essentiel pour gérer les requêtes entrantes et diriger le flux de l'application.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Le router peut enregistrer des routes GET, POST, PUT, PATCH, DELETE
- [ ] #2 Le router peut matcher une requête à une route enregistrée
- [ ] #3 Le router supporte les paramètres de route (ex: /users/{id})
- [ ] #4 Le router supporte les groupes de routes avec préfixes
- [ ] #5 Le router peut associer des middlewares aux routes
- [ ] #6 Les tests couvrent tous les verbes HTTP et scénarios de matching
<!-- AC:END -->
