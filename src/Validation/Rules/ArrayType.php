<?php

declare(strict_types=1);

namespace Elarion\Validation\Rules;

use Elarion\Validation\AbstractRule;

/**
 * Array Type Validation Rule
 *
 * The field under validation must be an array.
 */
class ArrayType extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function passes(string $attribute, mixed $value): bool
    {
        $this->attribute = $attribute;
        $this->value = $value;

        return is_array($value);
    }

    /**
     * {@inheritdoc}
     */
    protected function getMessageTemplate(): string
    {
        return 'The :attribute must be an array.';
    }
}
