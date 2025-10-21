<?php

declare(strict_types=1);

namespace Elarion\OpenAPI\Generator;

use Elarion\OpenAPI\Schema\Schema;

/**
 * Validation Scanner
 *
 * Converts validation rules to OpenAPI schemas.
 */
final class ValidationScanner
{
    /**
     * Convert validation rules to OpenAPI schema
     *
     * @param array<string, string> $rules Validation rules
     * @return Schema
     */
    public function convertToSchema(array $rules): Schema
    {
        $properties = [];
        $required = [];

        foreach ($rules as $field => $rule) {
            $fieldRules = is_string($rule) ? explode('|', $rule) : $rule;
            $schema = $this->parseFieldRules($fieldRules);

            $properties[$field] = $schema;

            if ($this->isRequired($fieldRules)) {
                $required[] = $field;
            }
        }

        return Schema::object($properties, $required);
    }

    /**
     * Parse field rules to schema
     *
     * @param array<string>|string $rules
     */
    private function parseFieldRules(array|string $rules): Schema
    {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        $type = 'string';
        $format = null;
        $minLength = null;
        $maxLength = null;
        $minimum = null;
        $maximum = null;

        foreach ($rules as $rule) {
            if (str_contains($rule, ':')) {
                [$ruleName, $value] = explode(':', $rule, 2);
            } else {
                $ruleName = $rule;
                $value = null;
            }

            match ($ruleName) {
                'string' => $type = 'string',
                'integer', 'int' => $type = 'integer',
                'numeric', 'number' => $type = 'number',
                'boolean', 'bool' => $type = 'boolean',
                'array' => $type = 'array',
                'email' => [$type, $format] = ['string', 'email'],
                'url' => [$type, $format] = ['string', 'uri'],
                'date' => [$type, $format] = ['string', 'date'],
                'min' => $type === 'string' ? $minLength = (int) $value : $minimum = (int) $value,
                'max' => $type === 'string' ? $maxLength = (int) $value : $maximum = (int) $value,
                default => null,
            };
        }

        return new Schema(
            type: $type,
            format: $format,
            minLength: $minLength,
            maxLength: $maxLength,
            minimum: $minimum,
            maximum: $maximum
        );
    }

    /**
     * Check if field is required
     *
     * @param array<string> $rules
     */
    private function isRequired(array $rules): bool
    {
        return in_array('required', $rules, true);
    }
}
