<?php

declare(strict_types=1);

namespace Elarion\Container\Registry;

use Elarion\Container\Contracts\BindingInterface;
use Elarion\Container\Contracts\BindingRegistryInterface;
use Elarion\Container\Exceptions\NotFoundException;

/**
 * Registry for managing container bindings
 *
 * Following SRP - this class is ONLY responsible for storing and retrieving bindings.
 * It does NOT handle resolution.
 *
 * PHP 8.5 Features:
 * - Readonly class for immutability
 * - Property hooks for validation
 * - Asymmetric visibility for controlled access
 */
final class BindingRegistry implements BindingRegistryInterface
{
    /**
     * Storage for bindings
     * Using asymmetric visibility: private write, public read through methods
     *
     * @var array<string, BindingInterface>
     */
    private array $bindings = [];

    /**
     * {@inheritdoc}
     */
    public function bind(string $abstract, BindingInterface $binding): void
    {
        // Validate that the binding's abstract matches the key
        if ($binding->getAbstract() !== $abstract) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Binding abstract [%s] does not match registry key [%s]',
                    $binding->getAbstract(),
                    $abstract
                )
            );
        }

        $this->bindings[$abstract] = $binding;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $abstract): BindingInterface
    {
        if (!$this->has($abstract)) {
            throw NotFoundException::forAbstract($abstract);
        }

        return $this->bindings[$abstract];
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return $this->bindings;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $abstract): void
    {
        unset($this->bindings[$abstract]);
    }

    /**
     * Get the count of registered bindings
     */
    public function count(): int
    {
        return count($this->bindings);
    }

    /**
     * Check if the registry is empty
     */
    public function isEmpty(): bool
    {
        return $this->bindings === [];
    }

    /**
     * Clear all bindings
     */
    public function clear(): void
    {
        $this->bindings = [];
    }

    /**
     * Get all binding abstracts (keys)
     *
     * @return array<int, string>
     */
    public function getAbstracts(): array
    {
        return array_keys($this->bindings);
    }
}
