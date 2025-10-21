---
id: task-00003
title: Configuration Management
status: In Progress
assignee:
  - '@ai-assistant'
created_date: '2025-10-21 19:57'
updated_date: '2025-10-21 20:23'
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
## Implementation Plan

### Phase 1: Core Contracts & Value Objects
1. Create namespace structure `Elarion\Config\Contracts`
2. Define interfaces:
   - ConfigRepositoryInterface (get, set, has, all, merge)
   - ConfigLoaderInterface (load, supports)
   - ConfigCacheInterface (get, put, forget, flush, remember)
3. Create Environment enum with values (Local, Development, Testing, Staging, Production)
4. Implement ConfigValue readonly class for immutable configuration values

### Phase 2: Dot Notation Parser
1. Create DotNotationParser class in `Elarion\Config`
2. Implement methods:
   - get(): Recursive navigation through arrays using dot notation
   - set(): Create nested arrays as needed
   - has(): Check existence of nested keys
   - forget(): Remove nested keys
3. Handle edge cases (null values, non-array parents, missing keys)
4. Write comprehensive unit tests for all scenarios

### Phase 3: Configuration Repository
1. Implement ConfigRepository with:
   - In-memory storage array
   - Integration with DotNotationParser
   - Cache integration for production
   - Environment-aware loading
2. Support merging configurations (base + environment-specific)
3. Implement lazy loading with first-class callables
4. Add type safety with proper type hints

### Phase 4: File Loader Implementation
1. Create PhpFileLoader implementing ConfigLoaderInterface
2. Features:
   - Load PHP files returning arrays
   - Support environment-specific files (e.g., app.production.php)
   - Validate configuration structure
   - Graceful error handling for malformed files
3. Security: Validate file paths to prevent directory traversal

### Phase 5: Caching Layer
1. Implement FileConfigCache with:
   - Serialize configurations to optimized PHP files
   - TTL support for cache expiration
   - Cache warming for production deployment
   - Atomic write operations to prevent corruption
2. Cache key strategy based on file modification times
3. Clear cache command for deployments

### Phase 6: Configuration Manager (Facade)
1. Create ConfigManager as main entry point
2. Features:
   - Register multiple loaders (extensible architecture)
   - Load entire directories recursively
   - Delegate to repository for storage
   - Provide fluent API for configuration access
3. Support loader priority/ordering

### Phase 7: Service Provider Integration
1. Create ConfigServiceProvider extending base ServiceProvider
2. Register bindings:
   - Singleton for ConfigRepositoryInterface
   - Singleton for ConfigManager
   - Conditional cache binding (production only)
3. Boot method: Auto-load config directory
4. Add config() helper function to helpers.php

### Phase 8: Testing Strategy
1. Unit tests for each component:
   - DotNotationParser: All parsing scenarios
   - ConfigRepository: CRUD operations
   - PhpFileLoader: File loading and validation
   - FileConfigCache: Cache operations
2. Integration tests:
   - Full configuration loading workflow
   - Environment-specific overrides
   - Cache vs non-cache performance
3. Benchmarks: Measure performance impact of caching

### Technical Decisions
- Use PHP 8.5 readonly classes for immutability
- Leverage constructor property promotion throughout
- Use enums for fixed value sets (Environment)
- Implement union types for flexible returns (mixed|null)
- Apply match expressions for environment logic
- Use attributes for future extensibility
<!-- SECTION:PLAN:END -->
