<?php

declare(strict_types=1);

namespace Elarion\Validation;

/**
 * Abstract Validation Rule
 *
 * Provides base functionality for validation rules including message handling.
 */
abstract class AbstractRule implements Rule
{
    /**
     * All data under validation
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * Current attribute being validated
     *
     * @var string
     */
    protected string $attribute = '';

    /**
     * Current value being validated
     *
     * @var mixed
     */
    protected mixed $value = null;

    /**
     * {@inheritdoc}
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * Get the validation error message template
     *
     * Override this method to provide custom message templates.
     *
     * @return string Message template with :attribute, :value placeholders
     */
    abstract protected function getMessageTemplate(): string;

    /**
     * {@inheritdoc}
     */
    public function message(): string
    {
        $message = $this->getMessageTemplate();

        return $this->replacePlaceholders($message);
    }

    /**
     * Replace message placeholders
     *
     * @param string $message Message template
     * @return string Message with replaced placeholders
     */
    protected function replacePlaceholders(string $message): string
    {
        $replacements = $this->getReplacements();

        foreach ($replacements as $key => $value) {
            $message = str_replace(":{$key}", (string) $value, $message);
        }

        return $message;
    }

    /**
     * Get placeholder replacements
     *
     * Override to add custom placeholders.
     *
     * @return array<string, mixed> Placeholder replacements
     */
    protected function getReplacements(): array
    {
        return [
            'attribute' => $this->attribute,
            'value' => $this->formatValue($this->value),
        ];
    }

    /**
     * Format value for display in error messages
     *
     * @param mixed $value Value to format
     * @return string Formatted value
     */
    protected function formatValue(mixed $value): string
    {
        if (is_null($value)) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return 'array';
        }

        if (is_object($value)) {
            return get_class($value);
        }

        return (string) $value;
    }
}
