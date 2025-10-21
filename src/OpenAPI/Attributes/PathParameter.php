<?php

declare(strict_types=1);

namespace Elarion\OpenAPI\Attributes;

use Attribute;

/**
 * Path Parameter Attribute
 *
 * @example #[PathParameter('id', type: 'integer', description: 'User ID')]
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class PathParameter
{
    public function __construct(
        public string $name,
        public string $type = 'string',
        public ?string $description = null,
        public ?string $format = null
    ) {
    }
}
