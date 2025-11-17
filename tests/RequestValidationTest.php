<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Tests;

use LongTermSupport\StrictOpenApiValidator\Exception\AdditionalPropertyException;
use LongTermSupport\StrictOpenApiValidator\Exception\BoundaryViolationException;
use LongTermSupport\StrictOpenApiValidator\Exception\CompositionViolationException;
use LongTermSupport\StrictOpenApiValidator\Exception\DiscriminatorViolationException;
use LongTermSupport\StrictOpenApiValidator\Exception\EnumViolationException;
use LongTermSupport\StrictOpenApiValidator\Exception\FormatViolationException;
use LongTermSupport\StrictOpenApiValidator\Exception\PatternViolationException;
use LongTermSupport\StrictOpenApiValidator\Exception\RequiredFieldMissingException;
use LongTermSupport\StrictOpenApiValidator\Exception\TypeMismatchException;
use LongTermSupport\StrictOpenApiValidator\Exception\ValidationException;
use LongTermSupport\StrictOpenApiValidator\Spec;
use LongTermSupport\StrictOpenApiValidator\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Test request validation against OpenAPI specs.
 *
 * Complete test suite covering all validation scenarios:
 * - Type Violations (10 tests)
 * - Required Fields (5 tests)
 * - Additional Properties (5 tests)
 * - Format Violations (10 tests)
 * - Enum Violations (5 tests)
 * - Boundary Violations (10 tests)
 * - Pattern Violations (5 tests)
 * - Composition Violations (8 tests)
 * - Discriminator Violations (3 tests)
 * - Multiple Errors (3 tests)
 *
 * All tests should FAIL because Validator::validateRequest() currently throws LogicException.
 * When actual validation is implemented, these tests will verify that violations are correctly detected.
 */
#[CoversClass(Validator::class)]
final class RequestValidationTest extends TestCase
{
    private Spec $simpleCrudSpec;
    private Spec $strictSchemasSpec;
    private Spec $compositionSpec;
    private Spec $edgeCasesSpec;

    protected function setUp(): void
    {
        $this->simpleCrudSpec = Spec::createFromFile(__DIR__ . '/Fixtures/Specs/simple-crud.json');
        $this->strictSchemasSpec = Spec::createFromFile(__DIR__ . '/Fixtures/Specs/strict-schemas.json');
        $this->compositionSpec = Spec::createFromFile(__DIR__ . '/Fixtures/Specs/composition-examples.json');
        $this->edgeCasesSpec = Spec::createFromFile(__DIR__ . '/Fixtures/Specs/edge-cases.json');
    }

    // ========================================
    // Type Violation Tests (10 tests)
    // ========================================

    /**
     * Tests that string value is rejected when number is expected.
     *
     * Example: {"age": "thirty-five"} instead of {"age": 35}
     */
    #[Test]
    public function itRejectsStringExpectedNumber(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/type-violations/string-expected-number.json');

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('age');

        Validator::validateRequest($json, $this->simpleCrudSpec);
    }

    /**
     * Tests that numeric value is rejected when string is expected.
     *
     * Example: {"name": 12345} instead of {"name": "John Doe"}
     */
    #[Test]
    public function itRejectsNumberExpectedString(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/type-violations/number-expected-string.json');

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('name');

        Validator::validateRequest($json, $this->simpleCrudSpec);
    }

    /**
     * Tests that null is rejected for non-nullable fields.
     *
     * Example: {"name": null} when name is required and not nullable
     */
    #[Test]
    public function itRejectsNullNotNullable(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/type-violations/null-not-nullable.json');

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('null');

        Validator::validateRequest($json, $this->simpleCrudSpec);
    }

    /**
     * Tests that array is rejected when object is expected.
     *
     * Example: ["weight", "100g"] instead of {"weight": "100g"}
     */
    #[Test]
    public function itRejectsArrayExpectedObject(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/type-violations/array-expected-object.json');

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('array');

        Validator::validateRequest($json, $this->strictSchemasSpec);
    }

    /**
     * Tests that object is rejected when array is expected.
     *
     * Example: {"0": "tag1"} instead of ["tag1"]
     */
    #[Test]
    public function itRejectsObjectExpectedArray(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/type-violations/object-expected-array.json');

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('object');

        Validator::validateRequest($json, $this->strictSchemasSpec);
    }

    /**
     * Tests that boolean is rejected when string is expected.
     *
     * Example: {"status": true} instead of {"status": "active"}
     */
    #[Test]
    public function itRejectsBooleanExpectedString(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/type-violations/boolean-expected-string.json');

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('boolean');

        Validator::validateRequest($json, $this->strictSchemasSpec);
    }

    /**
     * Tests that float is rejected when integer is expected.
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

        Validator::validateRequest($json, $this->simpleCrudSpec);
    }

    /**
     * CRITICAL: Tests that string numbers are NOT coerced to integers.
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

        Validator::validateRequest($json, $this->simpleCrudSpec);
    }

    /**
     * Tests that array with mixed types is rejected when homogeneous type is expected.
     *
     * Example: ["string", 123, true, null] instead of ["string1", "string2"]
     */
    #[Test]
    public function itRejectsMixedTypeArray(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/type-violations/mixed-type-array.json');

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('array');

        Validator::validateRequest($json, $this->strictSchemasSpec);
    }

    /**
     * Tests distinction between null and missing (The Four Combinations).
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

        Validator::validateRequest($json, $this->strictSchemasSpec);
    }

    // ========================================
    // Required Field Violation Tests (5 tests)
    // ========================================

    /**
     * Tests that missing required field is rejected.
     *
     * Example: {} when {"name": "..."} is required
     */
    #[Test]
    public function itRejectsRequiredFieldMissing(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/required-violations/required-field-missing.json');

        $this->expectException(RequiredFieldMissingException::class);
        $this->expectExceptionMessage('name');

        Validator::validateRequest($json, $this->simpleCrudSpec);
    }

    /**
     * Tests that null for required non-nullable field is rejected.
     *
     * Example: {"name": null} when name is required and not nullable
     */
    #[Test]
    public function itRejectsRequiredFieldNull(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/required-violations/required-field-null.json');

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('null');

        Validator::validateRequest($json, $this->simpleCrudSpec);
    }

    /**
     * Tests that empty string for required field with minLength is rejected.
     *
     * Example: {"name": ""} when minLength: 1 is required
     */
    #[Test]
    public function itRejectsRequiredFieldEmptyString(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/required-violations/required-field-empty-string.json');

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('minLength');

        Validator::validateRequest($json, $this->strictSchemasSpec);
    }

    /**
     * Tests that multiple missing required fields are all reported.
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

        Validator::validateRequest($json, $this->simpleCrudSpec);
    }

    /**
     * Tests that completely empty object is rejected when fields are required.
     *
     * Example: {} when multiple required fields exist
     */
    #[Test]
    public function itRejectsAllFieldsMissing(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/required-violations/all-fields-missing.json');

        $this->expectException(RequiredFieldMissingException::class);
        $this->expectExceptionMessage('required');

        Validator::validateRequest($json, $this->simpleCrudSpec);
    }

    // ========================================
    // Additional Properties Tests (5 tests)
    // ========================================

    /**
     * Tests that additional properties are rejected when additionalProperties: false.
     *
     * Example: {"name": "John", "extraField": "value"} when only "name" is defined
     */
    #[Test]
    public function itRejectsAdditionalPropertyNotAllowed(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/additional-properties/additional-property-not-allowed.json');

        $this->expectException(AdditionalPropertyException::class);
        $this->expectExceptionMessage('extraField');

        Validator::validateRequest($json, $this->strictSchemasSpec);
    }

    /**
     * Tests that snake_case property is rejected and hint suggests camelCase.
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

        Validator::validateRequest($json, $this->strictSchemasSpec);
    }

    /**
     * Tests that additional properties in nested objects are rejected.
     *
     * Example: {"metadata": {"dimensions": {"volume": 100}}} when volume is not defined
     */
    #[Test]
    public function itRejectsAdditionalNestedProperty(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/additional-properties/additional-nested-property.json');

        $this->expectException(AdditionalPropertyException::class);
        $this->expectExceptionMessage('volume');

        Validator::validateRequest($json, $this->strictSchemasSpec);
    }

    /**
     * Tests that multiple additional properties are all reported.
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

        Validator::validateRequest($json, $this->strictSchemasSpec);
    }

    /**
     * Tests that typo in property name creates both missing required AND additional property errors.
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

        Validator::validateRequest($json, $this->simpleCrudSpec);
    }

    // ========================================
    // Format Violation Tests (10 tests)
    // ========================================

    /**
     * Tests that invalid email format is rejected.
     *
     * Example: "not-an-email" instead of "user@example.com"
     */
    #[Test]
    public function itRejectsFormatEmailInvalid(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/format-violations/format-email-invalid.json');

        $this->expectException(FormatViolationException::class);
        $this->expectExceptionMessage('email');

        Validator::validateRequest($json, $this->simpleCrudSpec);
    }

    /**
     * Tests that invalid UUID format is rejected.
     *
     * Example: "not-a-uuid" instead of "550e8400-e29b-41d4-a716-446655440000"
     */
    #[Test]
    public function itRejectsFormatUuidInvalid(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/format-violations/format-uuid-invalid.json');

        $this->expectException(FormatViolationException::class);
        $this->expectExceptionMessage('uuid');

        Validator::validateRequest($json, $this->strictSchemasSpec);
    }

    /**
     * Tests that invalid date-time format is rejected.
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

        Validator::validateRequest($json, $this->strictSchemasSpec);
    }

    /**
     * Tests that invalid date format is rejected.
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

        Validator::validateRequest($json, $this->edgeCasesSpec);
    }

    /**
     * Tests that invalid URI format is rejected.
     *
     * Example: "not a url" instead of "https://example.com"
     */
    #[Test]
    public function itRejectsFormatUriInvalid(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/format-violations/format-uri-invalid.json');

        $this->expectException(FormatViolationException::class);
        $this->expectExceptionMessage('uri');

        Validator::validateRequest($json, $this->edgeCasesSpec);
    }

    /**
     * Tests that invalid URI reference format is rejected.
     *
     * Example: "://invalid" instead of "/path/to/resource"
     */
    #[Test]
    public function itRejectsFormatUriReferenceInvalid(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/format-violations/format-uri-reference-invalid.json');

        $this->expectException(FormatViolationException::class);
        $this->expectExceptionMessage('uri-reference');

        Validator::validateRequest($json, $this->edgeCasesSpec);
    }

    /**
     * Tests that invalid hostname format is rejected.
     *
     * Example: "invalid_hostname!" instead of "example.com"
     */
    #[Test]
    public function itRejectsFormatHostnameInvalid(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/format-violations/format-hostname-invalid.json');

        $this->expectException(FormatViolationException::class);
        $this->expectExceptionMessage('hostname');

        Validator::validateRequest($json, $this->edgeCasesSpec);
    }

    /**
     * Tests that invalid IPv4 format is rejected.
     *
     * Example: "192.168.1.999" instead of "192.168.1.1"
     */
    #[Test]
    public function itRejectsFormatIpv4Invalid(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/format-violations/format-ipv4-invalid.json');

        $this->expectException(FormatViolationException::class);
        $this->expectExceptionMessage('ipv4');

        Validator::validateRequest($json, $this->edgeCasesSpec);
    }

    /**
     * Tests that invalid IPv6 format is rejected.
     *
     * Example: "::1::2" instead of "2001:db8::1"
     */
    #[Test]
    public function itRejectsFormatIpv6Invalid(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/format-violations/format-ipv6-invalid.json');

        $this->expectException(FormatViolationException::class);
        $this->expectExceptionMessage('ipv6');

        Validator::validateRequest($json, $this->edgeCasesSpec);
    }

    /**
     * Tests that invalid phone format is rejected.
     *
     * Example: "123-456-7890" instead of "+12125551234" (E.164 format)
     */
    #[Test]
    public function itRejectsFormatPhoneInvalid(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/format-violations/format-phone-invalid.json');

        $this->expectException(FormatViolationException::class);
        $this->expectExceptionMessage('phone');

        Validator::validateRequest($json, $this->edgeCasesSpec);
    }

    // ========================================
    // Enum Violation Tests (5 tests)
    // ========================================

    /**
     * Tests that value not in enum is rejected.
     *
     * Example: "pending" when enum is ["active", "inactive", "discontinued"]
     */
    #[Test]
    public function itRejectsEnumInvalidValue(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/enum-violations/enum-invalid-value.json');

        $this->expectException(EnumViolationException::class);
        $this->expectExceptionMessage('pending');

        Validator::validateRequest($json, $this->strictSchemasSpec);
    }

    /**
     * Tests that enum validation is case-sensitive.
     *
     * Example: "Active" instead of "active"
     */
    #[Test]
    public function itRejectsEnumCaseMismatch(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/enum-violations/enum-case-mismatch.json');

        $this->expectException(EnumViolationException::class);
        $this->expectExceptionMessage('Active');

        Validator::validateRequest($json, $this->strictSchemasSpec);
    }

    /**
     * Tests that enum type must match.
     *
     * Example: 1 instead of "active" (number instead of string)
     */
    #[Test]
    public function itRejectsEnumTypeMismatch(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/enum-violations/enum-type-mismatch.json');

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('integer');

        Validator::validateRequest($json, $this->strictSchemasSpec);
    }

    /**
     * Tests that null is not automatically valid for enums.
     *
     * Example: null when enum is ["active", "inactive", "discontinued"]
     */
    #[Test]
    public function itRejectsEnumNullValue(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/enum-violations/enum-null-value.json');

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('null');

        Validator::validateRequest($json, $this->strictSchemasSpec);
    }

    /**
     * Tests that empty string is not automatically valid for string enums.
     *
     * Example: "" when enum is ["active", "inactive", "discontinued"]
     */
    #[Test]
    public function itRejectsEnumEmptyString(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/enum-violations/enum-empty-string.json');

        $this->expectException(EnumViolationException::class);
        $this->expectExceptionMessage('empty');

        Validator::validateRequest($json, $this->strictSchemasSpec);
    }

    // ========================================
    // Boundary Violation Tests (10 tests)
    // ========================================

    /**
     * Tests that value below minimum is rejected.
     *
     * Example: -10.50 when minimum is 0
     */
    #[Test]
    public function itRejectsBoundaryMinimumViolated(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/boundary-violations/boundary-minimum-violated.json');

        $this->expectException(BoundaryViolationException::class);
        $this->expectExceptionMessage('minimum');

        Validator::validateRequest($json, $this->strictSchemasSpec);
    }

    /**
     * Tests that value above maximum is rejected.
     *
     * Example: 200 when maximum is 150
     */
    #[Test]
    public function itRejectsBoundaryMaximumViolated(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/boundary-violations/boundary-maximum-violated.json');

        $this->expectException(BoundaryViolationException::class);
        $this->expectExceptionMessage('maximum');

        Validator::validateRequest($json, $this->edgeCasesSpec);
    }

    /**
     * Tests that value equal to exclusive minimum is rejected.
     *
     * Example: 0 when exclusiveMinimum is 0 (must be > 0, not >= 0)
     */
    #[Test]
    public function itRejectsBoundaryExclusiveMinimum(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/boundary-violations/boundary-exclusive-minimum.json');

        $this->expectException(BoundaryViolationException::class);
        $this->expectExceptionMessage('exclusiveMinimum');

        Validator::validateRequest($json, $this->edgeCasesSpec);
    }

    /**
     * Tests that value equal to exclusive maximum is rejected.
     *
     * Example: 100 when exclusiveMaximum is 100 (must be < 100, not <= 100)
     */
    #[Test]
    public function itRejectsBoundaryExclusiveMaximum(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/boundary-violations/boundary-exclusive-maximum.json');

        $this->expectException(BoundaryViolationException::class);
        $this->expectExceptionMessage('exclusiveMaximum');

        Validator::validateRequest($json, $this->edgeCasesSpec);
    }

    /**
     * Tests that string shorter than minLength is rejected.
     *
     * Example: "" when minLength is 1
     */
    #[Test]
    public function itRejectsBoundaryMinLengthViolated(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/boundary-violations/boundary-min-length-violated.json');

        $this->expectException(BoundaryViolationException::class);
        $this->expectExceptionMessage('minLength');

        Validator::validateRequest($json, $this->strictSchemasSpec);
    }

    /**
     * Tests that string longer than maxLength is rejected.
     *
     * Example: 280 character string when maxLength is 200
     */
    #[Test]
    public function itRejectsBoundaryMaxLengthViolated(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/boundary-violations/boundary-max-length-violated.json');

        $this->expectException(BoundaryViolationException::class);
        $this->expectExceptionMessage('maxLength');

        Validator::validateRequest($json, $this->strictSchemasSpec);
    }

    /**
     * Tests that array with fewer than minItems is rejected.
     *
     * Example: [] when minItems is 1
     */
    #[Test]
    public function itRejectsBoundaryMinItemsViolated(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/boundary-violations/boundary-min-items-violated.json');

        $this->expectException(BoundaryViolationException::class);
        $this->expectExceptionMessage('minItems');

        Validator::validateRequest($json, $this->strictSchemasSpec);
    }

    /**
     * Tests that array with more than maxItems is rejected.
     *
     * Example: 6 items when maxItems is 5
     */
    #[Test]
    public function itRejectsBoundaryMaxItemsViolated(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/boundary-violations/boundary-max-items-violated.json');

        $this->expectException(BoundaryViolationException::class);
        $this->expectExceptionMessage('maxItems');

        Validator::validateRequest($json, $this->strictSchemasSpec);
    }

    /**
     * Tests that value not a multiple of specified value is rejected.
     *
     * Example: 7 when multipleOf is 5
     */
    #[Test]
    public function itRejectsBoundaryMultipleOfViolated(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/boundary-violations/boundary-multiple-of-violated.json');

        $this->expectException(BoundaryViolationException::class);
        $this->expectExceptionMessage('multipleOf');

        Validator::validateRequest($json, $this->edgeCasesSpec);
    }

    /**
     * Tests that array with duplicate items is rejected when uniqueItems is true.
     *
     * Example: ["electronics", "widget", "electronics"] when uniqueItems: true
     */
    #[Test]
    public function itRejectsBoundaryUniqueItemsViolated(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/boundary-violations/boundary-unique-items-violated.json');

        $this->expectException(BoundaryViolationException::class);
        $this->expectExceptionMessage('uniqueItems');

        Validator::validateRequest($json, $this->strictSchemasSpec);
    }

    // ========================================
    // Pattern Violation Tests (5 tests)
    // ========================================

    /**
     * Tests that string not matching pattern is rejected.
     *
     * Example: "abc" when pattern is "^[A-Z]{3}$" (requires uppercase)
     */
    #[Test]
    public function itRejectsPatternNoMatch(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/pattern-violations/pattern-no-match.json');

        $this->expectException(PatternViolationException::class);
        $this->expectExceptionMessage('pattern');

        Validator::validateRequest($json, $this->edgeCasesSpec);
    }

    /**
     * Tests that invalid phone pattern is rejected.
     *
     * Example: "555-1234" when pattern requires E.164 format (^\+[1-9]\d{1,14}$)
     */
    #[Test]
    public function itRejectsPatternPhoneInvalid(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/pattern-violations/pattern-phone-invalid.json');

        $this->expectException(PatternViolationException::class);
        $this->expectExceptionMessage('phone');

        Validator::validateRequest($json, $this->edgeCasesSpec);
    }

    /**
     * Tests that invalid hex color pattern is rejected.
     *
     * Example: "#GGGGGG" when pattern is "^#[0-9A-Fa-f]{6}$"
     */
    #[Test]
    public function itRejectsPatternHexColorInvalid(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/pattern-violations/pattern-hex-color-invalid.json');

        $this->expectException(PatternViolationException::class);
        $this->expectExceptionMessage('hex');

        Validator::validateRequest($json, $this->edgeCasesSpec);
    }

    /**
     * Tests that alphanumeric pattern violation is rejected.
     *
     * Example: "ABC_123" when pattern is "^[a-zA-Z0-9]+$" (underscore not allowed)
     */
    #[Test]
    public function itRejectsPatternAlphanumericViolated(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/pattern-violations/pattern-alphanumeric-violated.json');

        $this->expectException(PatternViolationException::class);
        $this->expectExceptionMessage('alphanumeric');

        Validator::validateRequest($json, $this->edgeCasesSpec);
    }

    /**
     * Tests that custom email pattern is stricter than format.
     *
     * Example: "user@domain" when pattern requires TLD
     */
    #[Test]
    public function itRejectsPatternEmailCustomInvalid(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/pattern-violations/pattern-email-custom-invalid.json');

        $this->expectException(PatternViolationException::class);
        $this->expectExceptionMessage('email');

        Validator::validateRequest($json, $this->edgeCasesSpec);
    }

    // ========================================
    // Composition Violation Tests (8 tests)
    // ========================================

    /**
     * Tests that oneOf fails when matching none of the schemas.
     *
     * Example: boolean when oneOf expects string or number
     */
    #[Test]
    public function itRejectsOneofMatchesNone(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/composition-violations/oneof-matches-none.json');

        $this->expectException(CompositionViolationException::class);
        $this->expectExceptionMessage('oneOf');

        Validator::validateRequest($json, $this->compositionSpec);
    }

    /**
     * Tests that oneOf fails when matching multiple schemas.
     *
     * Example: Data matches 2 schemas when exactly 1 is required
     */
    #[Test]
    public function itRejectsOneofMatchesMultiple(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/composition-violations/oneof-matches-multiple.json');

        $this->expectException(CompositionViolationException::class);
        $this->expectExceptionMessage('multiple');

        Validator::validateRequest($json, $this->compositionSpec);
    }

    /**
     * Tests that anyOf fails when matching none of the schemas.
     *
     * Example: boolean when anyOf expects string or number
     */
    #[Test]
    public function itRejectsAnyofMatchesNone(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/composition-violations/anyof-matches-none.json');

        $this->expectException(CompositionViolationException::class);
        $this->expectExceptionMessage('anyOf');

        Validator::validateRequest($json, $this->compositionSpec);
    }

    /**
     * Tests that allOf fails when one schema fails.
     *
     * Example: "hi" fails minLength even though it's a string
     */
    #[Test]
    public function itRejectsAllofFailsOne(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/composition-violations/allof-fails-one.json');

        $this->expectException(CompositionViolationException::class);
        $this->expectExceptionMessage('allOf');

        Validator::validateRequest($json, $this->compositionSpec);
    }

    /**
     * Tests that allOf fails when multiple schemas fail.
     *
     * Example: number when all schemas expect string with constraints
     */
    #[Test]
    public function itRejectsAllofFailsMultiple(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/composition-violations/allof-fails-multiple.json');

        $this->expectException(CompositionViolationException::class);
        $this->expectExceptionMessage('allOf');

        Validator::validateRequest($json, $this->compositionSpec);
    }

    /**
     * Tests that oneOf without discriminator can be ambiguous.
     *
     * Example: Data could match multiple schemas due to additionalProperties
     */
    #[Test]
    public function itRejectsOneofWithoutDiscriminatorAmbiguous(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/composition-violations/oneof-without-discriminator-ambiguous.json');

        $this->expectException(CompositionViolationException::class);
        $this->expectExceptionMessage('ambiguous');

        Validator::validateRequest($json, $this->compositionSpec);
    }

    /**
     * Tests that nested composition violations are reported.
     *
     * Example: Enum error within oneOf branch
     */
    #[Test]
    public function itRejectsNestedCompositionViolation(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/composition-violations/nested-composition-violation.json');

        $this->expectException(CompositionViolationException::class);
        $this->expectExceptionMessage('nested');

        Validator::validateRequest($json, $this->compositionSpec);
    }

    /**
     * Tests that additional properties in oneOf branch are rejected.
     *
     * Example: Extra field in oneOf schema with additionalProperties: false
     */
    #[Test]
    public function itRejectsCompositionWithAdditionalProps(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/composition-violations/composition-with-additional-props.json');

        $this->expectException(CompositionViolationException::class);
        $this->expectExceptionMessage('additional');

        Validator::validateRequest($json, $this->compositionSpec);
    }

    // ========================================
    // Discriminator Violation Tests (3 tests)
    // ========================================

    /**
     * Tests that missing discriminator field is rejected.
     *
     * Example: Object missing "petType" field when discriminator requires it
     */
    #[Test]
    public function itRejectsDiscriminatorMissing(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/discriminator-violations/discriminator-missing.json');

        $this->expectException(DiscriminatorViolationException::class);
        $this->expectExceptionMessage('missing');

        Validator::validateRequest($json, $this->compositionSpec);
    }

    /**
     * Tests that invalid discriminator value is rejected.
     *
     * Example: "bird" when only "dog" and "cat" are mapped
     */
    #[Test]
    public function itRejectsDiscriminatorInvalidValue(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/discriminator-violations/discriminator-invalid-value.json');

        $this->expectException(DiscriminatorViolationException::class);
        $this->expectExceptionMessage('bird');

        Validator::validateRequest($json, $this->compositionSpec);
    }

    /**
     * Tests that discriminator value with wrong case is rejected.
     *
     * Example: "Dog" instead of "dog" (case-sensitive)
     */
    #[Test]
    public function itRejectsDiscriminatorUnmappedValue(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/discriminator-violations/discriminator-unmapped-value.json');

        $this->expectException(DiscriminatorViolationException::class);
        $this->expectExceptionMessage('case');

        Validator::validateRequest($json, $this->compositionSpec);
    }

    // ========================================
    // Multiple Errors Tests (3 tests)
    // ========================================

    /**
     * Tests that 5 different violations are all reported.
     *
     * Violations: type, required, additional, enum, format
     */
    #[Test]
    public function itRejectsMultipleErrors5(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/multiple-errors/multiple-errors-5.json');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('5');

        Validator::validateRequest($json, $this->strictSchemasSpec);
    }

    /**
     * Tests that 10+ violations are all reported.
     *
     * Comprehensive test with all violation types
     */
    #[Test]
    public function itRejectsMultipleErrors10(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/multiple-errors/multiple-errors-10.json');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('10');

        Validator::validateRequest($json, $this->strictSchemasSpec);
    }

    /**
     * Tests that cascading errors within composition are reported.
     *
     * Example: Errors within oneOf branches plus composition error
     */
    #[Test]
    public function itRejectsMultipleErrorsCascading(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/multiple-errors/multiple-errors-cascading.json');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('cascading');

        Validator::validateRequest($json, $this->compositionSpec);
    }
}
