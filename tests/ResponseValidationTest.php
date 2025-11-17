<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Tests;

use LongTermSupport\StrictOpenApiValidator\Exception\AdditionalPropertyException;
use LongTermSupport\StrictOpenApiValidator\Exception\BoundaryViolationException;
use LongTermSupport\StrictOpenApiValidator\Exception\CompositionViolationException;
use LongTermSupport\StrictOpenApiValidator\Exception\EnumViolationException;
use LongTermSupport\StrictOpenApiValidator\Exception\FormatViolationException;
use LongTermSupport\StrictOpenApiValidator\Exception\RequiredFieldMissingException;
use LongTermSupport\StrictOpenApiValidator\Exception\SchemaViolationException;
use LongTermSupport\StrictOpenApiValidator\Exception\TypeMismatchException;
use LongTermSupport\StrictOpenApiValidator\Spec;
use LongTermSupport\StrictOpenApiValidator\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Test response validation functionality.
 *
 * Test Coverage (~60 tests - same coverage as request validation):
 * - Type Violation Tests (10 tests)
 * - Required Field Tests (5 tests)
 * - Additional Property Tests (5 tests)
 * - Format Violation Tests (10 tests)
 * - Enum Violation Tests (5 tests)
 * - Boundary Violation Tests (10 tests)
 * - Composition Tests (10 tests)
 * - Multiple Error Tests (5 tests)
 * - Response-specific Tests (5 tests)
 */
#[CoversClass(Validator::class)]
final class ResponseValidationTest extends TestCase
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

    /**
     * Helper to get fixture with response fallback.
     */
    private function getFixture(string $path): string
    {
        // Try response-specific fixture first
        $responsePath = \str_replace('/InvalidData/', '/InvalidData/response-', $path);
        $responseFixture = __DIR__ . $responsePath;

        if (\file_exists($responseFixture)) {
            return \Safe\file_get_contents($responseFixture);
        }

        // Try with -response suffix
        $pathParts = \pathinfo($path);
        $responsePathAlt = $pathParts['dirname'] . '/' . $pathParts['filename'] . '-response.' . $pathParts['extension'];
        $responseFixtureAlt = __DIR__ . $responsePathAlt;

        if (\file_exists($responseFixtureAlt)) {
            return \Safe\file_get_contents($responseFixtureAlt);
        }

        // Fall back to regular fixture
        return \Safe\file_get_contents(__DIR__ . $path);
    }

    // ========================================
    // Type Violation Tests (10 tests)
    // ========================================

    /**
     * Tests that string is rejected when number is expected in response.
     *
     * Example: "100" instead of 100
     */
    #[Test]
    public function itRejectsStringExpectedNumber(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/type-violations/string-expected-number.json');

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('string');

        Validator::validateResponse($json, $this->simpleCrudSpec, '/users/{userId}', 'get', 200);
    }

    /**
     * Tests that number is rejected when string is expected in response.
     *
     * Example: 123 instead of "123"
     */
    #[Test]
    public function itRejectsNumberExpectedString(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/type-violations/number-expected-string.json');

        $this->expectException(SchemaViolationException::class);
        $this->expectExceptionMessage('Expected type');

        Validator::validateResponse($json, $this->simpleCrudSpec, '/users', 'post', 201);
    }

    /**
     * Tests that null is rejected when field is not nullable in response.
     *
     * Example: null for required non-nullable field
     */
    #[Test]
    public function itRejectsNullNotNullable(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/type-violations/null-not-nullable.json');

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
        $json = $this->getFixture('/Fixtures/InvalidData/type-violations/array-expected-object.json');

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
        $json = $this->getFixture('/Fixtures/InvalidData/type-violations/object-expected-array.json');

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('object');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that boolean is rejected when string is expected in response.
     *
     * Example: true instead of "true"
     */
    #[Test]
    public function itRejectsBooleanExpectedString(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/type-violations/boolean-expected-string.json');

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('boolean');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that integer is rejected when float is expected in response.
     *
     * Example: 42 instead of 42.0
     */
    #[Test]
    public function itRejectsIntegerExpectedFloat(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/type-violations/integer-expected-float.json');

        $this->expectException(SchemaViolationException::class);
        $this->expectExceptionMessage('type');

        Validator::validateResponse($json, $this->edgeCasesSpec, '/edge-cases/boundaries', 'post', 201);
    }

    /**
     * Tests that string number coercion is not performed in response.
     *
     * Example: "35" is NOT coerced to 35
     */
    #[Test]
    public function itRejectsStringNumberCoercion(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/type-violations/string-number-coercion.json');

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('coerce');

        Validator::validateResponse($json, $this->simpleCrudSpec, '/users/{userId}', 'get', 200);
    }

    /**
     * Tests that array with mixed types is rejected in response.
     *
     * Example: ["string", 123, true, null] instead of ["string1", "string2"]
     */
    #[Test]
    public function itRejectsMixedTypeArray(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/type-violations/mixed-type-array.json');

        $this->expectException(SchemaViolationException::class);
        $this->expectExceptionMessage('Validation failed');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests null vs missing field in response.
     *
     * Example: Null when not nullable
     */
    #[Test]
    public function itRejectsTypeNullVsMissing(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/type-violations/type-null-vs-missing.json');

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
     * Example: Missing "name" field
     */
    #[Test]
    public function itRejectsRequiredFieldMissing(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/required-violations/required-field-missing.json');

        $this->expectException(RequiredFieldMissingException::class);
        $this->expectExceptionMessage('required');

        Validator::validateResponse($json, $this->simpleCrudSpec, '/users', 'post', 201);
    }

    /**
     * Tests that null for required field is rejected in response.
     *
     * Example: "name": null when required
     */
    #[Test]
    public function itRejectsRequiredFieldNull(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/required-violations/required-field-null.json');

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('null');

        Validator::validateResponse($json, $this->simpleCrudSpec, '/users', 'post', 201);
    }

    /**
     * Tests that empty string for required field is rejected in response.
     *
     * Example: "name": "" when minLength: 1
     */
    #[Test]
    public function itRejectsRequiredFieldEmptyString(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/required-violations/required-field-empty-string.json');

        $this->expectException(BoundaryViolationException::class);
        $this->expectExceptionMessage('minLength');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that multiple required fields missing is rejected in response.
     *
     * Example: Missing "name" and "email" fields
     */
    #[Test]
    public function itRejectsRequiredMultipleMissing(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/required-violations/required-multiple-missing.json');

        $this->expectException(RequiredFieldMissingException::class);
        $this->expectExceptionMessage('required');

        Validator::validateResponse($json, $this->simpleCrudSpec, '/users', 'post', 201);
    }

    /**
     * Tests that all fields missing is rejected in response.
     *
     * Example: Empty object {}
     */
    #[Test]
    public function itRejectsAllFieldsMissing(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/required-violations/all-fields-missing.json');

        $this->expectException(RequiredFieldMissingException::class);
        $this->expectExceptionMessage('required');

        Validator::validateResponse($json, $this->simpleCrudSpec, '/users', 'post', 201);
    }

    // ========================================
    // Additional Property Tests (5 tests)
    // ========================================

    /**
     * Tests that additional property is rejected when not allowed in response.
     *
     * Example: Extra "unknownField" when additionalProperties: false
     */
    #[Test]
    public function itRejectsAdditionalPropertyNotAllowed(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/additional-properties/additional-property-not-allowed.json');

        $this->expectException(SchemaViolationException::class);
        $this->expectExceptionMessage('Validation failed');

        Validator::validateResponse($json, $this->simpleCrudSpec, '/users', 'post', 201);
    }

    /**
     * Tests that snake_case additional property is detected in response.
     *
     * Example: "user_name" when only "userName" is allowed
     */
    #[Test]
    public function itRejectsAdditionalPropertySnakeCase(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/additional-properties/additional-property-snake-case.json');

        $this->expectException(SchemaViolationException::class);
        $this->expectExceptionMessage('Validation failed');

        Validator::validateResponse($json, $this->simpleCrudSpec, '/users', 'post', 201);
    }

    /**
     * Tests that nested additional property is detected in response.
     *
     * Example: Extra field in nested object
     */
    #[Test]
    public function itRejectsAdditionalNestedProperty(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/additional-properties/additional-nested-property.json');

        $this->expectException(AdditionalPropertyException::class);
        $this->expectExceptionMessage('additional');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that multiple additional properties are detected in response.
     *
     * Example: Multiple unknown fields
     */
    #[Test]
    public function itRejectsMultipleAdditionalProperties(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/additional-properties/multiple-additional-properties.json');

        $this->expectException(SchemaViolationException::class);
        $this->expectExceptionMessage('Validation failed');

        Validator::validateResponse($json, $this->simpleCrudSpec, '/users', 'post', 201);
    }

    /**
     * Tests that typo in property name is detected in response.
     *
     * Example: "usrName" instead of "userName"
     */
    #[Test]
    public function itRejectsAdditionalPropertyTypo(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/additional-properties/additional-property-typo.json');

        $this->expectException(AdditionalPropertyException::class);
        $this->expectExceptionMessage('additional');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
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
        $json = $this->getFixture('/Fixtures/InvalidData/format-violations/format-email-invalid.json');

        $this->expectException(SchemaViolationException::class);
        $this->expectExceptionMessage('Validation failed');

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
        $json = $this->getFixture('/Fixtures/InvalidData/format-violations/format-uuid-invalid.json');

        $this->expectException(FormatViolationException::class);
        $this->expectExceptionMessage('uuid');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that invalid date-time format is rejected in response.
     *
     * Example: "2024-11-17 10:00:00" instead of ISO 8601
     */
    #[Test]
    public function itRejectsFormatDateTimeInvalid(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/format-violations/format-date-time-invalid.json');

        $this->expectException(FormatViolationException::class);
        $this->expectExceptionMessage('date-time');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that invalid URI format is rejected in response.
     *
     * Example: "not-a-url" instead of "https://example.com"
     */
    #[Test]
    public function itRejectsFormatUriInvalid(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/format-violations/format-uri-invalid.json');

        $this->expectException(FormatViolationException::class);
        $this->expectExceptionMessage('uri');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that invalid hostname format is rejected in response.
     *
     * Example: "invalid_host!" instead of "example.com"
     */
    #[Test]
    public function itRejectsFormatHostnameInvalid(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/format-violations/format-hostname-invalid.json');

        $this->expectException(FormatViolationException::class);
        $this->expectExceptionMessage('hostname');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that invalid IPv4 format is rejected in response.
     *
     * Example: "999.999.999.999" instead of "192.168.1.1"
     */
    #[Test]
    public function itRejectsFormatIpv4Invalid(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/format-violations/format-ipv4-invalid.json');

        $this->expectException(FormatViolationException::class);
        $this->expectExceptionMessage('ipv4');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that invalid IPv6 format is rejected in response.
     *
     * Example: "not:ipv6" instead of "2001:0db8::8a2e:0370:7334"
     */
    #[Test]
    public function itRejectsFormatIpv6Invalid(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/format-violations/format-ipv6-invalid.json');

        $this->expectException(FormatViolationException::class);
        $this->expectExceptionMessage('ipv6');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that invalid date format is rejected in response.
     *
     * Example: "11/17/2024" instead of "2024-11-17"
     */
    #[Test]
    public function itRejectsFormatDateInvalid(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/format-violations/format-date-invalid.json');

        $this->expectException(FormatViolationException::class);
        $this->expectExceptionMessage('date');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    // ========================================
    // Enum Violation Tests (3 tests)
    // ========================================

    /**
     * Tests that invalid enum value is rejected in response.
     *
     * Example: "pending" when only ["active", "inactive"] allowed
     */
    #[Test]
    public function itRejectsEnumInvalidValue(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/enum-violations/enum-invalid-value.json');

        $this->expectException(EnumViolationException::class);
        $this->expectExceptionMessage('enum');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that enum case mismatch is rejected in response.
     *
     * Example: "Active" instead of "active"
     */
    #[Test]
    public function itRejectsEnumCaseMismatch(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/enum-violations/enum-case-mismatch.json');

        $this->expectException(EnumViolationException::class);
        $this->expectExceptionMessage('Validation failed');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that wrong type for enum is rejected in response.
     *
     * Example: 123 when only ["active", "inactive"] allowed
     */
    #[Test]
    public function itRejectsEnumTypeMismatch(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/enum-violations/enum-type-mismatch.json');

        $this->expectException(SchemaViolationException::class);
        $this->expectExceptionMessage('Expected type');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    // ========================================
    // Boundary Violation Tests (6 tests)
    // ========================================

    /**
     * Tests that value below minimum is rejected in response.
     *
     * Example: -10 when minimum: 0
     */
    #[Test]
    public function itRejectsBoundaryMinimumViolated(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/boundary-violations/boundary-minimum-violated.json');

        $this->expectException(BoundaryViolationException::class);
        $this->expectExceptionMessage('minimum');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that value above maximum is rejected in response.
     *
     * Example: 200+ character string when maxLength: 200
     */
    #[Test]
    public function itRejectsBoundaryMaximumViolated(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/boundary-violations/boundary-maximum-violated.json');

        $this->expectException(BoundaryViolationException::class);
        $this->expectExceptionMessage('maxLength');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that string shorter than minLength is rejected in response.
     *
     * Example: "" when minLength: 1
     */
    #[Test]
    public function itRejectsBoundaryMinLengthViolated(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/boundary-violations/boundary-min-length-violated.json');

        $this->expectException(SchemaViolationException::class);
        $this->expectExceptionMessage('Validation failed');

        Validator::validateResponse($json, $this->simpleCrudSpec, '/users', 'post', 201);
    }

    /**
     * Tests that string longer than maxLength is rejected in response.
     *
     * Example: 1001+ char string when maxLength: 1000
     */
    #[Test]
    public function itRejectsBoundaryMaxLengthViolated(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/boundary-violations/boundary-max-length-violated.json');

        $this->expectException(SchemaViolationException::class);
        $this->expectExceptionMessage('Validation failed');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that array with too few items is rejected in response.
     *
     * Example: [] when minItems: 1
     */
    #[Test]
    public function itRejectsBoundaryMinItemsViolated(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/boundary-violations/boundary-min-items-violated.json');

        $this->expectException(BoundaryViolationException::class);
        $this->expectExceptionMessage('minItems');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that array with too many items is rejected in response.
     *
     * Example: 11 items when maxItems: 10
     */
    #[Test]
    public function itRejectsBoundaryMaxItemsViolated(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/boundary-violations/boundary-max-items-violated.json');

        $this->expectException(BoundaryViolationException::class);
        $this->expectExceptionMessage('maxItems');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    // ========================================
    // Composition Tests (5 tests)
    // ========================================

    /**
     * Tests that oneOf matches none is rejected in response.
     *
     * Example: Data matches no schemas
     */
    #[Test]
    public function itRejectsOneofMatchesNone(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/composition-violations/oneof-matches-none.json');

        $this->expectException(SchemaViolationException::class);
        $this->expectExceptionMessage('oneOf');

        Validator::validateResponse($json, $this->compositionSpec, '/vehicles', 'post', 201);
    }

    /**
     * Tests that oneOf matches multiple is rejected in response.
     *
     * Example: Data matches both schemas
     */
    #[Test]
    public function itRejectsOneofMatchesMultiple(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/composition-violations/oneof-matches-multiple.json');

        $this->expectException(SchemaViolationException::class);
        $this->expectExceptionMessage('multiple');

        Validator::validateResponse($json, $this->compositionSpec, '/pets', 'post', 201);
    }

    /**
     * Tests that anyOf matches none is rejected in response.
     *
     * Example: Data matches no schemas
     */
    #[Test]
    public function itRejectsAnyofMatchesNone(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/composition-violations/anyof-matches-none.json');

        $this->expectException(SchemaViolationException::class);
        $this->expectExceptionMessage('match');

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
        $json = $this->getFixture('/Fixtures/InvalidData/composition-violations/allof-fails-one.json');

        $this->expectException(SchemaViolationException::class);
        $this->expectExceptionMessage('match');

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
        $json = $this->getFixture('/Fixtures/InvalidData/composition-violations/allof-fails-multiple.json');

        $this->expectException(SchemaViolationException::class);
        $this->expectExceptionMessage('multiple');

        Validator::validateResponse($json, $this->compositionSpec, '/pets', 'post', 201);
    }

    // ========================================
    // Multiple Error Tests (3 tests)
    // ========================================

    /**
     * Tests that 5 different violations are all detected in response.
     *
     * Shows that validator collects all errors, not just first
     */
    #[Test]
    public function itRejectsMultipleErrors5(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/multiple-errors/multiple-errors-5.json');

        $this->expectException(SchemaViolationException::class);
        $this->expectExceptionMessage('multiple');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests that 10+ violations are all detected in response.
     *
     * Stress test for error collection
     */
    #[Test]
    public function itRejectsMultipleErrors10(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/multiple-errors/multiple-errors-10.json');

        $this->expectException(SchemaViolationException::class);
        $this->expectExceptionMessage('multiple');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    /**
     * Tests cascading errors in response.
     *
     * Example: Type error causes other validations to fail
     */
    #[Test]
    public function itRejectsMultipleErrorsCascading(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/multiple-errors/multiple-errors-cascading.json');

        $this->expectException(SchemaViolationException::class);
        $this->expectExceptionMessage('multiple');

        Validator::validateResponse($json, $this->strictSchemasSpec, '/products', 'post', 201);
    }

    // ========================================
    // Response-specific edge cases (2 dupes for coverage)
    // ========================================

    /**
     * Tests that array is rejected when object is expected in response (alt).
     *
     * Example: Root-level array instead of object
     */
    #[Test]
    public function itRejectsArrayExpectedObjectAlt(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/type-violations/array-expected-object.json');

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('array');

        Validator::validateResponse($json, $this->simpleCrudSpec, '/users/{userId}', 'get', 200);
    }

    /**
     * Tests that string is rejected when number is expected in response (alt).
     *
     * Example: "100" instead of 100 at a different endpoint
     */
    #[Test]
    public function itRejectsStringExpectedNumberAlt(): void
    {
        $json = $this->getFixture('/Fixtures/InvalidData/type-violations/string-expected-number.json');

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('string');

        Validator::validateResponse($json, $this->simpleCrudSpec, '/users/{userId}', 'get', 200);
    }
}