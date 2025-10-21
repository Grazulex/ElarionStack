<?php

declare(strict_types=1);

namespace Elarion\Config;

/**
 * Application environment enumeration
 *
 * PHP 8.5 enum for type-safe environment management
 */
enum Environment: string
{
    case Development = 'development';
    case Testing = 'testing';
    case Staging = 'staging';
    case Production = 'production';

    /**
     * Check if current environment is development
     */
    public function isDevelopment(): bool
    {
        return $this === self::Development;
    }

    /**
     * Check if current environment is testing
     */
    public function isTesting(): bool
    {
        return $this === self::Testing;
    }

    /**
     * Check if current environment is staging
     */
    public function isStaging(): bool
    {
        return $this === self::Staging;
    }

    /**
     * Check if current environment is production
     */
    public function isProduction(): bool
    {
        return $this === self::Production;
    }

    /**
     * Check if caching should be enabled for this environment
     */
    public function shouldCache(): bool
    {
        return match ($this) {
            self::Production, self::Staging => true,
            self::Development, self::Testing => false,
        };
    }

    /**
     * Check if debug mode should be enabled
     */
    public function isDebugEnabled(): bool
    {
        return match ($this) {
            self::Development, self::Testing => true,
            self::Staging, self::Production => false,
        };
    }

    /**
     * Detect environment from environment variable
     */
    public static function detect(): self
    {
        $env = getenv('APP_ENV') ?: 'production';

        return match (strtolower($env)) {
            'dev', 'development', 'local' => self::Development,
            'test', 'testing' => self::Testing,
            'stage', 'staging' => self::Staging,
            default => self::Production,
        };
    }
}
