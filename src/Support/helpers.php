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
     * Get configuration value using dot notation
     *
     * @param string|null $key Configuration key (e.g., 'database.host')
     * @param mixed $default Default value if key not found
     * @return mixed Configuration value or default
     */
    function config(?string $key = null, mixed $default = null): mixed
    {
        static $config = null;

        if ($config === null) {
            $config = new \Elarion\Config\ConfigRepository();
        }

        if ($key === null) {
            return $config;
        }

        return $config->get($key, $default);
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

if (! function_exists('response')) {
    /**
     * Create an HTTP response
     *
     * @param mixed $data Response data (will be JSON encoded if array/object)
     * @param int $status HTTP status code
     * @param array<string, string|array<int, string>> $headers Additional headers
     * @return \Elarion\Http\Message\Response HTTP Response
     */
    function response(
        mixed $data = '',
        int $status = 200,
        array $headers = []
    ): \Elarion\Http\Message\Response {
        if (is_array($data) || is_object($data)) {
            return \Elarion\Http\Message\Response::json($data, $status, $headers);
        }

        return new \Elarion\Http\Message\Response($status, $headers, (string) $data);
    }
}

if (! function_exists('collect')) {
    /**
     * Create a Collection instance from the given items
     *
     * @param iterable<mixed> $items Items to collect
     * @return \Elarion\Support\Collection Collection instance
     */
    function collect(iterable $items = []): \Elarion\Support\Collection
    {
        return new \Elarion\Support\Collection($items);
    }
}
