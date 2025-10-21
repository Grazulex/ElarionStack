<?php

declare(strict_types=1);

namespace Elarion\Validation\Rules;

use Elarion\Validation\AbstractRule;

/**
 * Integer Type Validation Rule
 *
 * The field under validation must be an integer.
 */
class IntegerType extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function passes(string $attribute, mixed $value): bool
    {
        $this->attribute = $attribute;
        $this->value = $value;

        return is_int($value);
    }

    /**
     * {@inheritdoc}
     */
    protected function getMessageTemplate(): string
    {
        return 'The :attribute must be an integer.';
    }
}
