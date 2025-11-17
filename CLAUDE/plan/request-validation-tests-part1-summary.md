# Request Validation Tests - Part 1 Summary

## Completion Status

**Phase 6 - Part 1**: COMPLETE

Created comprehensive test class for request validation covering:
- Type violations (10 tests)
- Required field violations (5 tests)
- Additional properties (5 tests)

**Total: 20 tests**

---

## Test File

**Location**: `tests/RequestValidationTest.php`

**Specs Used**:
- `simple-crud.json` - Basic user CRUD operations
- `strict-schemas.json` - Strict validation with additionalProperties: false

---

## Test Coverage

### 1. Type Violations (10 tests)

| Test | Fixture | Validates |
|------|---------|-----------|
| `itRejectsStringExpectedNumber()` | `string-expected-number.json` | "thirty-five" rejected when integer expected |
| `itRejectsNumberExpectedString()` | `number-expected-string.json` | 12345 rejected when string expected |
| `itRejectsNullNotNullable()` | `null-not-nullable.json` | null rejected for non-nullable fields |
| `itRejectsArrayExpectedObject()` | `array-expected-object.json` | Array rejected when object expected |
| `itRejectsObjectExpectedArray()` | `object-expected-array.json` | Object rejected when array expected |
| `itRejectsBooleanExpectedString()` | `boolean-expected-string.json` | Boolean rejected when string expected |
| `itRejectsIntegerExpectedFloat()` | `integer-expected-float.json` | Float (35.5) rejected when integer expected |
| `itRejectsStringNumberCoercion()` | `string-number-coercion.json` | **CRITICAL**: "35" NOT coerced to 35 |
| `itRejectsMixedTypeArray()` | `mixed-type-array.json` | Mixed types in array rejected |
| `itRejectsTypeNullVsMissing()` | `type-null-vs-missing.json` | Tests Four Combinations (required/optional × nullable/non-nullable) |

### 2. Required Field Violations (5 tests)

| Test | Fixture | Validates |
|------|---------|-----------|
| `itRejectsRequiredFieldMissing()` | `required-field-missing.json` | Missing required field |
| `itRejectsRequiredFieldNull()` | `required-field-null.json` | null for required non-nullable field |
| `itRejectsRequiredFieldEmptyString()` | `required-field-empty-string.json` | Empty string with minLength: 1 |
| `itRejectsRequiredMultipleMissing()` | `required-multiple-missing.json` | Multiple missing fields (error collection) |
| `itRejectsAllFieldsMissing()` | `all-fields-missing.json` | Empty object when fields required |

### 3. Additional Properties (5 tests)

| Test | Fixture | Validates |
|------|---------|-----------|
| `itRejectsAdditionalPropertyNotAllowed()` | `additional-property-not-allowed.json` | Extra field with additionalProperties: false |
| `itRejectsAdditionalPropertySnakeCase()` | `additional-property-snake-case.json` | created_at vs createdAt (hint generation) |
| `itRejectsAdditionalNestedProperty()` | `additional-nested-property.json` | Extra field in nested object |
| `itRejectsMultipleAdditionalProperties()` | `multiple-additional-properties.json` | Multiple extra fields (error collection) |
| `itRejectsAdditionalPropertyTypo()` | `additional-property-typo.json` | "nam" vs "name" (Levenshtein hint) |

---

## Test Execution Results

**Command**:
```bash
cd vendor/lts/strict-openapi-validator
vendor/bin/phpunit tests/RequestValidationTest.php --no-coverage
```

**Results**:
```
PHPUnit 12.4.3 by Sebastian Bergmann and contributors.

FFFFFFFFFFFFFFFFFFFF                                              20 / 20 (100%)

Time: 00:00.037, Memory: 10.00 MB

FAILURES!
Tests: 20, Assertions: 20, Failures: 20
```

**Expected Behavior**: ✅ All 20 tests FAIL

All tests correctly throw `LogicException: "Not yet implemented"` from `Validator::validateRequest()`.

When actual validation is implemented, these tests will verify that violations are correctly detected and appropriate exceptions are thrown.

---

## Key Features Tested

### 1. Strict Type Checking

**No Coercion**:
- `"35"` ≠ `35` (string vs integer)
- `35.0` ≠ `35` (float vs integer)
- `true` ≠ `"true"` (boolean vs string)
- `1` ≠ `true` (number vs boolean)

### 2. The Four Combinations

Tests all combinations of required/optional × nullable/non-nullable:

| Required? | Nullable? | Missing | null | Non-null |
|-----------|-----------|---------|------|----------|
| ✓ | ✓ | FAIL | PASS | PASS |
| ✓ | ✗ | FAIL | FAIL | PASS |
| ✗ | ✓ | PASS | PASS | PASS |
| ✗ | ✗ | PASS | FAIL | PASS |

### 3. Error Collection

Tests that validator collects ALL errors (not fail-fast):
- `itRejectsRequiredMultipleMissing()` - Multiple missing required fields
- `itRejectsMultipleAdditionalProperties()` - Multiple extra fields

### 4. Helpful Hints

Tests for hint generation:
- **snake_case vs camelCase**: `created_at` → "Did you mean 'createdAt'?"
- **Typos**: `nam` → "Did you mean 'name'?" (Levenshtein distance)
- **Type confusion**: `"35"` → "Did you mean numeric value 35?"

---

## Exception Types Used

All tests expect specific exception types from `src/Exception/`:

- **TypeMismatchException**: Type violations (string vs number, null vs non-nullable, etc.)
- **RequiredFieldMissingException**: Missing required fields
- **AdditionalPropertyException**: Extra properties when additionalProperties: false

---

## Fixtures Used

All tests use fixtures from `tests/Fixtures/InvalidData/`:

**Directory Structure**:
```
InvalidData/
├── type-violations/           (10 fixtures)
├── required-violations/       (5 fixtures)
└── additional-properties/     (5 fixtures)
```

Each fixture has:
- `.json` file with invalid data
- `.txt` file with documentation (spec, violation, expected error, hint)

---

## Part 2 Preview

Part 2 will add tests for:

### Format Violations (10 tests)
- email, uuid, date-time, date, uri, hostname, ipv4, ipv6, phone

### Enum Violations (5 tests)
- Invalid value, case mismatch, type mismatch, null, empty string

### Boundary Violations (10 tests)
- minimum, maximum, exclusiveMinimum, exclusiveMaximum
- minLength, maxLength, minItems, maxItems
- multipleOf, uniqueItems

### Pattern Violations (5 tests)
- Regex pattern matching, character classes

### Composition Violations (8 tests)
- oneOf, anyOf, allOf

### Discriminator Violations (3 tests)
- Missing discriminator, invalid value, unmapped value

### Multiple Errors (3 tests)
- Error collection across violation types

**Estimated Part 2**: 44 additional tests

---

## Implementation Notes

When implementing actual validation:

1. **Parse JSON**: Use `\Safe\json_decode()` with strict type checking
2. **Schema lookup**: Find relevant schema from spec for endpoint/method
3. **Traverse data**: Walk data and schema together
4. **Collect errors**: Use error collector pattern (no fail-fast)
5. **Generate hints**: Levenshtein distance for typos, snake_case detection
6. **Format exceptions**: Build detailed error messages with context

---

## Success Criteria

✅ All 20 tests created
✅ All tests use fixtures (no inline data)
✅ All tests fail predictably with LogicException
✅ All tests expect specific exception types
✅ All tests have clear docblocks
✅ All fixtures documented in .txt files
✅ Tests organized by category with clear comments
✅ Test names follow naming convention (itRejects...)

---

## Next Steps

1. **Part 2**: Write remaining request validation tests (44 tests)
2. **Phase 7**: Write response validation tests
3. **Phase 8**: Write edge case tests
4. **Phase 9**: Write error collection tests
5. **Phase 10**: Verify complete test suite runs

**Then**: Begin actual validation implementation (make tests pass)

---

## Related Documentation

- [tdd-implementation.md](tdd-implementation.md) - Overall TDD plan
- [validation-scenarios.md](validation-scenarios.md) - Complete validation reference
- [../tests/Fixtures/InvalidData/README.md](../tests/Fixtures/InvalidData/README.md) - Fixture catalog
