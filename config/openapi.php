<?php

declare(strict_types=1);

/**
 * OpenAPI Configuration
 *
 * Configuration for OpenAPI/Swagger documentation generation.
 */
return [
    /*
    |--------------------------------------------------------------------------
    | API Information
    |--------------------------------------------------------------------------
    |
    | Basic information about your API.
    |
    */
    'title' => env('API_TITLE', 'ElarionStack API'),
    'version' => env('API_VERSION', '1.0.0'),
    'description' => env('API_DESCRIPTION', 'API Documentation generated with ElarionStack'),

    /*
    |--------------------------------------------------------------------------
    | Servers
    |--------------------------------------------------------------------------
    |
    | Define the servers where your API is hosted.
    |
    */
    'servers' => [
        [
            'url' => env('API_URL', 'http://localhost:8000'),
            'description' => 'Development Server',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Documentation Routes
    |--------------------------------------------------------------------------
    |
    | Customize the routes for documentation endpoints.
    |
    */
    'routes' => [
        'ui' => '/api/documentation',
        'redoc' => '/api/redoc',
        'json' => '/api/documentation.json',
        'yaml' => '/api/documentation.yaml',
    ],
];
