---
id: task-00003
title: Configuration Management
status: In Progress
assignee:
  - '@ai-assistant'
created_date: '2025-10-21 19:57'
updated_date: '2025-10-21 20:24'
labels:
  - core
  - config
dependencies: []
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Implémenter un système de gestion de configuration pour charger et accéder aux paramètres de l'application depuis des fichiers de configuration. Permet la séparation entre code et configuration.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Le système peut charger des fichiers de configuration PHP depuis config/
- [ ] #2 On peut accéder aux valeurs avec notation point (ex: 'app.name')
- [ ] #3 Le système supporte des valeurs par défaut
- [ ] #4 Les configurations sont cachées en production
- [ ] #5 Les tests vérifient le chargement et l'accès aux configurations
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
## Phase 1: Core Interfaces and Contracts

1. Create ConfigRepositoryInterface
   - get(string $key, mixed $default = null): mixed
   - has(string $key): bool
   - set(string $key, mixed $value): void
   - all(): array

2. Create ConfigLoaderInterface
   - load(string $path): array
   - supports(string $path): bool

3. Create ConfigCacheInterface
   - has(): bool
   - get(): array
   - put(array $config): void
   - clear(): void

## Phase 2: Utilities and Value Objects

4. Create Environment enum (Development, Testing, Production)
   - With helper methods for environment detection

5. Create DotNotationParser
   - parse(string $key): array - Split "app.name" to ["app", "name"]
   - get(array $data, string $key, mixed $default): mixed
   - set(array &$data, string $key, mixed $value): void

6. Create ConfigValue readonly class (optional)
   - Immutable wrapper for config values with type safety

## Phase 3: Configuration Loading

7. Implement PhpFileLoader
   - Loads PHP files that return arrays
   - Validates file format
   - Error handling for missing/invalid files

8. Create ConfigRepository
   - Stores loaded configuration
   - Implements dot notation via DotNotationParser
   - Thread-safe access to config data

## Phase 4: Caching Implementation

9. Implement FileConfigCache
   - Compiles all configs into single cached file
   - Opcache-friendly format
   - Cache invalidation mechanism
   - Path: storage/framework/config.php

## Phase 5: Manager and Facade

10. Create ConfigManager
    - Orchestrates loader, repository, cache
    - Loads configs on first access (lazy)
    - Switches between cached/uncached based on environment
    - Provides clean API

## Phase 6: Service Provider Integration

11. Create ConfigServiceProvider
    - Registers ConfigManager in container
    - Loads configurations on boot
    - Publishes config files
    - Binds helper functions

## Phase 7: Helper Functions

12. Add global config() helper
    - config(): ConfigManager
    - config(string $key): mixed
    - config(string $key, mixed $default): mixed
    - config(array $set): void - mass assignment

## Phase 8: Comprehensive Testing

13. Unit tests for each component
    - DotNotationParser tests
    - PhpFileLoader tests
    - ConfigRepository tests
    - FileConfigCache tests
    - ConfigManager integration tests

14. Test coverage for edge cases
    - Missing files
    - Invalid formats
    - Nested arrays
    - Cache invalidation
    - Concurrent access
<!-- SECTION:PLAN:END -->
