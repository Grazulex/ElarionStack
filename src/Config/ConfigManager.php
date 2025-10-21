<?php

declare(strict_types=1);

namespace Elarion\Config;

use Elarion\Config\Contracts\ConfigCacheInterface;
use Elarion\Config\Contracts\ConfigLoaderInterface;
use Elarion\Config\Contracts\ConfigRepositoryInterface;

/**
 * Configuration manager - orchestrates loading, caching, and access
 *
 * Following Facade pattern to provide unified interface.
 * Implements lazy loading and caching strategies.
 */
final class ConfigManager implements ConfigRepositoryInterface
{
    /**
     * Whether configurations have been loaded
     */
    private bool $loaded = false;

    /**
     * Create a new configuration manager
     *
     * @param string $configPath Path to configuration directory
     * @param ConfigRepositoryInterface $repository Configuration repository
     * @param ConfigLoaderInterface $loader Configuration file loader
     * @param ConfigCacheInterface|null $cache Optional cache implementation
     * @param Environment $environment Current environment
     */
    public function __construct(
        private readonly string $configPath,
        private readonly ConfigRepositoryInterface $repository,
        private readonly ConfigLoaderInterface $loader,
        private readonly ?ConfigCacheInterface $cache = null,
        private readonly Environment $environment = Environment::Production
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->loadIfNeeded();

        return $this->repository->get($key, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        $this->loadIfNeeded();

        return $this->repository->has($key);
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value): void
    {
        $this->loadIfNeeded();

        $this->repository->set($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        $this->loadIfNeeded();

        return $this->repository->all();
    }

    /**
     * {@inheritdoc}
     */
    public function load(string $name, array $config): void
    {
        $this->repository->load($name, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function loadMany(array $configs): void
    {
        $this->repository->loadMany($configs);
    }

    /**
     * Load configurations if not already loaded
     */
    private function loadIfNeeded(): void
    {
        if ($this->loaded) {
            return;
        }

        // Try to load from cache first (production only)
        if ($this->shouldUseCache() && $this->cache?->has()) {
            $this->loadFromCache();
        } else {
            $this->loadFromFiles();
        }

        $this->loaded = true;
    }

    /**
     * Load configurations from cache
     */
    private function loadFromCache(): void
    {
        if ($this->cache === null) {
            return;
        }

        /** @var array<string, array<string, mixed>> $cached */
        $cached = $this->cache->get();
        $this->repository->loadMany($cached);
    }

    /**
     * Load configurations from files
     */
    private function loadFromFiles(): void
    {
        if (! is_dir($this->configPath)) {
            throw new \RuntimeException(
                sprintf('Configuration directory [%s] does not exist', $this->configPath)
            );
        }

        /** @var array<string, array<string, mixed>> $configs */
        $configs = [];

        // Load all PHP files in config directory
        $files = glob($this->configPath . '/*.php');

        if ($files === false) {
            throw new \RuntimeException(
                sprintf('Failed to read configuration directory [%s]', $this->configPath)
            );
        }

        foreach ($files as $file) {
            $name = basename($file, '.php');
            $configs[$name] = $this->loader->load($file);
        }

        // Load into repository
        $this->repository->loadMany($configs);

        // Cache if appropriate
        if ($this->shouldUseCache() && $this->cache !== null) {
            $this->cache->put($configs);
        }
    }

    /**
     * Determine if cache should be used
     */
    private function shouldUseCache(): bool
    {
        return $this->environment->shouldCache() && $this->cache !== null;
    }

    /**
     * Refresh configurations (reload from files)
     */
    public function refresh(): void
    {
        $this->loaded = false;
        $this->cache?->clear();
        $this->loadIfNeeded();
    }

    /**
     * Clear configuration cache
     */
    public function clearCache(): void
    {
        $this->cache?->clear();
    }
}
