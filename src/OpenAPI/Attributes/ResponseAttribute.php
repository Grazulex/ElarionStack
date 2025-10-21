<?php

declare(strict_types=1);

namespace Elarion\OpenAPI\Attributes;

use Attribute;

/**
 * Response Attribute
 *
 * @example #[ResponseAttribute(200, description: 'Success', schema: UserResource::class)]
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class ResponseAttribute
{
    /**
     * @param int $status HTTP status code
     * @param string $description Response description
     * @param string|null $schema Schema class name or ref
     * @param string $mediaType Media type (default: application/json)
     */
    public function __construct(
        public int $status,
        public string $description,
        public ?string $schema = null,
        public string $mediaType = 'application/json'
    ) {
    }
}
