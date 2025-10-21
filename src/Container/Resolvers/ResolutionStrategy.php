<?php

declare(strict_types=1);

namespace Elarion\Container\Resolvers;

/**
 * Enumeration of resolution strategies
 *
 * Defines how dependencies should be resolved
 */
enum ResolutionStrategy: string
{
    /**
     * Automatic resolution using reflection and auto-wiring
     */
    case AutoWire = 'autowire';

    /**
     * Explicit resolution using registered bindings only
     */
    case Explicit = 'explicit';

    /**
     * Factory-based resolution using callbacks
     */
    case Factory = 'factory';

    /**
     * Check if this strategy allows auto-wiring
     */
    public function allowsAutoWiring(): bool
    {
        return $this === self::AutoWire;
    }

    /**
     * Check if this strategy requires explicit bindings
     */
    public function requiresBinding(): bool
    {
        return $this === self::Explicit;
    }
}
