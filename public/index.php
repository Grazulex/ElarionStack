<?php

declare(strict_types=1);

/**
 * ElarionStack Framework - Entry Point
 *
 * This file is the main entry point for all HTTP requests.
 * It bootstraps the framework, loads routes, and dispatches requests.
 */

// Composer Autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use Elarion\Routing\Router;
use Elarion\Http\Message\ServerRequest;
use Elarion\Http\Message\Uri;
use Elarion\Http\Message\Response;

try {
    // Create router instance
    $router = new Router();

    // Load API routes
    if (file_exists(__DIR__ . '/../routes/api.php')) {
        require __DIR__ . '/../routes/api.php';
    }

    // Load web routes (if needed)
    if (file_exists(__DIR__ . '/../routes/web.php')) {
        require __DIR__ . '/../routes/web.php';
    }

    // Get HTTP method and URI
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';

    // Dispatch the route
    $routeMatch = $router->dispatch($method, $uri);

    // Handle 404 Not Found
    if (!$routeMatch->isFound()) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Not Found',
            'message' => "Route {$method} {$uri} not found",
        ], JSON_PRETTY_PRINT);
        exit;
    }

    // Create PSR-7 ServerRequest
    $request = new ServerRequest($method, new Uri($uri), [], null, '1.1', $_SERVER);

    // Parse query parameters
    parse_str(parse_url($uri, PHP_URL_QUERY) ?? '', $queryParams);
    $request = $request->withQueryParams($queryParams);

    // Parse request body for POST/PUT/PATCH
    if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            $request = $request->withParsedBody($body);
        } elseif (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            $request = $request->withParsedBody($_POST);
        }
    }

    // Get route handler and parameters
    $handler = $routeMatch->getHandler();
    $params = array_values($routeMatch->getParams());

    // Cast numeric parameters to integers
    $params = array_map(fn($p) => is_numeric($p) ? (int)$p : $p, $params);

    // Execute the handler
    if (is_callable($handler)) {
        // Closure handler
        $response = $handler($request, ...$params);
    } elseif (is_array($handler) && count($handler) === 2) {
        // Controller@method handler
        [$class, $methodName] = $handler;
        $controller = new $class();
        $response = $controller->$methodName($request, ...$params);
    } else {
        throw new \RuntimeException('Invalid route handler');
    }

    // Ensure we have a Response object
    if (!$response instanceof Response) {
        throw new \RuntimeException('Handler must return an instance of Response');
    }

    // Send HTTP status code
    http_response_code($response->getStatusCode());

    // Send headers
    foreach ($response->getHeaders() as $name => $values) {
        foreach ($values as $value) {
            header(sprintf('%s: %s', $name, $value), false);
        }
    }

    // Send response body
    echo $response->getBody();

} catch (\Throwable $e) {
    // Handle exceptions
    http_response_code(500);
    header('Content-Type: application/json');

    // Show detailed errors only in development
    $debug = (bool)($_ENV['APP_DEBUG'] ?? false);

    $error = [
        'error' => 'Internal Server Error',
        'message' => $e->getMessage(),
    ];

    if ($debug) {
        $error['file'] = $e->getFile();
        $error['line'] = $e->getLine();
        $error['trace'] = explode("\n", $e->getTraceAsString());
    }

    echo json_encode($error, JSON_PRETTY_PRINT);
}
