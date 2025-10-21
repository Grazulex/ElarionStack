<?php

declare(strict_types=1);

namespace Elarion\OpenAPI\Generator;

use Elarion\OpenAPI\Schema\{
    Info,
    OpenApiDocument,
    Operation,
    Parameter,
    PathItem,
    Response,
    Schema,
    Server
};
use Elarion\Routing\Router;

/**
 * OpenAPI Generator
 *
 * Main orchestrator for generating OpenAPI documentation.
 */
final class OpenApiGenerator
{
    private RouteScanner $routeScanner;
    private AttributeScanner $attributeScanner;

    public function __construct(
        private Router $router,
        private array $config = []
    ) {
        $this->routeScanner = new RouteScanner($router);
        $this->attributeScanner = new AttributeScanner();
    }

    /**
     * Generate complete OpenAPI document
     */
    public function generate(): OpenApiDocument
    {
        $document = new OpenApiDocument();

        // Set API info
        $document->setInfo(new Info(
            title: $this->config['title'] ?? 'API Documentation',
            version: $this->config['version'] ?? '1.0.0',
            description: $this->config['description'] ?? null
        ));

        // Add servers
        if (isset($this->config['servers'])) {
            foreach ($this->config['servers'] as $serverConfig) {
                $document->addServer(new Server(
                    url: $serverConfig['url'],
                    description: $serverConfig['description'] ?? null
                ));
            }
        }

        // Scan routes and generate paths
        $routes = $this->routeScanner->scan();

        foreach ($routes as $routeInfo) {
            $this->addRouteToDocument($document, $routeInfo);
        }

        return $document;
    }

    /**
     * Add a route to the OpenAPI document
     *
     * @param array<string, mixed> $routeInfo
     */
    private function addRouteToDocument(OpenApiDocument $document, array $routeInfo): void
    {
        $path = $routeInfo['path'];
        $method = $routeInfo['method'];
        $handler = $routeInfo['handler'];

        // Get or create PathItem
        $pathItem = new PathItem();

        // Scan attributes from handler
        $metadata = $this->attributeScanner->scan($handler);

        // Create operation
        $operation = $this->createOperation($routeInfo, $metadata);

        // Add path parameters
        $pathParams = $this->routeScanner->extractPathParameters($path);
        foreach ($pathParams as $paramName) {
            $operation->addParameter(Parameter::path(
                $paramName,
                Schema::string(),
                "The {$paramName} parameter"
            ));
        }

        // Set operation on path item
        match ($method) {
            'get' => $pathItem->setGet($operation),
            'post' => $pathItem->setPost($operation),
            'put' => $pathItem->setPut($operation),
            'patch' => $pathItem->setPatch($operation),
            'delete' => $pathItem->setDelete($operation),
            default => null,
        };

        $document->addPath($path, $pathItem);
    }

    /**
     * Create operation from route and metadata
     *
     * @param array<string, mixed> $routeInfo
     * @param array<string, mixed> $metadata
     */
    private function createOperation(array $routeInfo, array $metadata): Operation
    {
        $operation = new Operation();

        // Use attribute metadata if available
        if ($metadata['operation'] ?? null) {
            $opAttr = $metadata['operation'];
            $operation = new Operation(
                tags: $opAttr->tags,
                summary: $opAttr->summary,
                description: $opAttr->description,
                operationId: $opAttr->operationId
            );
        } else {
            // Generate default metadata
            $operation = new Operation(
                summary: ucfirst($routeInfo['method']) . ' ' . $routeInfo['path']
            );
        }

        // Add default 200 response if no responses defined
        if (empty($metadata['responses'])) {
            $operation->addResponse('200', Response::json('Success', Schema::object()));
        }

        return $operation;
    }
}
