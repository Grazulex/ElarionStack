<?php

declare(strict_types=1);

namespace Elarion\Validation\Rules;

use Elarion\Validation\AbstractRule;

/**
 * Boolean Type Validation Rule
 *
 * The field under validation must be a boolean value.
 * Accepts: true, false, 1, 0, "1", "0"
 */
class BooleanType extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function passes(string $attribute, mixed $value): bool
    {
        $this->attribute = $attribute;
        $this->value = $value;

        $acceptable = [true, false, 0, 1, '0', '1'];

        return in_array($value, $acceptable, true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getMessageTemplate(): string
    {
        return 'The :attribute must be a boolean.';
    }
}
