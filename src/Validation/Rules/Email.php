<?php

declare(strict_types=1);

namespace Elarion\Validation\Rules;

use Elarion\Validation\AbstractRule;

/**
 * Email Validation Rule
 *
 * The field under validation must be formatted as an email address.
 */
class Email extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function passes(string $attribute, mixed $value): bool
    {
        $this->attribute = $attribute;
        $this->value = $value;

        if (! is_string($value)) {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * {@inheritdoc}
     */
    protected function getMessageTemplate(): string
    {
        return 'The :attribute must be a valid email address.';
    }
}
