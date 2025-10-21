<?php

declare(strict_types=1);

namespace Elarion\OpenAPI\Attributes;

use Attribute;

/**
 * OpenAPI PATCH Operation Attribute
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class Patch
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

    public function getMethod(): string
    {
        return 'patch';
    }
}
