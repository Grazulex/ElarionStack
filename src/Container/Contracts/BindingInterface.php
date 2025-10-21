<?php

declare(strict_types=1);

namespace Elarion\Container\Contracts;

/**
 * Represents a binding in the container
 *
 * A binding defines how a particular abstract (class name or interface)
 * should be resolved to a concrete implementation.
 *
 * This interface follows the Interface Segregation Principle (ISP)
 * by providing only the essential methods needed by clients.
 */
interface BindingInterface
{
    /**
     * Get the abstract identifier (class name or interface)
     */
    public function getAbstract(): string;

    /**
     * Resolve the binding to a concrete instance
     *
     * @param array<string, mixed> $parameters Optional parameters for resolution
     * @return mixed The resolved instance
     */
    public function resolve(array $parameters = []): mixed;

    /**
     * Check if this is a singleton binding
     */
    public function isSingleton(): bool;

    /**
     * Check if this binding has been resolved at least once
     */
    public function isResolved(): bool;
}
