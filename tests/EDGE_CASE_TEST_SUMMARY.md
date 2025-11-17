# Edge Case Test Summary

## Overview

Created `tests/EdgeCaseTest.php` with 30 comprehensive tests covering subtle validation scenarios.

## Test Coverage

### 1. The Four Combinations: Nullable × Required (4 tests)

Tests the interaction between nullable and required properties:

| Test | Schema | Data | Expected | Status |
|------|--------|------|----------|--------|
| `itHandlesRequiredNullableFieldWithNull` | required: true, type: ["string", "null"] | null | PASS | ✓ Passes (expects LogicException) |
| `itRejectsRequiredNonNullableFieldWithNull` | required: true, type: "string" | null | FAIL (TypeMismatch) | ✓ Expects TypeMismatchException |
| `itHandlesOptionalNullableField` | required: false, type: ["string", "null"] | null or missing | PASS | ✓ Passes (expects LogicException) |
| `itRejectsOptionalNonNullableFieldWithNull` | required: false, type: "string" | null | FAIL (TypeMismatch) | ✓ Expects TypeMismatchException |

**Key Insight**: The matrix of required × nullable creates 4 distinct behaviors:
- Required + Nullable: Must be present, can be null
- Required + Non-nullable: Must be present, cannot be null
- Optional + Nullable: Can be missing, can be null
- Optional + Non-nullable: Can be missing, but if present cannot be null

### 2. Empty Values (6 tests)

Tests edge cases with empty strings, arrays, and objects:

| Test | Constraint | Data | Expected | Status |
|------|-----------|------|----------|--------|
| `itHandlesEmptyStringWithoutMinLength` | No minLength | "" | PASS | ✓ Passes (expects LogicException) |
| `itRejectsEmptyStringWithMinLength` | minLength: 1 | "" | FAIL (Boundary) | ✓ Expects BoundaryViolationException |
| `itHandlesEmptyArrayWithoutMinItems` | No minItems | [] | PASS | ✓ Passes (expects LogicException) |
| `itRejectsEmptyArrayWithMinItems` | minItems: 1 | [] | FAIL (Boundary) | ✓ Expects BoundaryViolationException |
| `itHandlesEmptyObjectWithoutMinProperties` | No minProperties | {} | PASS | ✓ Passes (expects LogicException) |
| `itRejectsEmptyObjectWithMinProperties` | minProperties: 1 | {} | FAIL (Boundary) | ✓ Expects BoundaryViolationException |

**Key Insight**: Empty values are valid by default. Only explicit constraints (minLength, minItems, minProperties) make them invalid.

### 3. Boundary Edge Cases (10 tests)

Tests inclusive vs exclusive boundaries and exact matches:

| Test | Constraint | Data | Expected | Status |
|------|-----------|------|----------|--------|
| `itHandlesValueEqualsMinimum` | minimum: 0 | 0 | PASS (inclusive) | ✓ Passes (expects LogicException) |
| `itHandlesValueEqualsMaximum` | maximum: 100 | 100 | PASS (inclusive) | ✓ Passes (expects LogicException) |
| `itRejectsValueEqualsExclusiveMinimum` | exclusiveMinimum: 0 | 0 | FAIL (must be > 0) | ✓ Expects BoundaryViolationException |
| `itRejectsValueEqualsExclusiveMaximum` | exclusiveMaximum: 100 | 100 | FAIL (must be < 100) | ✓ Expects BoundaryViolationException |
| `itHandlesStringLengthEqualsMinLength` | minLength: 3 | "abc" | PASS (inclusive) | ✓ Passes (expects LogicException) |
| `itHandlesStringLengthEqualsMaxLength` | maxLength: 5 | "hello" | PASS (inclusive) | ✓ Passes (expects LogicException) |
| `itHandlesArrayLengthEqualsMinItems` | minItems: 2 | ["a", "b"] | PASS (inclusive) | ✓ Passes (expects LogicException) |
| `itHandlesArrayLengthEqualsMaxItems` | maxItems: 3 | ["a", "b", "c"] | PASS (inclusive) | ✓ Passes (expects LogicException) |
| `itHandlesMultipleOfWithExactMultiple` | multipleOf: 5 | 15 | PASS | ✓ Passes (expects LogicException) |
| `itRejectsArrayWithDuplicateItems` | uniqueItems: true | [1, 2, 2, 3] | FAIL | ✓ Expects BoundaryViolationException |

**Key Insight**:
- `minimum`/`maximum` are **inclusive** by default (equals is valid)
- `exclusiveMinimum`/`exclusiveMaximum` are **exclusive** (equals is invalid)
- String/array length constraints are always **inclusive**

### 4. Type Edge Cases (4 tests)

Tests subtle type distinctions:

| Test | Schema Type | Data | Expected | Status |
|------|------------|------|----------|--------|
| `itRejectsFloatForInteger` | integer | 3.14 | FAIL (TypeMismatch) | ⊘ Skipped (needs fixture update) |
| `itRejectsFloatIntegerForInteger` | integer | 3.0 | FAIL (TypeMismatch) | ⊘ Skipped (needs fixture update) |
| `itHandlesZeroVsNull` | number | 0 | PASS (0 is not null) | ✓ Passes (expects LogicException) |
| `itHandlesEmptyStringVsNull` | string | "" | PASS ("" is not null) | ✓ Passes (expects LogicException) |

**Key Insight**:
- 0 is not null (many validators incorrectly treat as falsy)
- "" is not null (many validators incorrectly treat as falsy)
- 3.14 is not integer (float vs integer distinction)
- 3.0 is not integer (even if mathematically equal)

**Note**: Two tests are skipped pending addition of integer field to edge-cases.json spec.

### 5. Pattern Edge Cases (6 tests)

Tests regex pattern matching edge cases:

| Test | Pattern | Data | Expected | Status |
|------|---------|------|----------|--------|
| `itHandlesAnchoredPatternMatch` | `^[A-Z]{3}$` | "ABC" | PASS | ✓ Passes (expects LogicException) |
| `itRejectsAnchoredPatternMismatch` | `^[A-Z]{3}$` | "ABCD" | FAIL (Pattern) | ✓ Expects PatternViolationException |
| `itHandlesUnanchoredPatternMatch` | `[0-9]+` | "abc123def" | PASS | ✓ Passes (expects LogicException) |
| `itRejectsEmptyStringWithRequiredPattern` | `^[a-z]+$` | "" | FAIL (+ requires at least one) | ✓ Expects PatternViolationException |
| `itHandlesEmptyStringWithOptionalPattern` | `^[a-z]*$` | "" | PASS (* allows zero) | ✓ Passes (expects LogicException) |
| `itHandlesComplexPhonePattern` | `^\+?[1-9]\d{1,14}$` | "+14155552671" | PASS (E.164 format) | ✓ Passes (expects LogicException) |

**Key Insight**:
- Anchors (^, $) matter - `^[A-Z]{3}$` vs `[A-Z]{3}` behave differently
- Quantifiers matter - `+` (one or more) vs `*` (zero or more)
- Empty string behavior depends on regex quantifiers

## Test Results

**Total Tests**: 30
- **Passing**: 18 (correctly expecting LogicException)
- **Failing**: 10 (expecting specific validation exceptions but getting LogicException)
- **Skipped**: 2 (awaiting fixture updates)

### Why Tests "Fail"

Tests that expect validation exceptions (TypeMismatchException, BoundaryViolationException, PatternViolationException) currently "fail" because the validator throws LogicException with "Not yet implemented" message.

**This is expected behavior for Phase 8** - we're writing tests that document expected behavior before implementation.

When validation is implemented:
- Tests expecting LogicException should be updated to expect PASS
- Tests expecting validation exceptions will start passing

## Test Quality

### Strengths

1. **Comprehensive Coverage**: Tests cover all major edge case categories
2. **Clear Documentation**: Each test has detailed PHPDoc explaining scenario
3. **Realistic Data**: Uses real-world patterns (E.164 phone numbers, hex colors)
4. **Schema-Driven**: Uses edge-cases.json spec designed for these scenarios

### Areas for Future Enhancement

1. **Integer Type Tests**: Need to add integer field to edge-cases.json spec to test float vs integer edge cases
2. **Composition Edge Cases**: Could add tests for allOf/oneOf/anyOf with nullable/required combinations
3. **Unicode Patterns**: Could add more complex unicode pattern tests
4. **Nested Objects**: Could add tests for deeply nested nullable fields

## Integration with Other Tests

### Relationship to RequestValidationTest.php

- **RequestValidationTest**: Covers main validation scenarios (type violations, required fields, etc.)
- **EdgeCaseTest**: Covers subtle edge cases within those scenarios

Example:
- RequestValidationTest tests that strings are validated
- EdgeCaseTest tests that empty strings are handled correctly with/without minLength

### Relationship to edge-cases.json Spec

The spec was specifically designed for these tests:
- `/edge-cases/nullable` → Nullable combination tests
- `/edge-cases/empty-values` → Empty value tests
- `/edge-cases/boundaries` → Boundary condition tests
- `/edge-cases/patterns` → Pattern matching tests

## Running Tests

```bash
cd /var/www/vhosts/version_v2/green/product-data-api/vendor/lts/strict-openapi-validator
vendor/bin/phpunit tests/EdgeCaseTest.php --testdox
```

## Next Steps (Phase 9)

1. Review test coverage and identify gaps
2. Add integer field to edge-cases.json if needed
3. Consider adding composition + nullable edge cases
4. Review all tests before proceeding to implementation
5. Update tests when validation is implemented

## Key Takeaways

**The Four Combinations Matrix** is critical:

|  | Nullable | Non-Nullable |
|--|----------|--------------|
| **Required** | Must be present, can be null | Must be present, cannot be null |
| **Optional** | Can be missing, can be null | Can be missing, if present cannot be null |

**Boundary Behavior**:
- Inclusive constraints: value **equals** boundary is valid
- Exclusive constraints: value **equals** boundary is invalid

**Type Distinctions Matter**:
- 0 ≠ null
- "" ≠ null
- 3.0 ≠ integer (type difference, even if mathematically equal)

**Pattern Quantifiers**:
- `+` requires at least one (empty string fails)
- `*` allows zero (empty string passes)
