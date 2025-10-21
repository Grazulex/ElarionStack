<?php

declare(strict_types=1);

namespace Elarion\OpenAPI\Attributes;

use Attribute;

/**
 * OpenAPI GET Operation Attribute
 *
 * Marks a method as a GET endpoint and provides OpenAPI documentation.
 *
 * @example
 * #[Get(path: '/users/{id}', summary: 'Get user by ID', tags: ['Users'])]
 * public function show(int $id): Response
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class Get
{
    /**
     * @param string $path The endpoint path
     * @param string|null $summary Short summary
     * @param string|null $description Long description
     * @param array<string> $tags Tags for grouping
     * @param string|null $operationId Unique operation identifier
     * @param bool $deprecated Whether the endpoint is deprecated
     */
    public function __construct(
        public string $path,
        public ?string $summary = null,
        public ?string $description = null,
        public array $tags = [],
        public ?string $operationId = null,
        public bool $deprecated = false
    ) {
    }

    /**
     * Get HTTP method
     */
    public function getMethod(): string
    {
        return 'get';
    }
}
