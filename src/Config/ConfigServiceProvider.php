<?php

declare(strict_types=1);

namespace Elarion\Config;

use Elarion\Config\Cache\FileConfigCache;
use Elarion\Config\Contracts\ConfigCacheInterface;
use Elarion\Config\Contracts\ConfigLoaderInterface;
use Elarion\Config\Contracts\ConfigRepositoryInterface;
use Elarion\Config\Loaders\PhpFileLoader;
use Elarion\Container\ServiceProvider;

/**
 * Configuration service provider
 *
 * Registers configuration services in the container.
 */
final class ConfigServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register(): void
    {
        // Register loader
        $this->container->singleton(ConfigLoaderInterface::class, PhpFileLoader::class);

        // Register repository
        $this->container->singleton(ConfigRepositoryInterface::class, ConfigRepository::class);

        // Register cache - factory binding
        $this->container->factory(ConfigCacheInterface::class, function ($resolver) {
            /** @var \Elarion\Core\Application $app */
            $app = $this->container->get('app');
            $cachePath = $app->basePath('storage/framework/config.php');

            return new FileConfigCache($cachePath);
        }, true); // singleton

        // Register manager as singleton - factory binding
        $this->container->factory(ConfigManager::class, function ($resolver) {
            /** @var \Elarion\Core\Application $app */
            $app = $this->container->get('app');
            $configPath = $app->basePath('config');

            /** @var ConfigRepositoryInterface $repository */
            $repository = $this->container->get(ConfigRepositoryInterface::class);

            /** @var ConfigLoaderInterface $loader */
            $loader = $this->container->get(ConfigLoaderInterface::class);

            /** @var ConfigCacheInterface $cache */
            $cache = $this->container->get(ConfigCacheInterface::class);

            $environment = Environment::detect();

            return new ConfigManager(
                $configPath,
                $repository,
                $loader,
                $cache,
                $environment
            );
        }, true); // singleton

        // Alias for easier access
        $this->container->alias('config', ConfigManager::class);
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void
    {
        // Configuration is loaded on-demand (lazy loading)
        // No need to load here
    }
}
