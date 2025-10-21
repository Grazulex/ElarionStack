<?php

declare(strict_types=1);

namespace Elarion\Validation;

/**
 * Validation Rule Interface
 *
 * Defines the contract for all validation rules.
 */
interface Rule
{
    /**
     * Determine if the validation rule passes
     *
     * @param string $attribute Attribute name being validated
     * @param mixed $value Value being validated
     * @return bool True if validation passes
     */
    public function passes(string $attribute, mixed $value): bool;

    /**
     * Get the validation error message
     *
     * @return string Error message
     */
    public function message(): string;

    /**
     * Set the data under validation
     *
     * @param array<string, mixed> $data All data being validated
     * @return void
     */
    public function setData(array $data): void;
}
