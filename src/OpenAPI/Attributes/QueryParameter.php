<?php

declare(strict_types=1);

namespace Elarion\OpenAPI\Attributes;

use Attribute;

/**
 * Query Parameter Attribute
 *
 * @example #[QueryParameter('page', type: 'integer', description: 'Page number')]
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class QueryParameter
{
    public function __construct(
        public string $name,
        public string $type = 'string',
        public bool $required = false,
        public ?string $description = null,
        public ?string $format = null
    ) {
    }
}
