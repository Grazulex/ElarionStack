<?php

declare(strict_types=1);

namespace Elarion\Container\Bindings;

/**
 * Enumeration of binding types
 *
 * PHP 8.5 Enum for type-safe binding type constants
 */
enum BindingType: string
{
    /**
     * Concrete binding - creates a new instance every time
     */
    case Concrete = 'concrete';

    /**
     * Singleton binding - creates and caches a single instance
     */
    case Singleton = 'singleton';

    /**
     * Factory binding - uses a callable to create instances
     */
    case Factory = 'factory';

    /**
     * Alias binding - points to another binding
     */
    case Alias = 'alias';

    /**
     * Check if this binding type should cache instances
     */
    public function shouldCache(): bool
    {
        return $this === self::Singleton;
    }

    /**
     * Check if this binding type creates new instances each time
     */
    public function isTransient(): bool
    {
        return $this === self::Concrete || $this === self::Factory;
    }

    /**
     * Get a human-readable description
     */
    public function description(): string
    {
        return match ($this) {
            self::Concrete => 'Creates a new instance each time',
            self::Singleton => 'Returns the same instance every time',
            self::Factory => 'Uses a factory callback to create instances',
            self::Alias => 'Points to another binding',
        };
    }
}
