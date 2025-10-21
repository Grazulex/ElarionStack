<?php

declare(strict_types=1);

namespace Elarion\OpenAPI\Attributes;

use Attribute;

/**
 * Tag Attribute
 *
 * Groups operations together.
 * @example #[Tag('Users', description: 'User management endpoints')]
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Tag
{
    public function __construct(
        public string $name,
        public ?string $description = null
    ) {
    }
}
