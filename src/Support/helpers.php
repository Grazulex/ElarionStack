<?php

declare(strict_types=1);

/**
 * ElarionStack Framework - Helper Functions
 *
 * Ce fichier contient les fonctions helper globales du framework
 */

if (! function_exists('env')) {
    /**
     * Récupère une variable d'environnement avec valeur par défaut
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        return match (strtolower($value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => $value,
        };
    }
}

if (! function_exists('dd')) {
    /**
     * Dump and die - Affiche une variable et arrête l'exécution
     */
    function dd(mixed ...$vars): never
    {
        foreach ($vars as $var) {
            var_dump($var);
        }

        exit(1);
    }
}

if (! function_exists('dump')) {
    /**
     * Dump - Affiche une variable sans arrêter l'exécution
     */
    function dump(mixed ...$vars): void
    {
        foreach ($vars as $var) {
            var_dump($var);
        }
    }
}

if (! function_exists('value')) {
    /**
     * Retourne la valeur par défaut d'une valeur donnée
     */
    function value(mixed $value, mixed ...$args): mixed
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}

if (! function_exists('tap')) {
    /**
     * Appelle une callback avec la valeur et retourne la valeur
     */
    function tap(mixed $value, ?callable $callback = null): mixed
    {
        if ($callback === null) {
            return $value;
        }

        $callback($value);

        return $value;
    }
}

if (! function_exists('with')) {
    /**
     * Retourne la valeur donnée
     */
    function with(mixed $value, ?callable $callback = null): mixed
    {
        return $callback === null ? $value : $callback($value);
    }
}

if (! function_exists('config')) {
    /**
     * Get / set the specified configuration value
     *
     * @param array<string, mixed>|string|null $key
     * @param mixed $default
     * @return mixed
     */
    function config(array|string|null $key = null, mixed $default = null): mixed
    {
        // This will be properly implemented when integrated with Application
        // For now, return default
        return $default;
    }
}

if (! function_exists('route')) {
    /**
     * Generate URL for named route or get Router instance
     *
     * @param string|null $name Route name
     * @param array<string, string|int> $params Route parameters
     * @return \Elarion\Routing\Router|string Router instance or generated URL
     */
    function route(?string $name = null, array $params = []): \Elarion\Routing\Router|string
    {
        // This will be properly implemented when integrated with Application
        // For now, return a placeholder
        static $router = null;

        if ($router === null) {
            $router = new \Elarion\Routing\Router();
        }

        if ($name === null) {
            return $router;
        }

        return $router->url($name, $params);
    }
}
