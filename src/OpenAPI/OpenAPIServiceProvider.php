<?php

declare(strict_types=1);

namespace Elarion\OpenAPI;

use Elarion\Container\ServiceProvider;
use Elarion\OpenAPI\Generator\OpenApiGenerator;
use Elarion\OpenAPI\Http\Controllers\DocumentationController;
use Elarion\Routing\Router;

/**
 * OpenAPI Service Provider
 *
 * Registers OpenAPI services and documentation routes.
 */
final class OpenAPIServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register(): void
    {
        // Register OpenApiGenerator
        $this->container->singleton(OpenApiGenerator::class, function ($container) {
            $router = $container->make(Router::class);
            $config = $container->make('config')->get('openapi', []);

            return new OpenApiGenerator($router, $config);
        });

        // Register DocumentationController
        $this->container->singleton(DocumentationController::class, function ($container) {
            return new DocumentationController(
                $container->make(OpenApiGenerator::class)
            );
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void
    {
        $router = $this->container->make(Router::class);

        // Register documentation routes
        $router->get('/api/documentation', [DocumentationController::class, 'swaggerUI'], 'api.docs');
        $router->get('/api/documentation.json', [DocumentationController::class, 'json'], 'api.docs.json');
        $router->get('/api/documentation.yaml', [DocumentationController::class, 'yaml'], 'api.docs.yaml');
    }
}
