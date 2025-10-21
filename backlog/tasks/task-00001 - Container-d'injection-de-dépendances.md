---
id: task-00001
title: Container d'injection de dépendances
status: In Progress
assignee:
  - '@claude'
created_date: '2025-10-21 19:57'
updated_date: '2025-10-21 20:17'
labels:
  - core
  - psr-11
  - foundation
dependencies: []
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Implémenter un conteneur d'injection de dépendances conforme PSR-11 pour gérer les dépendances de l'application. C'est la fondation du framework qui permettra l'auto-wiring et la résolution automatique des dépendances.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Le conteneur implémente PSR-11 ContainerInterface
- [x] #2 Le conteneur peut résoudre des classes avec auto-wiring
- [x] #3 Le conteneur peut lier des interfaces à des implémentations
- [x] #4 Le conteneur peut résoudre des dépendances récursives
- [x] #5 Le conteneur lance des exceptions appropriées pour les dépendances non trouvées
- [x] #6 Les tests unitaires couvrent tous les cas d'usage principaux
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
## Phase 1: Core Interfaces and Contracts (Foundation)

1. Create Contract Interfaces
   - Define BindingRegistryInterface with methods: bind(), has(), get(), all()
   - Define ResolverInterface with resolve() and canResolve() methods
   - Define AutoWiringInterface for auto-wiring capabilities
   - Define BindingInterface for binding abstraction
   - Ensure all interfaces follow ISP (focused, client-specific)

2. Create PSR-11 Exception Implementations
   - Implement NotFoundException extending Psr\Container\NotFoundExceptionInterface
   - Implement ContainerException extending Psr\Container\ContainerExceptionInterface
   - Create CircularDependencyException for recursive resolution issues
   - Create ExceptionFactory with static factory methods (SRP)

## Phase 2: Binding System Implementation

3. Implement Binding Types using PHP 8.5 Features
   - Create BindingType enum: Concrete, Singleton, Factory, Alias
   - Implement AbstractBinding with readonly properties and property hooks
   - Create ConcreteBinding for class bindings with lazy resolution
   - Create SingletonBinding with instance caching using asymmetric visibility
   - Create FactoryBinding using first-class callables
   - Create AliasBinding for interface-to-implementation mapping

4. Implement Binding Registry
   - Create BindingRegistry implementing BindingRegistryInterface
   - Use readonly class with private(set) public properties for thread safety
   - Implement binding storage with type-safe collections
   - Add binding validation using property hooks
   - Create ResolvedInstancesRegistry for singleton management

## Phase 3: Resolution System

5. Implement Reflection-based Resolver
   - Create AbstractResolver with common resolution logic
   - Implement ReflectionResolver for auto-wiring via reflection
   - Use ReflectionClass and ReflectionParameter for dependency analysis
   - Implement ParameterResolver for constructor parameter resolution
   - Handle union/intersection types properly
   - Track resolution path for circular dependency detection

6. Implement Resolution Strategies
   - Create resolution strategy enum: AutoWire, Explicit, Factory
   - Implement strategy pattern for different resolution approaches
   - Add CachedResolver as decorator for performance optimization
   - Use WeakMap for caching to prevent memory leaks

## Phase 4: Container Implementation

7. Implement Main Container Class
   - Create Container implementing Psr\Container\ContainerInterface
   - Inject dependencies: BindingRegistry, ResolverInterface
   - Implement get() with proper exception handling
   - Implement has() for checking bindings
   - Add bind() method with fluent interface
   - Add singleton(), factory(), and alias() convenience methods
   - Use readonly promotion for immutable dependencies

8. Add Auto-wiring Capabilities
   - Implement make() method for on-demand resolution
   - Add contextual binding support
   - Implement parameter override capabilities
   - Handle variadic parameters and default values
   - Support nullable types and optional dependencies

## Phase 5: Advanced Features

9. Implement Service Provider System
   - Create ServiceProviderInterface with register() and boot() methods
   - Implement abstract ServiceProvider base class
   - Add deferred service provider support for lazy loading
   - Implement provider registration in Container

10. Add Method Injection Support
    - Create MethodInjector for calling methods with resolved dependencies
    - Support for @Inject attributes/annotations
    - Handle method parameter resolution like constructors

## Phase 6: Testing and Validation

11. Create Comprehensive Test Suite
    - Unit tests for each binding type
    - Integration tests for complex resolution scenarios
    - Test circular dependency detection
    - Test interface-to-implementation binding
    - Test singleton behavior
    - Test factory callbacks
    - Test auto-wiring with various parameter types
    - Test exception scenarios
    - Performance benchmarks for resolution

12. Add Development Tools
    - Create ContainerDebugger for dependency graph visualization
    - Implement binding dump for debugging
    - Add resolution profiling for performance analysis
    - Create validation command for checking bindings
<!-- SECTION:PLAN:END -->
