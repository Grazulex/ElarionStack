<?php

declare(strict_types=1);

namespace Elarion\Core;

/**
 * Application principale du framework ElarionStack
 *
 * Cette classe représente le cœur de l'application et gère
 * le cycle de vie complet d'une requête HTTP.
 */
class Application
{
    /**
     * Version du framework
     */
    public const VERSION = '0.1.0-dev';

    /**
     * Chemin racine de l'application
     */
    private readonly string $basePath;

    /**
     * Crée une nouvelle instance de l'application
     */
    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * Retourne le chemin racine de l'application
     */
    public function basePath(string $path = ''): string
    {
        if ($path === '') {
            return $this->basePath;
        }

        return $this->basePath . '/' . ltrim($path, '/');
    }

    /**
     * Retourne la version du framework
     */
    public function version(): string
    {
        return self::VERSION;
    }

    /**
     * Lance l'application
     */
    public function run(): void
    {
        echo sprintf(
            "ElarionStack Framework v%s\n",
            $this->version()
        );
        echo "Application running from: {$this->basePath}\n";
    }
}
