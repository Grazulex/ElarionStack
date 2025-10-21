<?php

declare(strict_types=1);

namespace Elarion\Validation;

use Closure;
use Elarion\Validation\Rules\ArrayType;
use Elarion\Validation\Rules\BooleanType;
use Elarion\Validation\Rules\Email;
use Elarion\Validation\Rules\IntegerType;
use Elarion\Validation\Rules\Max;
use Elarion\Validation\Rules\Min;
use Elarion\Validation\Rules\Numeric;
use Elarion\Validation\Rules\Required;
use Elarion\Validation\Rules\StringType;

/**
 * Validator
 *
 * Validates data against a set of rules and collects error messages.
 */
class Validator
{
    /**
     * Validation errors
     *
     * @var array<string, array<string>>
     */
    protected array $errors = [];

    /**
     * Custom error messages
     *
     * @var array<string, string>
     */
    protected array $messages = [];

    /**
     * Rule aliases for string-based rules
     *
     * @var array<string, class-string<Rule>>
     */
    protected array $ruleAliases = [
        'required' => Required::class,
        'email' => Email::class,
        'min' => Min::class,
        'max' => Max::class,
        'string' => StringType::class,
        'integer' => IntegerType::class,
        'numeric' => Numeric::class,
        'boolean' => BooleanType::class,
        'array' => ArrayType::class,
    ];

    /**
     * Create new Validator instance
     *
     * @param array<string, mixed> $data Data to validate
     * @param array<string, mixed> $rules Validation rules
     * @param array<string, string> $messages Custom error messages
     */
    public function __construct(
        protected array $data,
        protected array $rules,
        array $messages = []
    ) {
        $this->messages = $messages;
    }

    /**
     * Validate the data
     *
     * @return bool True if validation passes
     */
    public function validate(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $attribute => $rules) {
            $this->validateAttribute($attribute, $rules);
        }

        return empty($this->errors);
    }

    /**
     * Validate a single attribute
     *
     * @param string $attribute Attribute name
     * @param mixed $rules Rules for this attribute
     * @return void
     */
    protected function validateAttribute(string $attribute, mixed $rules): void
    {
        // Normalize rules to array
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        if (! is_array($rules)) {
            $rules = [$rules];
        }

        // Get value using dot notation
        $value = $this->getValue($attribute);

        foreach ($rules as $rule) {
            $this->validateRule($attribute, $value, $rule);
        }
    }

    /**
     * Validate a single rule
     *
     * @param string $attribute Attribute name
     * @param mixed $value Value to validate
     * @param mixed $rule Rule to apply
     * @return void
     */
    protected function validateRule(string $attribute, mixed $value, mixed $rule): void
    {
        // Handle Closure rules
        if ($rule instanceof Closure) {
            $passes = $rule($attribute, $value, $this->data);

            if (! $passes) {
                $this->addError($attribute, $this->getCustomMessage($attribute, 'closure') ?? "The {$attribute} is invalid.");
            }

            return;
        }

        // Handle Rule instances
        if ($rule instanceof Rule) {
            $rule->setData($this->data);

            if (! $rule->passes($attribute, $value)) {
                $message = $this->getCustomMessage($attribute, get_class($rule)) ?? $rule->message();
                $this->addError($attribute, $message);
            }

            return;
        }

        // Handle string rules
        if (is_string($rule)) {
            $this->validateStringRule($attribute, $value, $rule);

            return;
        }
    }

    /**
     * Validate string-based rule (e.g., "required", "min:3")
     *
     * @param string $attribute Attribute name
     * @param mixed $value Value to validate
     * @param string $rule Rule string
     * @return void
     */
    protected function validateStringRule(string $attribute, mixed $value, string $rule): void
    {
        [$ruleName, $parameters] = $this->parseRule($rule);

        // Get rule class
        $ruleClass = $this->ruleAliases[$ruleName] ?? null;

        if ($ruleClass === null) {
            return; // Unknown rule, skip
        }

        // Instantiate rule
        $ruleInstance = match ($ruleName) {
            'min' => new Min((int) $parameters[0]),
            'max' => new Max((int) $parameters[0]),
            default => new $ruleClass(),
        };

        $ruleInstance->setData($this->data);

        // Validate
        if (! $ruleInstance->passes($attribute, $value)) {
            $message = $this->getCustomMessage($attribute, $ruleName) ?? $ruleInstance->message();
            $this->addError($attribute, $message);
        }
    }

    /**
     * Parse string rule into name and parameters
     *
     * Examples:
     * - "required" -> ["required", []]
     * - "min:3" -> ["min", [3]]
     * - "between:1,10" -> ["between", [1, 10]]
     *
     * @param string $rule Rule string
     * @return array{0: string, 1: array<int|string>} [ruleName, parameters]
     */
    protected function parseRule(string $rule): array
    {
        if (! str_contains($rule, ':')) {
            return [$rule, []];
        }

        [$name, $parameters] = explode(':', $rule, 2);

        return [$name, $parameters !== '' ? explode(',', $parameters) : []];
    }

    /**
     * Get value from data using dot notation
     *
     * Supports:
     * - Simple keys: "email"
     * - Nested keys: "user.email"
     * - Array wildcards: "items.*.price" (returns array of all prices)
     *
     * @param string $key Key in dot notation
     * @return mixed Value
     */
    protected function getValue(string $key): mixed
    {
        // Simple key (no dots)
        if (! str_contains($key, '.')) {
            return $this->data[$key] ?? null;
        }

        // Nested key with dot notation
        $keys = explode('.', $key);
        $value = $this->data;

        foreach ($keys as $segment) {
            if ($segment === '*') {
                // Wildcard: collect values from all array elements
                if (! is_array($value)) {
                    return null;
                }

                $results = [];
                foreach ($value as $item) {
                    if (is_array($item)) {
                        $results[] = $item;
                    }
                }

                return $results;
            }

            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Get custom error message
     *
     * @param string $attribute Attribute name
     * @param string $rule Rule name or class
     * @return string|null Custom message or null
     */
    protected function getCustomMessage(string $attribute, string $rule): ?string
    {
        // Try attribute.rule format
        $key = "{$attribute}.{$rule}";
        if (isset($this->messages[$key])) {
            return $this->messages[$key];
        }

        // Try attribute format
        if (isset($this->messages[$attribute])) {
            return $this->messages[$attribute];
        }

        return null;
    }

    /**
     * Add error message
     *
     * @param string $attribute Attribute name
     * @param string $message Error message
     * @return void
     */
    protected function addError(string $attribute, string $message): void
    {
        if (! isset($this->errors[$attribute])) {
            $this->errors[$attribute] = [];
        }

        $this->errors[$attribute][] = $message;
    }

    /**
     * Check if validation failed
     *
     * @return bool True if validation failed
     */
    public function fails(): bool
    {
        return ! $this->validate();
    }

    /**
     * Get all validation errors
     *
     * @return array<string, array<string>> Errors by attribute
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get validated data (only validated fields)
     *
     * @return array<string, mixed> Validated data
     */
    public function validated(): array
    {
        $validated = [];

        foreach (array_keys($this->rules) as $attribute) {
            $value = $this->getValue($attribute);
            if ($value !== null) {
                $validated[$attribute] = $value;
            }
        }

        return $validated;
    }

    /**
     * Static factory method for convenience
     *
     * @param array<string, mixed> $data Data to validate
     * @param array<string, mixed> $rules Validation rules
     * @param array<string, string> $messages Custom error messages
     * @return static Validator instance
     */
    public static function make(array $data, array $rules, array $messages = []): static
    {
        // @phpstan-ignore-next-line new.static (Required for factory pattern)
        return new static($data, $rules, $messages);
    }
}
