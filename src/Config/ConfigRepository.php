<?php

declare(strict_types=1);

namespace Elarion\Config;

use Elarion\Config\Contracts\ConfigRepositoryInterface;

/**
 * Configuration repository implementation
 *
 * Stores and provides access to configuration data using dot notation.
 * Thread-safe access to configuration values.
 */
final class ConfigRepository implements ConfigRepositoryInterface
{
    /**
     * All configuration data
     *
     * @var array<string, mixed>
     */
    private array $items = [];

    /**
     * Dot notation parser
     */
    private readonly DotNotationParser $parser;

    public function __construct(?DotNotationParser $parser = null)
    {
        $this->parser = $parser ?? new DotNotationParser();
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->parser->get($this->items, $key, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return $this->parser->has($this->items, $key);
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value): void
    {
        $this->parser->set($this->items, $key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * {@inheritdoc}
     */
    public function load(string $name, array $config): void
    {
        $this->items[$name] = $config;
    }

    /**
     * Load multiple configuration arrays at once
     *
     * @param array<string, array<string, mixed>> $configs
     */
    public function loadMany(array $configs): void
    {
        foreach ($configs as $name => $config) {
            $this->load($name, $config);
        }
    }

    /**
     * Merge configuration into existing data
     *
     * @param string $name Configuration name
     * @param array<string, mixed> $config Configuration to merge
     */
    public function merge(string $name, array $config): void
    {
        if (isset($this->items[$name]) && is_array($this->items[$name])) {
            /** @var array<string, mixed> $existing */
            $existing = $this->items[$name];
            $this->items[$name] = array_replace_recursive($existing, $config);
        } else {
            $this->load($name, $config);
        }
    }
}
