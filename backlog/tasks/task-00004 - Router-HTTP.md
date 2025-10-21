---
id: task-00004
title: Router HTTP
status: In Progress
assignee:
  - '@ai-assistant'
created_date: '2025-10-21 19:57'
updated_date: '2025-10-21 20:48'
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
- [x] #1 Le router peut enregistrer des routes GET, POST, PUT, PATCH, DELETE
- [x] #2 Le router peut matcher une requête à une route enregistrée
- [x] #3 Le router supporte les paramètres de route (ex: /users/{id})
- [x] #4 Le router supporte les groupes de routes avec préfixes
- [x] #5 Le router peut associer des middlewares aux routes
- [x] #6 Les tests couvrent tous les verbes HTTP et scénarios de matching
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
## Phase 1: Core Interfaces (SOLID Foundation)

1. Create RouteInterface
   - getMethod(): string
   - getUri(): string
   - getHandler(): callable|array
   - getMiddleware(): array
   - getName(): ?string

2. Create RouteCollectorInterface
   - get(string $uri, $handler): RouteInterface
   - post(string $uri, $handler): RouteInterface
   - put(string $uri, $handler): RouteInterface
   - patch(string $uri, $handler): RouteInterface
   - delete(string $uri, $handler): RouteInterface
   - addRoute(string $method, string $uri, $handler): RouteInterface
   - group(array $attributes, callable $callback): void

3. Create RouteDispatcherInterface
   - dispatch(string $method, string $uri): RouteMatch

## Phase 2: Value Objects and Enums (PHP 8.5)

4. Create HttpMethod enum
   - GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD
   - case insensitive matching
   - toString() support

5. Create RouteMatch value object
   - status: int (FOUND, NOT_FOUND, METHOD_NOT_ALLOWED)
   - handler: callable|array
   - params: array
   - middleware: array
   - allowedMethods: array (for 405 responses)

6. Create Route value object (readonly)
   - Immutable route representation
   - Fluent API for middleware/name

## Phase 3: Route Groups and Attributes

7. Create RouteGroup class
   - Manages prefix, middleware, namespace
   - Nested groups support
   - Attribute inheritance

8. Create RouteAttributeStack
   - Manages group attribute inheritance
   - Merges prefixes, middleware arrays

## Phase 4: FastRoute Integration

9. Implement FastRouteCollector
   - Wraps nikic/fast-route
   - Implements RouteCollectorInterface
   - Builds route collection

10. Implement FastRouteDispatcher
    - Implements RouteDispatcherInterface
    - Uses FastRoute for matching
    - Returns RouteMatch objects

## Phase 5: Router Facade and Manager

11. Create Router class (main API)
    - Combines collector + dispatcher
    - Provides clean fluent API
    - Registers named routes
    - Route URL generation

12. Add route() helper function
    - route(): Router
    - route(string $name, array $params): string (URL generation)

## Phase 6: Service Provider Integration

13. Create RoutingServiceProvider
    - Register Router in container
    - Bind interfaces to implementations
    - Cache route definitions

## Phase 7: Comprehensive Testing

14. Unit tests for each component
    - HttpMethod enum tests
    - Route value object tests
    - RouteMatch tests
    - RouteGroup tests
    - FastRouteCollector tests
    - FastRouteDispatcher tests
    - Router integration tests

15. Test coverage for edge cases
    - Route parameter extraction
    - Nested groups
    - Middleware inheritance
    - 404 handling
    - 405 (Method Not Allowed)
    - Optional parameters
    - Regex constraints
<!-- SECTION:PLAN:END -->
