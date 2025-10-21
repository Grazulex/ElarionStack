<?php

declare(strict_types=1);

namespace Elarion\Validation\Rules;

use Elarion\Validation\AbstractRule;

/**
 * Min Validation Rule
 *
 * The field under validation must have a minimum value.
 * - For strings: minimum length
 * - For numbers: minimum value
 * - For arrays: minimum count
 */
class Min extends AbstractRule
{
    /**
     * Create new Min rule
     *
     * @param int|float $min Minimum value/length/count
     */
    public function __construct(protected int|float $min)
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
            return mb_strlen($value) >= $this->min;
        }

        if (is_numeric($value)) {
            return $value >= $this->min;
        }

        if (is_array($value)) {
            return count($value) >= $this->min;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function getMessageTemplate(): string
    {
        return 'The :attribute must be at least :min.';
    }

    /**
     * {@inheritdoc}
     */
    protected function getReplacements(): array
    {
        return array_merge(parent::getReplacements(), [
            'min' => $this->min,
        ]);
    }
}
