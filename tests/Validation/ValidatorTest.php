<?php

declare(strict_types=1);

namespace Elarion\Tests\Validation;

use Elarion\Validation\Rule;
use Elarion\Validation\Rules\Email;
use Elarion\Validation\Rules\Max;
use Elarion\Validation\Rules\Min;
use Elarion\Validation\Rules\Required;
use Elarion\Validation\Validator;
use PHPUnit\Framework\TestCase;

/**
 * Validator Tests
 *
 * Comprehensive tests for validation system.
 */
final class ValidatorTest extends TestCase
{
    // ========================================
    // REQUIRED RULE
    // ========================================

    public function test_required_passes_with_non_empty_string(): void
    {
        $validator = Validator::make(
            ['name' => 'John'],
            ['name' => 'required']
        );

        $this->assertTrue($validator->validate());
    }

    public function test_required_fails_with_null(): void
    {
        $validator = Validator::make(
            ['name' => null],
            ['name' => 'required']
        );

        $this->assertFalse($validator->validate());
        $this->assertArrayHasKey('name', $validator->errors());
    }

    public function test_required_fails_with_empty_string(): void
    {
        $validator = Validator::make(
            ['name' => ''],
            ['name' => 'required']
        );

        $this->assertFalse($validator->validate());
    }

    public function test_required_fails_with_whitespace_only(): void
    {
        $validator = Validator::make(
            ['name' => '   '],
            ['name' => 'required']
        );

        $this->assertFalse($validator->validate());
    }

    public function test_required_fails_with_empty_array(): void
    {
        $validator = Validator::make(
            ['items' => []],
            ['items' => 'required']
        );

        $this->assertFalse($validator->validate());
    }

    public function test_required_passes_with_non_empty_array(): void
    {
        $validator = Validator::make(
            ['items' => [1, 2, 3]],
            ['items' => 'required']
        );

        $this->assertTrue($validator->validate());
    }

    // ========================================
    // EMAIL RULE
    // ========================================

    public function test_email_passes_with_valid_email(): void
    {
        $validator = Validator::make(
            ['email' => 'john@example.com'],
            ['email' => 'email']
        );

        $this->assertTrue($validator->validate());
    }

    public function test_email_fails_with_invalid_email(): void
    {
        $validator = Validator::make(
            ['email' => 'not-an-email'],
            ['email' => 'email']
        );

        $this->assertFalse($validator->validate());
        $this->assertArrayHasKey('email', $validator->errors());
    }

    public function test_email_fails_with_non_string(): void
    {
        $validator = Validator::make(
            ['email' => 123],
            ['email' => 'email']
        );

        $this->assertFalse($validator->validate());
    }

    // ========================================
    // MIN RULE
    // ========================================

    public function test_min_passes_with_string_meeting_length(): void
    {
        $validator = Validator::make(
            ['name' => 'John'],
            ['name' => 'min:3']
        );

        $this->assertTrue($validator->validate());
    }

    public function test_min_fails_with_string_below_length(): void
    {
        $validator = Validator::make(
            ['name' => 'Jo'],
            ['name' => 'min:3']
        );

        $this->assertFalse($validator->validate());
    }

    public function test_min_passes_with_number_meeting_value(): void
    {
        $validator = Validator::make(
            ['age' => 18],
            ['age' => 'min:18']
        );

        $this->assertTrue($validator->validate());
    }

    public function test_min_fails_with_number_below_value(): void
    {
        $validator = Validator::make(
            ['age' => 17],
            ['age' => 'min:18']
        );

        $this->assertFalse($validator->validate());
    }

    public function test_min_passes_with_array_meeting_count(): void
    {
        $validator = Validator::make(
            ['items' => [1, 2, 3]],
            ['items' => 'min:2']
        );

        $this->assertTrue($validator->validate());
    }

    public function test_min_fails_with_array_below_count(): void
    {
        $validator = Validator::make(
            ['items' => [1]],
            ['items' => 'min:2']
        );

        $this->assertFalse($validator->validate());
    }

    // ========================================
    // MAX RULE
    // ========================================

    public function test_max_passes_with_string_within_length(): void
    {
        $validator = Validator::make(
            ['name' => 'John'],
            ['name' => 'max:10']
        );

        $this->assertTrue($validator->validate());
    }

    public function test_max_fails_with_string_exceeding_length(): void
    {
        $validator = Validator::make(
            ['name' => 'Very Long Name'],
            ['name' => 'max:5']
        );

        $this->assertFalse($validator->validate());
    }

    public function test_max_passes_with_number_within_value(): void
    {
        $validator = Validator::make(
            ['age' => 100],
            ['age' => 'max:150']
        );

        $this->assertTrue($validator->validate());
    }

    public function test_max_fails_with_number_exceeding_value(): void
    {
        $validator = Validator::make(
            ['age' => 200],
            ['age' => 'max:150']
        );

        $this->assertFalse($validator->validate());
    }

    public function test_max_passes_with_array_within_count(): void
    {
        $validator = Validator::make(
            ['items' => [1, 2]],
            ['items' => 'max:5']
        );

        $this->assertTrue($validator->validate());
    }

    public function test_max_fails_with_array_exceeding_count(): void
    {
        $validator = Validator::make(
            ['items' => [1, 2, 3, 4, 5, 6]],
            ['items' => 'max:5']
        );

        $this->assertFalse($validator->validate());
    }

    // ========================================
    // TYPE RULES
    // ========================================

    public function test_string_passes_with_string(): void
    {
        $validator = Validator::make(
            ['name' => 'John'],
            ['name' => 'string']
        );

        $this->assertTrue($validator->validate());
    }

    public function test_string_fails_with_non_string(): void
    {
        $validator = Validator::make(
            ['name' => 123],
            ['name' => 'string']
        );

        $this->assertFalse($validator->validate());
    }

    public function test_integer_passes_with_integer(): void
    {
        $validator = Validator::make(
            ['age' => 25],
            ['age' => 'integer']
        );

        $this->assertTrue($validator->validate());
    }

    public function test_integer_fails_with_non_integer(): void
    {
        $validator = Validator::make(
            ['age' => '25'],
            ['age' => 'integer']
        );

        $this->assertFalse($validator->validate());
    }

    public function test_numeric_passes_with_int(): void
    {
        $validator = Validator::make(
            ['value' => 42],
            ['value' => 'numeric']
        );

        $this->assertTrue($validator->validate());
    }

    public function test_numeric_passes_with_float(): void
    {
        $validator = Validator::make(
            ['value' => 42.5],
            ['value' => 'numeric']
        );

        $this->assertTrue($validator->validate());
    }

    public function test_numeric_passes_with_numeric_string(): void
    {
        $validator = Validator::make(
            ['value' => '42'],
            ['value' => 'numeric']
        );

        $this->assertTrue($validator->validate());
    }

    public function test_numeric_fails_with_non_numeric(): void
    {
        $validator = Validator::make(
            ['value' => 'abc'],
            ['value' => 'numeric']
        );

        $this->assertFalse($validator->validate());
    }

    public function test_boolean_passes_with_true(): void
    {
        $validator = Validator::make(
            ['active' => true],
            ['active' => 'boolean']
        );

        $this->assertTrue($validator->validate());
    }

    public function test_boolean_passes_with_false(): void
    {
        $validator = Validator::make(
            ['active' => false],
            ['active' => 'boolean']
        );

        $this->assertTrue($validator->validate());
    }

    public function test_boolean_passes_with_1_and_0(): void
    {
        $validator = Validator::make(
            ['active' => 1, 'inactive' => 0],
            ['active' => 'boolean', 'inactive' => 'boolean']
        );

        $this->assertTrue($validator->validate());
    }

    public function test_boolean_passes_with_string_1_and_0(): void
    {
        $validator = Validator::make(
            ['active' => '1', 'inactive' => '0'],
            ['active' => 'boolean', 'inactive' => 'boolean']
        );

        $this->assertTrue($validator->validate());
    }

    public function test_boolean_fails_with_non_boolean(): void
    {
        $validator = Validator::make(
            ['active' => 'yes'],
            ['active' => 'boolean']
        );

        $this->assertFalse($validator->validate());
    }

    public function test_array_passes_with_array(): void
    {
        $validator = Validator::make(
            ['items' => [1, 2, 3]],
            ['items' => 'array']
        );

        $this->assertTrue($validator->validate());
    }

    public function test_array_fails_with_non_array(): void
    {
        $validator = Validator::make(
            ['items' => 'not-array'],
            ['items' => 'array']
        );

        $this->assertFalse($validator->validate());
    }

    // ========================================
    // MULTIPLE RULES
    // ========================================

    public function test_multiple_rules_with_pipe_separator(): void
    {
        $validator = Validator::make(
            ['email' => 'john@example.com'],
            ['email' => 'required|email']
        );

        $this->assertTrue($validator->validate());
    }

    public function test_multiple_rules_with_array(): void
    {
        $validator = Validator::make(
            ['email' => 'john@example.com'],
            ['email' => ['required', 'email']]
        );

        $this->assertTrue($validator->validate());
    }

    public function test_multiple_rules_fails_when_any_fails(): void
    {
        $validator = Validator::make(
            ['email' => ''],
            ['email' => 'required|email']
        );

        $this->assertFalse($validator->validate());
        $this->assertArrayHasKey('email', $validator->errors());
    }

    // ========================================
    // RULE INSTANCES
    // ========================================

    public function test_rule_instance(): void
    {
        $validator = Validator::make(
            ['email' => 'john@example.com'],
            ['email' => [new Required(), new Email()]]
        );

        $this->assertTrue($validator->validate());
    }

    public function test_rule_instance_with_parameters(): void
    {
        $validator = Validator::make(
            ['age' => 25],
            ['age' => [new Min(18), new Max(100)]]
        );

        $this->assertTrue($validator->validate());
    }

    // ========================================
    // CUSTOM RULES (CLOSURES)
    // ========================================

    public function test_closure_rule_passes(): void
    {
        $validator = Validator::make(
            ['name' => 'John'],
            ['name' => [
                fn ($attribute, $value) => strlen($value) > 2,
            ]]
        );

        $this->assertTrue($validator->validate());
    }

    public function test_closure_rule_fails(): void
    {
        $validator = Validator::make(
            ['name' => 'Jo'],
            ['name' => [
                fn ($attribute, $value) => strlen($value) > 2,
            ]]
        );

        $this->assertFalse($validator->validate());
        $this->assertArrayHasKey('name', $validator->errors());
    }

    public function test_closure_rule_with_data_access(): void
    {
        $validator = Validator::make(
            ['password' => 'secret', 'password_confirmation' => 'secret'],
            ['password_confirmation' => [
                fn ($attribute, $value, $data) => $value === $data['password'],
            ]]
        );

        $this->assertTrue($validator->validate());
    }

    // ========================================
    // CUSTOM ERROR MESSAGES
    // ========================================

    public function test_custom_message_for_attribute(): void
    {
        $validator = Validator::make(
            ['email' => ''],
            ['email' => 'required'],
            ['email' => 'Please provide your email address.']
        );

        $validator->validate();

        $errors = $validator->errors();
        $this->assertSame('Please provide your email address.', $errors['email'][0]);
    }

    public function test_custom_message_for_attribute_and_rule(): void
    {
        $validator = Validator::make(
            ['email' => 'invalid'],
            ['email' => 'email'],
            ['email.email' => 'The email format is invalid.']
        );

        $validator->validate();

        $errors = $validator->errors();
        $this->assertSame('The email format is invalid.', $errors['email'][0]);
    }

    // ========================================
    // NESTED ARRAYS
    // ========================================

    public function test_nested_array_validation(): void
    {
        $validator = Validator::make(
            ['user' => ['name' => 'John', 'email' => 'john@example.com']],
            ['user.name' => 'required', 'user.email' => 'email']
        );

        $this->assertTrue($validator->validate());
    }

    public function test_nested_array_validation_fails(): void
    {
        $validator = Validator::make(
            ['user' => ['name' => '', 'email' => 'invalid']],
            ['user.name' => 'required', 'user.email' => 'email']
        );

        $this->assertFalse($validator->validate());
        $this->assertArrayHasKey('user.name', $validator->errors());
        $this->assertArrayHasKey('user.email', $validator->errors());
    }

    // ========================================
    // VALIDATOR METHODS
    // ========================================

    public function test_fails_method(): void
    {
        $validator = Validator::make(
            ['email' => 'invalid'],
            ['email' => 'email']
        );

        $this->assertTrue($validator->fails());
    }

    public function test_errors_method_returns_all_errors(): void
    {
        $validator = Validator::make(
            ['name' => '', 'email' => 'invalid'],
            ['name' => 'required', 'email' => 'email']
        );

        $validator->validate();

        $errors = $validator->errors();

        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertIsArray($errors['name']);
        $this->assertIsArray($errors['email']);
    }

    public function test_validated_method_returns_only_validated_fields(): void
    {
        $validator = Validator::make(
            ['name' => 'John', 'email' => 'john@example.com', 'extra' => 'ignored'],
            ['name' => 'required', 'email' => 'email']
        );

        $validator->validate();

        $validated = $validator->validated();

        $this->assertArrayHasKey('name', $validated);
        $this->assertArrayHasKey('email', $validated);
        $this->assertArrayNotHasKey('extra', $validated);
    }

    public function test_make_factory_method(): void
    {
        $validator = Validator::make(
            ['email' => 'john@example.com'],
            ['email' => 'email']
        );

        $this->assertInstanceOf(Validator::class, $validator);
        $this->assertTrue($validator->validate());
    }

    // ========================================
    // ERROR MESSAGES
    // ========================================

    public function test_error_messages_contain_attribute_names(): void
    {
        $validator = Validator::make(
            ['email' => 'invalid'],
            ['email' => 'email']
        );

        $validator->validate();

        $errors = $validator->errors();
        $this->assertStringContainsString('email', $errors['email'][0]);
    }

    // ========================================
    // CUSTOM RULE CLASS
    // ========================================

    public function test_custom_rule_class(): void
    {
        $validator = Validator::make(
            ['name' => 'John'],
            ['name' => [new TestCustomRule()]]
        );

        $this->assertTrue($validator->validate());
    }

    public function test_custom_rule_class_fails(): void
    {
        $validator = Validator::make(
            ['name' => 'Jo'],
            ['name' => [new TestCustomRule()]]
        );

        $this->assertFalse($validator->validate());
    }
}

// ========================================
// TEST HELPER CLASSES
// ========================================

/**
 * Test Custom Rule
 *
 * Name must be at least 3 characters.
 */
class TestCustomRule implements Rule
{
    protected array $data = [];

    public function passes(string $attribute, mixed $value): bool
    {
        return is_string($value) && strlen($value) >= 3;
    }

    public function message(): string
    {
        return 'The name must be at least 3 characters.';
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }
}
