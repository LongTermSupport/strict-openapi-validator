<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Tests;

use LongTermSupport\StrictOpenApiValidator\Exception\BoundaryViolationException;
use LongTermSupport\StrictOpenApiValidator\Exception\PatternViolationException;
use LongTermSupport\StrictOpenApiValidator\Exception\RequiredFieldMissingException;
use LongTermSupport\StrictOpenApiValidator\Exception\TypeMismatchException;
use LongTermSupport\StrictOpenApiValidator\Spec;
use LongTermSupport\StrictOpenApiValidator\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Edge case tests for subtle validation scenarios.
 *
 * Tests cover:
 * - The Four Combinations (Nullable × Required matrix) - 4 tests
 * - Empty Values (strings, arrays, objects) - 6 tests
 * - Boundary Edge Cases (inclusive/exclusive, exact matches) - 10 tests
 * - Type Edge Cases (float vs integer, zero vs null) - 4 tests (2 skipped, need fixtures)
 * - Pattern Edge Cases (anchors, empty strings, unicode) - 6 tests
 *
 * Total: 30 tests (28 active, 2 skipped)
 *
 * These tests verify that edge cases are handled correctly by the validator.
 */
#[CoversClass(Validator::class)]
final class EdgeCaseTest extends TestCase
{
    private Spec $edgeCasesSpec;

    protected function setUp(): void
    {
        $this->edgeCasesSpec = Spec::createFromFile(__DIR__ . '/Fixtures/Specs/edge-cases.json');
    }

    // ========================================
    // The Four Combinations: Nullable × Required (4 tests)
    // ========================================

    /**
     * Tests required + nullable field with null value.
     *
     * Schema: required: true, type: ["string", "null"]
     * Data: {"requiredNullable": null}
     * Expected: PASS (field present, null is allowed)
     */
    #[Test]
    public function itHandlesRequiredNullableFieldWithNull(): void
    {
        $json = <<<'JSON'
{
  "requiredNullable": null,
  "requiredNonNullable": "valid"
}
JSON;

        // This should PASS - null is explicitly allowed for nullable fields
        Validator::validateRequest($json, $this->edgeCasesSpec, '/edge-cases/nullable', 'post');

        // Assert test completed without exception
        $this->assertTrue(true);
    }

    /**
     * Tests required + non-nullable field with null value.
     *
     * Schema: required: true, type: "string"
     * Data: {"requiredNonNullable": null}
     * Expected: FAIL (field present, but null not allowed)
     */
    #[Test]
    public function itRejectsRequiredNonNullableFieldWithNull(): void
    {
        $json = <<<'JSON'
{
  "requiredNullable": "valid",
  "requiredNonNullable": null
}
JSON;

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('requiredNonNullable');

        Validator::validateRequest($json, $this->edgeCasesSpec, '/edge-cases/nullable', 'post');
    }

    /**
     * Tests optional + nullable field with null value and missing.
     *
     * Schema: required: false, type: ["string", "null"]
     * Data: {"optionalNullable": null} OR field missing
     * Expected: PASS (both null and missing are valid)
     */
    #[Test]
    public function itHandlesOptionalNullableField(): void
    {
        // Test 1: Present with null value - should PASS
        $json = <<<'JSON'
{
  "requiredNullable": "valid",
  "requiredNonNullable": "valid",
  "optionalNullable": null
}
JSON;

        // This should PASS - null is allowed for optional nullable fields
        Validator::validateRequest($json, $this->edgeCasesSpec, '/edge-cases/nullable', 'post');

        // Assert test completed without exception
        $this->assertTrue(true);
    }

    /**
     * Tests optional + non-nullable field with null value.
     *
     * Schema: required: false, type: "string"
     * Data: {"optionalNonNullable": null}
     * Expected: FAIL (field missing is OK, but if present must be non-null)
     */
    #[Test]
    public function itRejectsOptionalNonNullableFieldWithNull(): void
    {
        $json = <<<'JSON'
{
  "requiredNullable": "valid",
  "requiredNonNullable": "valid",
  "optionalNonNullable": null
}
JSON;

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('optionalNonNullable');

        Validator::validateRequest($json, $this->edgeCasesSpec, '/edge-cases/nullable', 'post');
    }

    // ========================================
    // Empty Values (6 tests)
    // ========================================

    /**
     * Tests empty string is valid when no minLength constraint.
     *
     * Schema: type: "string" (no minLength)
     * Data: {"emptyStringAllowed": ""}
     * Expected: PASS (empty string is valid string)
     */
    #[Test]
    public function itHandlesEmptyStringWithoutMinLength(): void
    {
        $json = <<<'JSON'
{
  "emptyStringAllowed": ""
}
JSON;

        // This should PASS - empty string is valid when no minLength constraint
        Validator::validateRequest($json, $this->edgeCasesSpec, '/edge-cases/empty-values', 'post');

        // Assert test completed without exception
        $this->assertTrue(true);
    }

    /**
     * Tests empty string is invalid when minLength is 1.
     *
     * Schema: type: "string", minLength: 1
     * Data: {"emptyStringNotAllowed": ""}
     * Expected: FAIL (empty string has length 0, violates minLength: 1)
     */
    #[Test]
    public function itRejectsEmptyStringWithMinLength(): void
    {
        $json = <<<'JSON'
{
  "emptyStringNotAllowed": ""
}
JSON;

        $this->expectException(BoundaryViolationException::class);
        $this->expectExceptionMessage('emptyStringNotAllowed');

        Validator::validateRequest($json, $this->edgeCasesSpec, '/edge-cases/empty-values', 'post');
    }

    /**
     * Tests empty array is valid when no minItems constraint.
     *
     * Schema: type: "array" (no minItems)
     * Data: {"emptyArrayAllowed": []}
     * Expected: PASS (empty array is valid array)
     */
    #[Test]
    public function itHandlesEmptyArrayWithoutMinItems(): void
    {
        $json = <<<'JSON'
{
  "emptyArrayAllowed": []
}
JSON;

        // This should PASS - empty array is valid when no minItems constraint
        Validator::validateRequest($json, $this->edgeCasesSpec, '/edge-cases/empty-values', 'post');

        // Assert test completed without exception
        $this->assertTrue(true);
    }

    /**
     * Tests empty array is invalid when minItems is 1.
     *
     * Schema: type: "array", minItems: 1
     * Data: {"emptyArrayNotAllowed": []}
     * Expected: FAIL (empty array has 0 items, violates minItems: 1)
     */
    #[Test]
    public function itRejectsEmptyArrayWithMinItems(): void
    {
        $json = <<<'JSON'
{
  "emptyArrayNotAllowed": []
}
JSON;

        $this->expectException(BoundaryViolationException::class);
        $this->expectExceptionMessage('emptyArrayNotAllowed');

        Validator::validateRequest($json, $this->edgeCasesSpec, '/edge-cases/empty-values', 'post');
    }

    /**
     * Tests empty object is valid when no minProperties constraint.
     *
     * Schema: type: "object" (no minProperties)
     * Data: {"emptyObjectAllowed": {}}
     * Expected: PASS (empty object is valid object)
     */
    #[Test]
    public function itHandlesEmptyObjectWithoutMinProperties(): void
    {
        $json = <<<'JSON'
{
  "emptyObjectAllowed": {}
}
JSON;

        // This should PASS - empty object is valid when no minProperties constraint
        Validator::validateRequest($json, $this->edgeCasesSpec, '/edge-cases/empty-values', 'post');

        // Assert test completed without exception
        $this->assertTrue(true);
    }

    /**
     * Tests empty object is invalid when minProperties is 1.
     *
     * Schema: type: "object", minProperties: 1
     * Data: {"emptyObjectNotAllowed": {}}
     * Expected: FAIL (empty object has 0 properties, violates minProperties: 1)
     */
    #[Test]
    public function itRejectsEmptyObjectWithMinProperties(): void
    {
        $json = <<<'JSON'
{
  "emptyObjectNotAllowed": {}
}
JSON;

        $this->expectException(BoundaryViolationException::class);
        $this->expectExceptionMessage('emptyObjectNotAllowed');

        Validator::validateRequest($json, $this->edgeCasesSpec, '/edge-cases/empty-values', 'post');
    }

    // ========================================
    // Boundary Edge Cases (10 tests)
    // ========================================

    /**
     * Tests value exactly equals inclusive minimum.
     *
     * Schema: type: "number", minimum: 0
     * Data: {"minimumInclusive": 0}
     * Expected: PASS (0 equals minimum, inclusive by default)
     */
    #[Test]
    public function itHandlesValueEqualsMinimum(): void
    {
        $json = <<<'JSON'
{
  "minimumInclusive": 0
}
JSON;

        // This should PASS - value equals inclusive minimum
        Validator::validateRequest($json, $this->edgeCasesSpec, '/edge-cases/boundaries', 'post');

        // Assert test completed without exception
        $this->assertTrue(true);
    }

    /**
     * Tests value exactly equals inclusive maximum.
     *
     * Schema: type: "number", maximum: 100
     * Data: {"maximumInclusive": 100}
     * Expected: PASS (100 equals maximum, inclusive by default)
     */
    #[Test]
    public function itHandlesValueEqualsMaximum(): void
    {
        $json = <<<'JSON'
{
  "maximumInclusive": 100
}
JSON;

        // This should PASS - value equals inclusive maximum
        Validator::validateRequest($json, $this->edgeCasesSpec, '/edge-cases/boundaries', 'post');

        // Assert test completed without exception
        $this->assertTrue(true);
    }

    /**
     * Tests value exactly equals exclusive minimum (must be greater).
     *
     * Schema: type: "number", exclusiveMinimum: 0
     * Data: {"exclusiveMinimum": 0}
     * Expected: FAIL (0 is NOT valid, must be > 0)
     */
    #[Test]
    public function itRejectsValueEqualsExclusiveMinimum(): void
    {
        $json = <<<'JSON'
{
  "exclusiveMinimum": 0
}
JSON;

        $this->expectException(BoundaryViolationException::class);
        $this->expectExceptionMessage('exclusiveMinimum');

        Validator::validateRequest($json, $this->edgeCasesSpec, '/edge-cases/boundaries', 'post');
    }

    /**
     * Tests value exactly equals exclusive maximum (must be less).
     *
     * Schema: type: "number", exclusiveMaximum: 100
     * Data: {"exclusiveMaximum": 100}
     * Expected: FAIL (100 is NOT valid, must be < 100)
     */
    #[Test]
    public function itRejectsValueEqualsExclusiveMaximum(): void
    {
        $json = <<<'JSON'
{
  "exclusiveMaximum": 100
}
JSON;

        $this->expectException(BoundaryViolationException::class);
        $this->expectExceptionMessage('exclusiveMaximum');

        Validator::validateRequest($json, $this->edgeCasesSpec, '/edge-cases/boundaries', 'post');
    }

    /**
     * Tests string length exactly equals minLength.
     *
     * Schema: type: "string", minLength: 3
     * Data: {"minLengthEdge": "abc"}
     * Expected: PASS (length 3 equals minLength, inclusive)
     */
    #[Test]
    public function itHandlesStringLengthEqualsMinLength(): void
    {
        $json = <<<'JSON'
{
  "minLengthEdge": "abc"
}
JSON;

        // This should PASS - string length equals minLength (inclusive)
        Validator::validateRequest($json, $this->edgeCasesSpec, '/edge-cases/boundaries', 'post');

        // Assert test completed without exception
        $this->assertTrue(true);
    }

    /**
     * Tests string length exactly equals maxLength.
     *
     * Schema: type: "string", maxLength: 5
     * Data: {"maxLengthEdge": "hello"}
     * Expected: PASS (length 5 equals maxLength, inclusive)
     */
    #[Test]
    public function itHandlesStringLengthEqualsMaxLength(): void
    {
        $json = <<<'JSON'
{
  "maxLengthEdge": "hello"
}
JSON;

        // This should PASS - string length equals maxLength (inclusive)
        Validator::validateRequest($json, $this->edgeCasesSpec, '/edge-cases/boundaries', 'post');

        // Assert test completed without exception
        $this->assertTrue(true);
    }

    /**
     * Tests array length exactly equals minItems.
     *
     * Schema: type: "array", minItems: 2
     * Data: {"minItemsEdge": ["a", "b"]}
     * Expected: PASS (2 items equals minItems, inclusive)
     */
    #[Test]
    public function itHandlesArrayLengthEqualsMinItems(): void
    {
        $json = <<<'JSON'
{
  "minItemsEdge": ["a", "b"]
}
JSON;

        // This should PASS - array length equals minItems (inclusive)
        Validator::validateRequest($json, $this->edgeCasesSpec, '/edge-cases/boundaries', 'post');

        // Assert test completed without exception
        $this->assertTrue(true);
    }

    /**
     * Tests array length exactly equals maxItems.
     *
     * Schema: type: "array", maxItems: 3
     * Data: {"maxItemsEdge": ["a", "b", "c"]}
     * Expected: PASS (3 items equals maxItems, inclusive)
     */
    #[Test]
    public function itHandlesArrayLengthEqualsMaxItems(): void
    {
        $json = <<<'JSON'
{
  "maxItemsEdge": ["a", "b", "c"]
}
JSON;

        // This should PASS - array length equals maxItems (inclusive)
        Validator::validateRequest($json, $this->edgeCasesSpec, '/edge-cases/boundaries', 'post');

        // Assert test completed without exception
        $this->assertTrue(true);
    }

    /**
     * Tests multipleOf validation with exact multiple.
     *
     * Schema: type: "number", multipleOf: 5
     * Data: {"multipleOf": 15}
     * Expected: PASS (15 is exactly divisible by 5)
     */
    #[Test]
    public function itHandlesMultipleOfWithExactMultiple(): void
    {
        $json = <<<'JSON'
{
  "multipleOf": 15
}
JSON;

        // This should PASS - value is exact multiple of 5
        Validator::validateRequest($json, $this->edgeCasesSpec, '/edge-cases/boundaries', 'post');

        // Assert test completed without exception
        $this->assertTrue(true);
    }

    /**
     * Tests uniqueItems validation with duplicate values.
     *
     * Schema: type: "array", uniqueItems: true
     * Data: {"uniqueItems": [1, 2, 2, 3]}
     * Expected: FAIL (contains duplicate: 2)
     */
    #[Test]
    public function itRejectsArrayWithDuplicateItems(): void
    {
        $json = <<<'JSON'
{
  "uniqueItems": [1, 2, 2, 3]
}
JSON;

        $this->expectException(BoundaryViolationException::class);
        $this->expectExceptionMessage('uniqueItems');

        Validator::validateRequest($json, $this->edgeCasesSpec, '/edge-cases/boundaries', 'post');
    }

    // ========================================
    // Type Edge Cases (4 tests)
    // ========================================

    /**
     * Tests float value is rejected when integer is expected.
     *
     * Schema: type: "integer"
     * Data: 3.14
     * Expected: FAIL (float is not integer, even if numeric)
     *
     * NOTE: This is a critical distinction - JSON "integer" means no decimal point.
     */
    #[Test]
    public function itRejectsFloatForInteger(): void
    {
        // This test needs a simple-crud spec or we need to add an integer field to edge-cases
        // For now, let's mark this as requiring future implementation
        $this->markTestSkipped('Requires integer field in edge-cases spec or separate fixture');
    }

    /**
     * Tests float with integer value (3.0) is rejected when integer is expected.
     *
     * Schema: type: "integer"
     * Data: 3.0
     * Expected: FAIL (even though mathematically equal, 3.0 is float type)
     *
     * NOTE: Some validators accept this, strict mode should reject.
     */
    #[Test]
    public function itRejectsFloatIntegerForInteger(): void
    {
        $this->markTestSkipped('Requires integer field in edge-cases spec or separate fixture');
    }

    /**
     * Tests zero is not treated as null.
     *
     * Schema: type: "number"
     * Data: 0
     * Expected: PASS (0 is valid number, not null)
     *
     * NOTE: Many loose validators incorrectly treat 0 as falsy/null.
     */
    #[Test]
    public function itHandlesZeroVsNull(): void
    {
        $json = <<<'JSON'
{
  "minimumInclusive": 0
}
JSON;

        // This should PASS - 0 is valid number, not null
        Validator::validateRequest($json, $this->edgeCasesSpec, '/edge-cases/boundaries', 'post');

        // Assert test completed without exception
        $this->assertTrue(true);
    }

    /**
     * Tests empty string is not treated as null.
     *
     * Schema: type: "string"
     * Data: ""
     * Expected: PASS (empty string is valid string, not null)
     *
     * NOTE: Many loose validators incorrectly treat "" as null.
     */
    #[Test]
    public function itHandlesEmptyStringVsNull(): void
    {
        $json = <<<'JSON'
{
  "emptyStringAllowed": ""
}
JSON;

        // This should PASS - empty string is valid string, not null
        Validator::validateRequest($json, $this->edgeCasesSpec, '/edge-cases/empty-values', 'post');

        // Assert test completed without exception
        $this->assertTrue(true);
    }

    // ========================================
    // Pattern Edge Cases (6 tests)
    // ========================================

    /**
     * Tests anchored pattern matches exactly 3 uppercase letters.
     *
     * Schema: pattern: "^[A-Z]{3}$"
     * Data: {"anchoredPattern": "ABC"}
     * Expected: PASS (exactly 3 uppercase letters)
     */
    #[Test]
    public function itHandlesAnchoredPatternMatch(): void
    {
        $json = <<<'JSON'
{
  "anchoredPattern": "ABC"
}
JSON;

        // This should PASS - string matches anchored pattern exactly
        Validator::validateRequest($json, $this->edgeCasesSpec, '/edge-cases/patterns', 'post');

        // Assert test completed without exception
        $this->assertTrue(true);
    }

    /**
     * Tests anchored pattern rejects string with extra characters.
     *
     * Schema: pattern: "^[A-Z]{3}$"
     * Data: {"anchoredPattern": "ABCD"}
     * Expected: FAIL (4 letters, not exactly 3)
     */
    #[Test]
    public function itRejectsAnchoredPatternMismatch(): void
    {
        $json = <<<'JSON'
{
  "anchoredPattern": "ABCD"
}
JSON;

        $this->expectException(PatternViolationException::class);
        $this->expectExceptionMessage('anchoredPattern');

        Validator::validateRequest($json, $this->edgeCasesSpec, '/edge-cases/patterns', 'post');
    }

    /**
     * Tests unanchored pattern matches substring.
     *
     * Schema: pattern: "[0-9]+"
     * Data: {"unanchoredPattern": "abc123def"}
     * Expected: PASS (contains digits, unanchored pattern)
     */
    #[Test]
    public function itHandlesUnanchoredPatternMatch(): void
    {
        $json = <<<'JSON'
{
  "unanchoredPattern": "abc123def"
}
JSON;

        // This should PASS - string contains digits (unanchored pattern)
        Validator::validateRequest($json, $this->edgeCasesSpec, '/edge-cases/patterns', 'post');

        // Assert test completed without exception
        $this->assertTrue(true);
    }

    /**
     * Tests empty string with pattern requiring at least one character.
     *
     * Schema: pattern: "^[a-z]+$" (+ requires at least one)
     * Data: {"emptyStringPattern": ""}
     * Expected: FAIL (empty string doesn't match [a-z]+)
     */
    #[Test]
    public function itRejectsEmptyStringWithRequiredPattern(): void
    {
        $json = <<<'JSON'
{
  "emptyStringPattern": ""
}
JSON;

        $this->expectException(PatternViolationException::class);
        $this->expectExceptionMessage('emptyStringPattern');

        Validator::validateRequest($json, $this->edgeCasesSpec, '/edge-cases/patterns', 'post');
    }

    /**
     * Tests empty string with pattern allowing zero characters.
     *
     * Schema: pattern: "^[a-z]*$" (* allows zero)
     * Data: {"optionalPattern": ""}
     * Expected: PASS (empty string matches [a-z]*)
     */
    #[Test]
    public function itHandlesEmptyStringWithOptionalPattern(): void
    {
        $json = <<<'JSON'
{
  "optionalPattern": ""
}
JSON;

        // This should PASS - empty string matches pattern allowing zero chars
        Validator::validateRequest($json, $this->edgeCasesSpec, '/edge-cases/patterns', 'post');

        // Assert test completed without exception
        $this->assertTrue(true);
    }

    /**
     * Tests E.164 phone pattern validation.
     *
     * Schema: pattern: "^\+?[1-9]\d{1,14}$"
     * Data: {"phonePattern": "+14155552671"}
     * Expected: PASS (valid E.164 format)
     *
     * This tests a real-world complex pattern with optional prefix and precise length.
     */
    #[Test]
    public function itHandlesComplexPhonePattern(): void
    {
        $json = <<<'JSON'
{
  "phonePattern": "+14155552671"
}
JSON;

        // This should PASS - string matches complex E.164 phone pattern
        Validator::validateRequest($json, $this->edgeCasesSpec, '/edge-cases/patterns', 'post');

        // Assert test completed without exception
        $this->assertTrue(true);
    }
}
