<?php

declare(strict_types=1);

namespace Elarion\OpenAPI\Http\Controllers;

use Elarion\Http\Message\Response;
use Elarion\OpenAPI\Generator\OpenApiGenerator;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Documentation Controller
 *
 * Serves OpenAPI documentation in various formats.
 */
final class DocumentationController
{
    public function __construct(
        private OpenApiGenerator $generator
    ) {
    }

    /**
     * Get OpenAPI documentation as JSON
     */
    public function json(ServerRequestInterface $request): Response
    {
        $document = $this->generator->generate();

        return Response::json(
            $document->jsonSerialize(),
            200,
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * Get OpenAPI documentation as YAML
     */
    public function yaml(ServerRequestInterface $request): Response
    {
        $document = $this->generator->generate();

        return new Response(
            200,
            ['Content-Type' => 'application/x-yaml'],
            $document->toYaml()
        );
    }

    /**
     * Serve Swagger UI
     */
    public function swaggerUI(ServerRequestInterface $request): Response
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation - Swagger UI</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = () => {
            window.ui = SwaggerUIBundle({
                url: '/api/documentation.json',
                dom_id: '#swagger-ui',
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                layout: "StandaloneLayout"
            });
        };
    </script>
</body>
</html>
HTML;

        return Response::html($html);
    }

    /**
     * Serve ReDoc UI
     */
    public function redocUI(ServerRequestInterface $request): Response
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation - ReDoc</title>
    <style>
        body {
            margin: 0;
            padding: 0;
        }
    </style>
</head>
<body>
    <redoc spec-url='/api/documentation.json'></redoc>
    <script src="https://cdn.redoc.ly/redoc/latest/bundles/redoc.standalone.js"></script>
</body>
</html>
HTML;

        return Response::html($html);
    }
}
