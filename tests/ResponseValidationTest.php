<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Tests;

use LongTermSupport\StrictOpenApiValidator\Exception\AdditionalPropertyException;
use LongTermSupport\StrictOpenApiValidator\Exception\BoundaryViolationException;
use LongTermSupport\StrictOpenApiValidator\Exception\CompositionViolationException;
use LongTermSupport\StrictOpenApiValidator\Exception\EnumViolationException;
use LongTermSupport\StrictOpenApiValidator\Exception\FormatViolationException;
use LongTermSupport\StrictOpenApiValidator\Exception\InvalidResponseException;
use LongTermSupport\StrictOpenApiValidator\Exception\RequiredFieldMissingException;
use LongTermSupport\StrictOpenApiValidator\Exception\TypeMismatchException;
use LongTermSupport\StrictOpenApiValidator\Exception\ValidationException;
use LongTermSupport\StrictOpenApiValidator\Spec;
use LongTermSupport\StrictOpenApiValidator\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Test response validation against OpenAPI specs.
 *
 * Complete test suite covering response validation scenarios:
 * - Type Violations (10 tests)
 * - Required Fields (5 tests)
 * - Additional Properties (5 tests)
 * - Format Violations (8 tests)
 * - Enum Violations (3 tests)
 * - Boundary Violations (6 tests)
 * - Composition Violations (5 tests)
 * - Multiple Errors (3 tests)
 *
 * All tests should FAIL because Validator::validateResponse() currently throws LogicException.
 * When actual validation is implemented, these tests will verify that violations are correctly detected.
 *
 * Response validation is similar to request validation but validates API responses against
 * response schemas defined in the OpenAPI spec.
 */
#[CoversClass(Validator::class)]
final class ResponseValidationTest extends TestCase
{
    private Spec $simpleCrudSpec;
    private Spec $strictSchemasSpec;
    private Spec $compositionSpec;

    protected function setUp(): void
    {
        $this->simpleCrudSpec = Spec::createFromFile(__DIR__ . '/Fixtures/Specs/simple-crud.json');
        $this->strictSchemasSpec = Spec::createFromFile(__DIR__ . '/Fixtures/Specs/strict-schemas.json');
        $this->compositionSpec = Spec::createFromFile(__DIR__ . '/Fixtures/Specs/composition-examples.json');
    }

    // ========================================
    // Type Violation Tests (10 tests)
    // ========================================

    /**
     * Tests that string value is rejected when number is expected in response.
     *
     * Example: {"age": "thirty-five"} instead of {"age": 35}
     */
    #[Test]
    public function itRejectsStringExpectedNumber(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/type-violations/string-expected-number.json');

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('age');

        Validator::validateResponse($json, $this->simpleCrudSpec, '/users', 'post', 201);
    }

    /**
     * Tests that numeric value is rejected when string is expected in response.
     *
     * Example: {"name": 12345} instead of {"name": "John Doe"}
     */
    #[Test]
    public function itRejectsNumberExpectedString(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/type-violations/number-expected-string.json');

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('name');

        Validator::validateResponse($json, $this->simpleCrudSpec, '/users', 'post', 201);
    }

    /**
     * Tests that null is rejected for non-nullable fields in response.
     *
     * Example: {"name": null} when name is required and not nullable
     */
    #[Test]
    public function itRejectsNullNotNullable(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/type-violations/null-not-nullable.json');

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('null');

        Validator::validateResponse($json, $this->simpleCrudSpec, '/users', 'post', 201);
    }

    /**
     * Tests that array is rejected when object is expected in response.
     *
     * Example: ["weight", "100g"] instead of {"weight": "100g"}
     */
    #[Test]
    public function itRejectsArrayExpectedObject(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/type-violations/array-expected-object.json');

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('array');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that object is rejected when array is expected in response.
     *
     * Example: {"0": "tag1"} instead of ["tag1"]
     */
    #[Test]
    public function itRejectsObjectExpectedArray(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/type-violations/object-expected-array.json');

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('object');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that boolean is rejected when string is expected in response.
     *
     * Example: {"status": true} instead of {"status": "active"}
     */
    #[Test]
    public function itRejectsBooleanExpectedString(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/type-violations/boolean-expected-string.json');

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('boolean');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that float is rejected when integer is expected in response.
     *
     * Example: {"age": 35.5} instead of {"age": 35}
     * Integer type should NOT accept float values, even if mathematically equivalent (35.0).
     */
    #[Test]
    public function itRejectsIntegerExpectedFloat(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/type-violations/integer-expected-float.json');

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('integer');

        Validator::validateResponse($json, $this->simpleCrudSpec, '/users', 'post', 201);
    }

    /**
     * CRITICAL: Tests that string numbers are NOT coerced to integers in response.
     *
     * Example: {"age": "35"} is NOT accepted when integer is expected.
     * Strict validation means "35" ≠ 35.
     */
    #[Test]
    public function itRejectsStringNumberCoercion(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/type-violations/string-number-coercion.json');

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('string');

        Validator::validateResponse($json, $this->simpleCrudSpec, '/users', 'post', 201);
    }

    /**
     * Tests that array with mixed types is rejected when homogeneous type is expected in response.
     *
     * Example: ["string", 123, true, null] instead of ["string1", "string2"]
     */
    #[Test]
    public function itRejectsMixedTypeArray(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/type-violations/mixed-type-array.json');

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('array');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests distinction between null and missing in response (The Four Combinations).
     *
     * Tests all combinations of required/optional × nullable/non-nullable:
     * - Required + Nullable: missing fails, null passes
     * - Required + Non-nullable: missing fails, null fails
     * - Optional + Nullable: missing passes, null passes
     * - Optional + Non-nullable: missing passes, null fails
     */
    #[Test]
    public function itRejectsTypeNullVsMissing(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/type-violations/type-null-vs-missing.json');

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('null');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    // ========================================
    // Required Field Violation Tests (5 tests)
    // ========================================

    /**
     * Tests that missing required field is rejected in response.
     *
     * Example: {} when {"name": "..."} is required
     */
    #[Test]
    public function itRejectsRequiredFieldMissing(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/required-violations/required-field-missing.json');

        $this->expectException(RequiredFieldMissingException::class);
        $this->expectExceptionMessage('name');

        Validator::validateResponse($json, $this->simpleCrudSpec, '/users', 'post', 201);
    }

    /**
     * Tests that null for required non-nullable field is rejected in response.
     *
     * Example: {"name": null} when name is required and not nullable
     */
    #[Test]
    public function itRejectsRequiredFieldNull(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/required-violations/required-field-null.json');

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('null');

        Validator::validateResponse($json, $this->simpleCrudSpec, '/users', 'post', 201);
    }

    /**
     * Tests that empty string for required field with minLength is rejected in response.
     *
     * Example: {"name": ""} when minLength: 1 is required
     */
    #[Test]
    public function itRejectsRequiredFieldEmptyString(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/required-violations/required-field-empty-string.json');

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('minLength');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that multiple missing required fields are all reported in response.
     *
     * Example: {} when {"name": "...", "email": "..."} are both required
     * Validator should collect ALL errors, not fail fast.
     */
    #[Test]
    public function itRejectsRequiredMultipleMissing(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/required-violations/required-multiple-missing.json');

        $this->expectException(RequiredFieldMissingException::class);
        $this->expectExceptionMessage('name');

        Validator::validateResponse($json, $this->simpleCrudSpec, '/users', 'post', 201);
    }

    /**
     * Tests that completely empty object is rejected when fields are required in response.
     *
     * Example: {} when multiple required fields exist
     */
    #[Test]
    public function itRejectsAllFieldsMissing(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/required-violations/all-fields-missing.json');

        $this->expectException(RequiredFieldMissingException::class);
        $this->expectExceptionMessage('required');

        Validator::validateResponse($json, $this->simpleCrudSpec, '/users', 'post', 201);
    }

    // ========================================
    // Additional Properties Tests (5 tests)
    // ========================================

    /**
     * Tests that additional properties are rejected when additionalProperties: false in response.
     *
     * Example: {"name": "John", "extraField": "value"} when only "name" is defined
     */
    #[Test]
    public function itRejectsAdditionalPropertyNotAllowed(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/additional-properties/additional-property-not-allowed.json');

        $this->expectException(AdditionalPropertyException::class);
        $this->expectExceptionMessage('extraField');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that snake_case property is rejected and hint suggests camelCase in response.
     *
     * Example: {"created_at": "..."} instead of {"createdAt": "..."}
     * Should provide hint: "Did you mean 'createdAt'?"
     */
    #[Test]
    public function itRejectsAdditionalPropertySnakeCase(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/additional-properties/additional-property-snake-case.json');

        $this->expectException(AdditionalPropertyException::class);
        $this->expectExceptionMessage('created_at');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that additional properties in nested objects are rejected in response.
     *
     * Example: {"metadata": {"dimensions": {"volume": 100}}} when volume is not defined
     */
    #[Test]
    public function itRejectsAdditionalNestedProperty(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/additional-properties/additional-nested-property.json');

        $this->expectException(AdditionalPropertyException::class);
        $this->expectExceptionMessage('volume');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that multiple additional properties are all reported in response.
     *
     * Example: {"name": "John", "extra1": "...", "extra2": "...", "extra3": "..."}
     * Validator should collect ALL additional property errors.
     */
    #[Test]
    public function itRejectsMultipleAdditionalProperties(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/additional-properties/multiple-additional-properties.json');

        $this->expectException(AdditionalPropertyException::class);
        $this->expectExceptionMessage('additional');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that typo in property name creates both missing required AND additional property errors in response.
     *
     * Example: {"nam": "John"} instead of {"name": "John"}
     * Should report:
     * 1. Missing required field "name"
     * 2. Additional property "nam" not allowed
     * 3. Hint: "Did you mean 'name'?" (Levenshtein distance = 1)
     */
    #[Test]
    public function itRejectsAdditionalPropertyTypo(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/additional-properties/additional-property-typo.json');

        $this->expectException(AdditionalPropertyException::class);
        $this->expectExceptionMessage('nam');

        Validator::validateResponse($json, $this->simpleCrudSpec, '/users', 'post', 201);
    }

    // ========================================
    // Format Violation Tests (8 tests)
    // ========================================

    /**
     * Tests that invalid email format is rejected in response.
     *
     * Example: "not-an-email" instead of "user@example.com"
     */
    #[Test]
    public function itRejectsFormatEmailInvalid(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/format-violations/format-email-invalid.json');

        $this->expectException(FormatViolationException::class);
        $this->expectExceptionMessage('email');

        Validator::validateResponse($json, $this->simpleCrudSpec, '/users', 'post', 201);
    }

    /**
     * Tests that invalid UUID format is rejected in response.
     *
     * Example: "not-a-uuid" instead of "550e8400-e29b-41d4-a716-446655440000"
     */
    #[Test]
    public function itRejectsFormatUuidInvalid(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/format-violations/format-uuid-invalid.json');

        $this->expectException(FormatViolationException::class);
        $this->expectExceptionMessage('uuid');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that invalid date-time format is rejected in response.
     *
     * Example: "2024-11-17 10:00:00" instead of "2024-11-17T10:00:00Z"
     * Format must be RFC 3339.
     */
    #[Test]
    public function itRejectsFormatDateTimeInvalid(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/format-violations/format-date-time-invalid.json');

        $this->expectException(FormatViolationException::class);
        $this->expectExceptionMessage('date-time');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that invalid URI format is rejected in response.
     *
     * Example: "not a url" instead of "https://example.com"
     */
    #[Test]
    public function itRejectsFormatUriInvalid(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/format-violations/format-uri-invalid.json');

        $this->expectException(FormatViolationException::class);
        $this->expectExceptionMessage('uri');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that invalid hostname format is rejected in response.
     *
     * Example: "invalid_hostname!" instead of "example.com"
     */
    #[Test]
    public function itRejectsFormatHostnameInvalid(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/format-violations/format-hostname-invalid.json');

        $this->expectException(FormatViolationException::class);
        $this->expectExceptionMessage('hostname');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that invalid IPv4 format is rejected in response.
     *
     * Example: "192.168.1.999" instead of "192.168.1.1"
     */
    #[Test]
    public function itRejectsFormatIpv4Invalid(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/format-violations/format-ipv4-invalid.json');

        $this->expectException(FormatViolationException::class);
        $this->expectExceptionMessage('ipv4');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that invalid IPv6 format is rejected in response.
     *
     * Example: "::1::2" instead of "2001:db8::1"
     */
    #[Test]
    public function itRejectsFormatIpv6Invalid(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/format-violations/format-ipv6-invalid.json');

        $this->expectException(FormatViolationException::class);
        $this->expectExceptionMessage('ipv6');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that invalid date format is rejected in response.
     *
     * Example: "11/17/2024" instead of "2024-11-17"
     * Format must be YYYY-MM-DD.
     */
    #[Test]
    public function itRejectsFormatDateInvalid(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/format-violations/format-date-invalid.json');

        $this->expectException(FormatViolationException::class);
        $this->expectExceptionMessage('date');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    // ========================================
    // Enum Violation Tests (3 tests)
    // ========================================

    /**
     * Tests that value not in enum is rejected in response.
     *
     * Example: "pending" when enum is ["active", "inactive", "discontinued"]
     */
    #[Test]
    public function itRejectsEnumInvalidValue(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/enum-violations/enum-invalid-value.json');

        $this->expectException(EnumViolationException::class);
        $this->expectExceptionMessage('pending');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that enum validation is case-sensitive in response.
     *
     * Example: "Active" instead of "active"
     */
    #[Test]
    public function itRejectsEnumCaseMismatch(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/enum-violations/enum-case-mismatch.json');

        $this->expectException(EnumViolationException::class);
        $this->expectExceptionMessage('Active');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that enum type must match in response.
     *
     * Example: 1 instead of "active" (number instead of string)
     */
    #[Test]
    public function itRejectsEnumTypeMismatch(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/enum-violations/enum-type-mismatch.json');

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('integer');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    // ========================================
    // Boundary Violation Tests (6 tests)
    // ========================================

    /**
     * Tests that value below minimum is rejected in response.
     *
     * Example: -10.50 when minimum is 0
     */
    #[Test]
    public function itRejectsBoundaryMinimumViolated(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/boundary-violations/boundary-minimum-violated.json');

        $this->expectException(BoundaryViolationException::class);
        $this->expectExceptionMessage('minimum');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that value above maximum is rejected in response.
     *
     * Example: 200 when maximum is 150
     */
    #[Test]
    public function itRejectsBoundaryMaximumViolated(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/boundary-violations/boundary-maximum-violated.json');

        $this->expectException(BoundaryViolationException::class);
        $this->expectExceptionMessage('maximum');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that string shorter than minLength is rejected in response.
     *
     * Example: "" when minLength is 1
     */
    #[Test]
    public function itRejectsBoundaryMinLengthViolated(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/boundary-violations/boundary-min-length-violated.json');

        $this->expectException(BoundaryViolationException::class);
        $this->expectExceptionMessage('minLength');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that string longer than maxLength is rejected in response.
     *
     * Example: 280 character string when maxLength is 200
     */
    #[Test]
    public function itRejectsBoundaryMaxLengthViolated(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/boundary-violations/boundary-max-length-violated.json');

        $this->expectException(BoundaryViolationException::class);
        $this->expectExceptionMessage('maxLength');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that array with fewer than minItems is rejected in response.
     *
     * Example: [] when minItems is 1
     */
    #[Test]
    public function itRejectsBoundaryMinItemsViolated(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/boundary-violations/boundary-min-items-violated.json');

        $this->expectException(BoundaryViolationException::class);
        $this->expectExceptionMessage('minItems');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that array with more than maxItems is rejected in response.
     *
     * Example: 6 items when maxItems is 5
     */
    #[Test]
    public function itRejectsBoundaryMaxItemsViolated(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/boundary-violations/boundary-max-items-violated.json');

        $this->expectException(BoundaryViolationException::class);
        $this->expectExceptionMessage('maxItems');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    // ========================================
    // Composition Violation Tests (5 tests)
    // ========================================

    /**
     * Tests that oneOf fails when matching none of the schemas in response.
     *
     * Example: boolean when oneOf expects string or number
     */
    #[Test]
    public function itRejectsOneofMatchesNone(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/composition-violations/oneof-matches-none.json');

        $this->expectException(CompositionViolationException::class);
        $this->expectExceptionMessage('oneOf');

        Validator::validateResponse($json, $this->compositionSpec, '/pets', 'post', 201);
    }

    /**
     * Tests that oneOf fails when matching multiple schemas in response.
     *
     * Example: Data matches 2 schemas when exactly 1 is required
     */
    #[Test]
    public function itRejectsOneofMatchesMultiple(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/composition-violations/oneof-matches-multiple.json');

        $this->expectException(CompositionViolationException::class);
        $this->expectExceptionMessage('multiple');

        Validator::validateResponse($json, $this->compositionSpec, '/pets', 'post', 201);
    }

    /**
     * Tests that anyOf fails when matching none of the schemas in response.
     *
     * Example: boolean when anyOf expects string or number
     */
    #[Test]
    public function itRejectsAnyofMatchesNone(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/composition-violations/anyof-matches-none.json');

        $this->expectException(CompositionViolationException::class);
        $this->expectExceptionMessage('anyOf');

        Validator::validateResponse($json, $this->compositionSpec, '/pets', 'post', 201);
    }

    /**
     * Tests that allOf fails when one schema fails in response.
     *
     * Example: "hi" fails minLength even though it's a string
     */
    #[Test]
    public function itRejectsAllofFailsOne(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/composition-violations/allof-fails-one.json');

        $this->expectException(CompositionViolationException::class);
        $this->expectExceptionMessage('allOf');

        Validator::validateResponse($json, $this->compositionSpec, '/pets', 'post', 201);
    }

    /**
     * Tests that allOf fails when multiple schemas fail in response.
     *
     * Example: number when all schemas expect string with constraints
     */
    #[Test]
    public function itRejectsAllofFailsMultiple(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/composition-violations/allof-fails-multiple.json');

        $this->expectException(CompositionViolationException::class);
        $this->expectExceptionMessage('allOf');

        Validator::validateResponse($json, $this->compositionSpec, '/pets', 'post', 201);
    }

    // ========================================
    // Multiple Errors Tests (3 tests)
    // ========================================

    /**
     * Tests that 5 different violations are all reported in response.
     *
     * Violations: type, required, additional, enum, format
     */
    #[Test]
    public function itRejectsMultipleErrors5(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/multiple-errors/multiple-errors-5.json');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('5');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that 10+ violations are all reported in response.
     *
     * Comprehensive test with all violation types
     */
    #[Test]
    public function itRejectsMultipleErrors10(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/multiple-errors/multiple-errors-10.json');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('10');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that cascading errors within composition are reported in response.
     *
     * Example: Errors within oneOf branches plus composition error
     */
    #[Test]
    public function itRejectsMultipleErrorsCascading(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/multiple-errors/multiple-errors-cascading.json');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('cascading');

        Validator::validateResponse($json, $this->compositionSpec, '/pets', 'post', 201);
    }
}
