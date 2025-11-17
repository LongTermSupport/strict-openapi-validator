# Phase 9: Error Collection Tests - Summary

## Overview

Phase 9 focused on comprehensive testing of error collection and reporting mechanisms. The validator is designed to collect ALL errors before throwing (not fail-fast), ensuring developers get complete feedback about validation issues in a single pass.

## Test Class: `ErrorCollectionTest.php`

**Location**: `tests/ErrorCollectionTest.php`

**Total Tests**: 15

**Status**: ✅ All 15 tests created and failing as expected with `LogicException: Not yet implemented`

## Test Coverage Breakdown

### 1. Error Collection Behavior (5 tests)

Tests that verify the validator collects all errors before throwing, rather than failing on the first error encountered.

| Test | Purpose | Fixture Used |
|------|---------|--------------|
| `itCollectsAllErrors` | Verifies multiple violations in one request are all collected (5+ errors) | multiple-errors-5.json |
| `itDoesNotFailFast` | Proves validation scans entire request (10+ errors, multiple constraint types) | multiple-errors-10.json |
| `itCollectsErrorsAcrossNestedObjects` | Tests deep object traversal (root → nested → deeply nested) | multiple-errors-10.json |
| `itCollectsErrorsInArrayItems` | Tests array validation with duplicate detection | multiple-errors-10.json |
| `itHandlesHundredsOfErrors` | Performance/stress test for many errors (< 1 second) | multiple-errors-10.json |

**Key Assertions**:
- Error count ≥ expected minimum (5, 10, etc.)
- All errors have required fields (path, constraint, reason)
- Errors span different constraint types
- Errors found at all nesting levels
- Array item errors detected

### 2. Error Message Format (3 tests)

Tests that verify error messages include all necessary context for debugging.

| Test | Purpose | Verifies |
|------|---------|----------|
| `itFormatsErrorWithAllContextFields` | Checks all ValidationError fields are present | path, specReference, constraint, expectedValue, receivedValue, reason, hint |
| `itIncludesJSONPathInError` | Verifies JSONPath format in errors | "request.body.field", "request.body.nested.field" |
| `itIncludesSpecLineNumber` | Checks spec references with line numbers | "filename.ext line N" format |

**Key Assertions**:
- Error message includes "Validation failed with N error(s)"
- Each error shows path, expected, received, hint
- JSONPath starts with "request.body"
- Spec references match pattern: `[a-z0-9_-]+\.(json|yml|yaml) line \d+`

### 3. Hint Generation (4 tests)

Tests that verify helpful hints are generated for common mistakes.

| Test | Purpose | Hint Type | Example |
|------|---------|-----------|---------|
| `itSuggestsSnakeCaseToCamelCaseConversion` | Naming convention issues | Case conversion | user_name → userName |
| `itSuggestsTypeConversion` | Type mismatch errors | Type conversion | "35" → 35 |
| `itSuggestsClosestFieldName` | Typos in field names | Levenshtein distance | usrName → userName |
| `itSuggestsFormatFixes` | Format violations | Format examples | Invalid date → expected format |

**Key Assertions**:
- Hints are generated for applicable errors
- Hints start with lowercase (conversational)
- Hints mention relevant conversion/correction
- Format hints explain expected format

### 4. Error Ordering (3 tests)

Tests that verify errors are properly ordered and grouped for readability.

| Test | Purpose | Ordering Strategy |
|------|---------|-------------------|
| `itOrdersErrorsByPath` | Alphabetical ordering | Sort paths alphabetically |
| `itOrdersErrorsByDepth` | Depth-first ordering | Shallow before deep (count dots) |
| `itGroupsRelatedErrors` | Group by field | Same field errors consecutive |

**Key Assertions**:
- Paths are alphabetically sorted
- Depth increases monotonically (shallow → deep)
- Multiple errors for same field appear consecutively

## Fixtures Used

### multiple-errors-5.json
**Violations**: 5 errors
- Missing required field "name" (has "nam" typo)
- Invalid value for "price" (negative)
- Invalid enum value for "status"
- Invalid date format for "createdAt"
- Additional property "extraField"

### multiple-errors-10.json
**Violations**: 12+ errors
- Invalid UUID format for "id"
- Missing required field "name" (has "nam" typo)
- Description exceeds maxLength (1000+ chars)
- Invalid type for "price" (string instead of number)
- Invalid type for "status" (number instead of string)
- Duplicate array items in "tags"
- Invalid type for nested "metadata.weight" (string instead of number)
- Additional property "metadata.dimensions.volume"
- Additional property "metadata.extraMetadata"
- Invalid date-time format for "createdAt"
- Additional properties "extraField1", "extraField2"

### multiple-errors-cascading.json
**Violations**: Cascading errors
- Invalid discriminator for polymorphic types
- Related errors in oneOf/anyOf/allOf compositions

## Expected Validation Behavior

When validation is implemented, the validator should:

1. **Collect ALL errors** - Scan entire request/response before throwing
2. **Provide context** - Include path, spec reference, constraint, values, reason
3. **Generate hints** - Suggest fixes for common mistakes
4. **Order logically** - Group and sort errors for readability
5. **Format clearly** - LLM-optimized error messages

## Error Message Example

```
Validation failed with 5 errors:

[1] missing required field at request.body.name, breaking strict-schemas.json line 42 expectations
    expected: string
    received: undefined
    hint: did you mean "nam"? (found in request but not in schema)

[2] invalid value at request.body.price, breaking strict-schemas.json line 58 expectations
    expected: number >= 0
    received: -50
    hint: price must be non-negative

[3] invalid enum value at request.body.status, breaking strict-schemas.json line 65 expectations
    expected: one of ["active", "inactive", "draft"]
    received: "pending"

[4] invalid format at request.body.createdAt, breaking strict-schemas.json line 72 expectations
    expected: date-time format (RFC3339)
    received: "invalid-date"
    hint: expected format like "2024-11-17T10:00:00Z"

[5] additional property not allowed at request.body.extraField, breaking strict-schemas.json line 15 expectations
    expected: only known properties
    received: "not allowed"
    hint: remove "extraField" or add to schema if intentional
```

## Integration with Other Phases

- **Phase 1-2**: Uses Spec loading for test fixtures
- **Phase 3-8**: Tests all validation types (type, required, format, enum, etc.)
- **Phase 10**: Edge cases will stress-test error collection further
- **Phase 11**: Performance metrics include error collection overhead

## Test Execution

```bash
# Run all error collection tests
./vendor/bin/phpunit tests/ErrorCollectionTest.php

# Run with detailed output
./vendor/bin/phpunit tests/ErrorCollectionTest.php --testdox

# List all tests
./vendor/bin/phpunit tests/ErrorCollectionTest.php --list-tests
```

**Current Status**: All 15 tests fail with `LogicException: Not yet implemented`

**Expected After Implementation**: All 15 tests pass, verifying comprehensive error collection

## Next Steps: Phase 10

Phase 10 will focus on **Edge Case Tests** covering:
- Deeply nested objects (10+ levels)
- Massive arrays (1000+ items)
- Unicode/emoji in field names
- Circular references
- Empty/null edge cases

**Current Test Suite Summary**:
- SpecValidationTest: 27 tests ✅
- RequestValidationTest: 64 tests ✅
- ResponseValidationTest: 45 tests ✅
- EdgeCaseTest: 30 tests ✅
- **ErrorCollectionTest: 15 tests** ✅ (Phase 9 - just completed)
- **Total: 181 tests**

All tests currently fail with `LogicException: Not yet implemented` as designed.
