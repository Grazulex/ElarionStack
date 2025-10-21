<?php

declare(strict_types=1);

namespace Elarion\Config\Loaders;

use Elarion\Config\Contracts\ConfigLoaderInterface;

/**
 * PHP file configuration loader
 *
 * Loads configuration from PHP files that return arrays.
 * Following SRP - only responsible for loading PHP config files.
 */
final class PhpFileLoader implements ConfigLoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(string $path): array
    {
        if (! $this->supports($path)) {
            throw new \RuntimeException(
                sprintf('File [%s] is not a PHP file', $path)
            );
        }

        if (! file_exists($path)) {
            throw new \RuntimeException(
                sprintf('Configuration file [%s] does not exist', $path)
            );
        }

        if (! is_readable($path)) {
            throw new \RuntimeException(
                sprintf('Configuration file [%s] is not readable', $path)
            );
        }

        // Load the file
        $config = require $path;

        // Validate it returns an array
        if (! is_array($config)) {
            throw new \RuntimeException(
                sprintf(
                    'Configuration file [%s] must return an array, %s returned',
                    $path,
                    get_debug_type($config)
                )
            );
        }

        /** @var array<string, mixed> */
        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $path): bool
    {
        return str_ends_with($path, '.php');
    }
}
