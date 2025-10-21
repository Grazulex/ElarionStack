<?php

declare(strict_types=1);

namespace Elarion\Validation\Rules;

use Elarion\Validation\AbstractRule;

/**
 * Required Validation Rule
 *
 * The field under validation must be present and not empty.
 */
class Required extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function passes(string $attribute, mixed $value): bool
    {
        $this->attribute = $attribute;
        $this->value = $value;

        if (is_null($value)) {
            return false;
        }

        if (is_string($value) && trim($value) === '') {
            return false;
        }

        if (is_array($value) && count($value) === 0) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function getMessageTemplate(): string
    {
        return 'The :attribute field is required.';
    }
}
