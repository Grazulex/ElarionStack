<?php

declare(strict_types=1);

namespace Elarion\OpenAPI\Attributes;

use Attribute;

/**
 * Request Body Attribute
 *
 * @example #[RequestBodyAttribute(schema: CreateUserRequest::class, required: true)]
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class RequestBodyAttribute
{
    /**
     * @param string|null $schema Schema class name
     * @param bool $required Whether body is required
     * @param string|null $description Description
     * @param string $mediaType Media type
     */
    public function __construct(
        public ?string $schema = null,
        public bool $required = true,
        public ?string $description = null,
        public string $mediaType = 'application/json'
    ) {
    }
}
