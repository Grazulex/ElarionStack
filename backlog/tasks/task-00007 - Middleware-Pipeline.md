---
id: task-00007
title: Middleware Pipeline
status: In Progress
assignee:
  - '@claude'
created_date: '2025-10-21 19:57'
updated_date: '2025-10-21 21:09'
labels:
  - http
  - middleware
  - psr-15
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Créer un système de pipeline pour exécuter des middlewares PSR-15 autour du traitement des requêtes. Les middlewares permettent d'ajouter des fonctionnalités transversales comme l'authentification, le logging, etc.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Le pipeline implémente PSR-15 RequestHandlerInterface
- [x] #2 Les middlewares sont exécutés dans l'ordre d'enregistrement
- [x] #3 Un middleware peut court-circuiter la chaîne en retournant une réponse
- [x] #4 Les middlewares peuvent modifier request et response
- [x] #5 Le pipeline peut être utilisé avec le router
- [x] #6 Les tests démontrent l'exécution séquentielle et le court-circuit
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
1. Check PSR-15 dependency (psr/http-server-middleware) in composer.json
2. Create MiddlewarePipeline class implementing RequestHandlerInterface
3. Implement middleware stack with FIFO execution order
4. Add ability to short-circuit by returning Response early
5. Create tests for sequential execution, short-circuit, and request/response modification
6. Integration point for Router to use pipeline
7. Run quality checks (PHPStan level 9, PHP-CS-Fixer, tests)
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implemented PSR-15 middleware pipeline system with full router integration.

## Components Delivered:

### Core Pipeline (MiddlewarePipeline)
- PSR-15 RequestHandlerInterface implementation
- FIFO execution order for middlewares
- Built-in short-circuit support
- Recursive handler chain construction
- Fluent interface (pipe(), setFallbackHandler())

### Router Integration (RouteMiddlewareExecutor)
- Executes routes through their middleware stack
- Resolves middlewares from container or instantiates directly
- Supports callable, string (class name), and MiddlewareInterface
- Adds route parameters as request attributes
- Handles both callable and array [Controller, method] handlers
- Full PSR-11 container integration

### Utilities (CallableMiddleware)
- Adapter for wrapping closures as PSR-15 MiddlewareInterface
- Enables using simple callables as middlewares

## Testing:
- 19 comprehensive tests, 29 assertions - all passing
- Tests cover: FIFO execution, short-circuit, request/response modification, route integration
- Full test suite: 137 tests, 259 assertions - all passing

## Quality:
- PHPStan level 9: clean (0 errors)
- PHP-CS-Fixer: clean (2 files formatted)
- Full PSR-15 compliance
<!-- SECTION:NOTES:END -->
