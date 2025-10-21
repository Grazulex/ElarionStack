<?php

declare(strict_types=1);

namespace Elarion\Config\Cache;

use Elarion\Config\Contracts\ConfigCacheInterface;

/**
 * File-based configuration cache
 *
 * Caches compiled configuration in a single PHP file for production performance.
 * Opcache-friendly format.
 */
final class FileConfigCache implements ConfigCacheInterface
{
    /**
     * Create a new file config cache
     *
     * @param string $cachePath Full path to cache file
     */
    public function __construct(
        private readonly string $cachePath
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function has(): bool
    {
        return file_exists($this->cachePath);
    }

    /**
     * {@inheritdoc}
     */
    public function get(): array
    {
        if (! $this->has()) {
            throw new \RuntimeException(
                sprintf('Configuration cache file [%s] does not exist', $this->cachePath)
            );
        }

        /** @var array<string, mixed> */
        return require $this->cachePath;
    }

    /**
     * {@inheritdoc}
     */
    public function put(array $config): void
    {
        $cacheDir = dirname($this->cachePath);

        // Ensure cache directory exists
        if (! is_dir($cacheDir) && ! mkdir($cacheDir, 0755, true) && ! is_dir($cacheDir)) {
            throw new \RuntimeException(
                sprintf('Failed to create cache directory [%s]', $cacheDir)
            );
        }

        // Generate cache content
        $content = "<?php\n\n// Configuration cache generated at " . date('Y-m-d H:i:s') . "\n";
        $content .= "// Do not modify this file manually\n\n";
        $content .= 'return ' . var_export($config, true) . ";\n";

        // Write atomically using temporary file
        $tempFile = $this->cachePath . '.' . uniqid('tmp', true);

        if (file_put_contents($tempFile, $content, LOCK_EX) === false) {
            throw new \RuntimeException(
                sprintf('Failed to write configuration cache to [%s]', $tempFile)
            );
        }

        // Atomic rename
        if (! rename($tempFile, $this->cachePath)) {
            @unlink($tempFile);

            throw new \RuntimeException(
                sprintf('Failed to move cache file from [%s] to [%s]', $tempFile, $this->cachePath)
            );
        }

        // Set permissions
        chmod($this->cachePath, 0644);

        // Clear opcache for this file
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($this->cachePath, true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): void
    {
        if ($this->has()) {
            // Clear opcache first
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($this->cachePath, true);
            }

            unlink($this->cachePath);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCachePath(): string
    {
        return $this->cachePath;
    }
}
