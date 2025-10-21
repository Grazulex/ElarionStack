<?php

declare(strict_types=1);

namespace Elarion\Validation\Rules;

use Elarion\Validation\AbstractRule;

/**
 * Max Validation Rule
 *
 * The field under validation must not exceed a maximum value.
 * - For strings: maximum length
 * - For numbers: maximum value
 * - For arrays: maximum count
 */
class Max extends AbstractRule
{
    /**
     * Create new Max rule
     *
     * @param int|float $max Maximum value/length/count
     */
    public function __construct(protected int|float $max)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function passes(string $attribute, mixed $value): bool
    {
        $this->attribute = $attribute;
        $this->value = $value;

        if (is_string($value)) {
            return mb_strlen($value) <= $this->max;
        }

        if (is_numeric($value)) {
            return $value <= $this->max;
        }

        if (is_array($value)) {
            return count($value) <= $this->max;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function getMessageTemplate(): string
    {
        return 'The :attribute must not exceed :max.';
    }

    /**
     * {@inheritdoc}
     */
    protected function getReplacements(): array
    {
        return array_merge(parent::getReplacements(), [
            'max' => $this->max,
        ]);
    }
}
