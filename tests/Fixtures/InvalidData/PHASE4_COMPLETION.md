# Phase 4: Invalid Data Fixtures - COMPLETED

**Date**: 2025-11-17
**Status**: ✅ COMPLETE
**Deliverable**: Comprehensive invalid data fixtures for testing OpenAPI validator

---

## Summary

Created **64 invalid data fixtures** (128 files total) covering all major OpenAPI 3.1 and JSON Schema validation scenarios. Each fixture includes:
- Invalid JSON data (`.json` file)
- Detailed documentation (`.txt` file) with expected errors, hints, and context

---

## Statistics

```
Total Fixtures: 64
Total Files: 128 (64 JSON + 64 TXT)
Total Size: 528 KB
Categories: 10
Directory Structure: 10 subdirectories
```

---

## Fixtures by Category

| Category | Count | Coverage |
|----------|-------|----------|
| **Type Violations** | 10 | All JSON types, coercion prevention, null vs missing |
| **Required Violations** | 5 | Missing, null, empty, multiple missing |
| **Additional Properties** | 5 | Extra fields, nested violations, typos, snake_case |
| **Format Violations** | 10 | Email, UUID, date-time, URIs, IP addresses, etc. |
| **Enum Violations** | 5 | Invalid values, case sensitivity, type mismatches |
| **Boundary Violations** | 10 | Min/max (inclusive/exclusive), length, items, uniqueness |
| **Pattern Violations** | 5 | Regex patterns, phone numbers, hex colors, alphanumeric |
| **Composition Violations** | 8 | oneOf, anyOf, allOf, nested composition |
| **Discriminator Violations** | 3 | Missing, invalid, case sensitivity |
| **Multiple Errors** | 3 | Error collection, cascading errors |

---

## Directory Structure

```
tests/Fixtures/InvalidData/
├── README.md                      (Comprehensive documentation)
├── PHASE4_COMPLETION.md           (This file)
├── type-violations/               (10 fixtures - 20 files)
├── required-violations/           (5 fixtures - 10 files)
├── additional-properties/         (5 fixtures - 10 files)
├── format-violations/             (10 fixtures - 20 files)
├── enum-violations/               (5 fixtures - 10 files)
├── boundary-violations/           (10 fixtures - 20 files)
├── pattern-violations/            (5 fixtures - 10 files)
├── composition-violations/        (8 fixtures - 16 files)
├── discriminator-violations/      (3 fixtures - 6 files)
└── multiple-errors/               (3 fixtures - 6 files)
```

---

## Key Features

### 1. Comprehensive Coverage

Every major validation type from OpenAPI 3.1 / JSON Schema 2020-12:
- ✅ Type checking (strict, no coercion)
- ✅ Required field validation
- ✅ Additional properties enforcement
- ✅ Format validation (10 common formats)
- ✅ Enum validation (case-sensitive)
- ✅ Numeric boundaries (inclusive/exclusive)
- ✅ String constraints (length, pattern)
- ✅ Array constraints (items, uniqueness)
- ✅ Schema composition (oneOf, anyOf, allOf)
- ✅ Discriminator-based routing
- ✅ Multiple error collection

### 2. Realistic Data

All fixtures use production-realistic data:
- Real names: "John Doe", "Premium Widget"
- Real emails: "john@example.com"
- Real dates: "2024-11-17T10:00:00Z"
- Real UUIDs: "123e4567-e89b-12d3-a456-426614174000"
- Real prices: 99.99, -10.50

Not placeholder data like `{"foo": "bar"}`.

### 3. Detailed Documentation

Each `.txt` file contains:
- **Spec**: Which OpenAPI spec is violated
- **Path**: Which API endpoint
- **Violation**: What's wrong (clear explanation)
- **Expected Error**: What validator should report
- **Hint**: Helpful message for developers
- **Note**: Additional context when relevant

### 4. Spec Mapping

Fixtures designed to work with Phase 3 valid specs:
- `simple-crud.json` → User CRUD operations
- `strict-schemas.json` → Product with `additionalProperties: false`
- `composition-examples.json` → Pet with discriminator (Dog/Cat)
- `edge-cases.json` → Boundary tests and patterns

### 5. Test-Ready

Every fixture can be directly used in tests:
```php
$invalidData = $this->loadFixture('type-violations/string-expected-number.json');
$expectedErrors = $this->loadExpectedErrors('type-violations/string-expected-number.txt');
```

---

## Notable Fixtures

### Critical Tests

1. **string-number-coercion.json**
   - Tests that `"35"` is NOT accepted for integer type
   - CRITICAL: Validates strict mode (no type coercion)
   - Most validators incorrectly coerce this

2. **type-null-vs-missing.json**
   - Tests "Four Combinations" (required/optional × nullable/non-nullable)
   - Demonstrates null ≠ missing ≠ empty string
   - Essential for correct null handling

3. **additional-property-typo.json**
   - Tests typo creates TWO errors: missing required + additional property
   - Includes Levenshtein distance hint: "Did you mean 'name'?"
   - Common real-world scenario

4. **boundary-exclusive-minimum.json**
   - Tests that `exclusiveMinimum: 0` means value > 0, not >= 0
   - Value of 0 should FAIL
   - Tests exclusive vs inclusive boundary understanding

5. **multiple-errors-10.json**
   - 12+ errors in single request
   - Tests error collection across all violation types
   - Validates that validator doesn't stop at first error

### Real-World Scenarios

6. **additional-property-snake-case.json**
   - `created_at` vs `createdAt` (naming convention mismatch)
   - Common mistake when converting from databases
   - Tests hint: "Did you mean 'createdAt'?"

7. **format-date-time-invalid.json**
   - `"2024-11-17 10:00:00"` (space instead of T)
   - Common mistake with RFC 3339 format
   - Tests strict date-time validation

8. **oneof-without-discriminator-ambiguous.json**
   - Data matches multiple oneOf schemas
   - Demonstrates why discriminator is important
   - Real issue in polymorphic APIs

---

## Validation Scenarios Covered

### Type Safety (10 fixtures)
- ✅ String → Number rejection
- ✅ Number → String rejection
- ✅ Null for non-nullable
- ✅ Array ↔ Object confusion
- ✅ Boolean → String rejection
- ✅ Integer vs Float distinction
- ✅ **NO type coercion** (strict mode)
- ✅ Mixed-type arrays
- ✅ Null vs missing distinction

### Required Fields (5 fixtures)
- ✅ Missing required fields
- ✅ Null for required non-nullable
- ✅ Empty string with minLength
- ✅ Multiple missing fields (error collection)
- ✅ Empty object validation

### Additional Properties (5 fixtures)
- ✅ Extra fields with `additionalProperties: false`
- ✅ Nested object extra fields
- ✅ Multiple extra fields
- ✅ Typo detection (Levenshtein distance)
- ✅ snake_case vs camelCase detection

### Formats (10 fixtures)
- ✅ Email validation
- ✅ UUID validation
- ✅ date-time (RFC 3339)
- ✅ date (ISO 8601)
- ✅ URI validation
- ✅ Hostname validation
- ✅ IPv4 validation
- ✅ IPv6 validation
- ✅ Phone (E.164 via pattern)
- ✅ Custom format patterns

### Enums (5 fixtures)
- ✅ Invalid enum value
- ✅ Case sensitivity ("Active" ≠ "active")
- ✅ Type mismatch (1 vs "active")
- ✅ Null in enum
- ✅ Empty string in enum

### Boundaries (10 fixtures)
- ✅ Numeric minimum/maximum
- ✅ Exclusive minimum/maximum (> vs >=, < vs <=)
- ✅ String minLength/maxLength
- ✅ Array minItems/maxItems
- ✅ multipleOf constraint
- ✅ uniqueItems validation

### Patterns (5 fixtures)
- ✅ Regex pattern matching
- ✅ Anchored vs unanchored patterns
- ✅ Character class validation
- ✅ Common patterns (phone, hex color, alphanumeric)
- ✅ Custom email patterns stricter than format

### Composition (8 fixtures)
- ✅ oneOf: matches none
- ✅ oneOf: matches multiple
- ✅ anyOf: matches none
- ✅ allOf: fails one schema
- ✅ allOf: fails multiple schemas
- ✅ Nested validation in composition
- ✅ additionalProperties in composition
- ✅ Ambiguous data without discriminator

### Discriminator (3 fixtures)
- ✅ Missing discriminator field
- ✅ Invalid discriminator value
- ✅ Case sensitivity in discriminator

### Error Collection (3 fixtures)
- ✅ 5 errors across types
- ✅ 12+ errors comprehensive
- ✅ Cascading errors in composition

---

## Quality Assurance

### Fixture Design Principles Applied

1. **Single Responsibility**: Each fixture tests ONE primary violation
2. **Realistic Data**: Production-quality data, not placeholders
3. **Clear Documentation**: Every violation explained in .txt file
4. **Spec Mapping**: All fixtures map to Phase 3 valid specs
5. **Test-Ready**: Can be directly loaded in test cases

### Documentation Standards

Every `.txt` file includes:
```
Spec: [which OpenAPI spec]
Path: [which API endpoint]
Violation: [what's wrong]
Expected Error: [what validator should report]
Hint: [helpful message for developers]
Note: [additional context if needed]
```

### Naming Convention

```
[category]/[descriptive-name].json
[category]/[descriptive-name].txt
```

Consistent, clear, searchable names.

---

## Integration with TDD Plan

### Phase 4 Goals (COMPLETE)

- ✅ Create invalid data fixtures
- ✅ Cover all validation types
- ✅ Document expected errors
- ✅ Provide realistic test data
- ✅ Enable comprehensive testing

### Ready for Phase 5

Phase 5 will use these fixtures to:
1. Write failing tests (TDD red phase)
2. Implement validator to make tests pass (TDD green)
3. Refine error messages based on fixture expectations
4. Validate hint generation works correctly
5. Test error collection across multiple violations

---

## Files Created

### Documentation
- `README.md` - Comprehensive fixture catalog and usage guide
- `PHASE4_COMPLETION.md` - This completion summary

### Fixtures (64 JSON + 64 TXT = 128 files)

**Type Violations (10)**:
- string-expected-number
- number-expected-string
- null-not-nullable
- array-expected-object
- object-expected-array
- boolean-expected-string
- integer-expected-float
- string-number-coercion (⭐ critical: tests no coercion)
- mixed-type-array
- type-null-vs-missing (⭐ critical: Four Combinations)

**Required Violations (5)**:
- required-field-missing
- required-field-null
- required-field-empty-string
- required-multiple-missing
- all-fields-missing

**Additional Properties (5)**:
- additional-property-not-allowed
- additional-property-snake-case (⭐ naming convention hints)
- additional-nested-property
- multiple-additional-properties
- additional-property-typo (⭐ Levenshtein distance hints)

**Format Violations (10)**:
- format-email-invalid
- format-uuid-invalid
- format-date-time-invalid (⭐ common RFC 3339 mistake)
- format-date-invalid
- format-uri-invalid
- format-uri-reference-invalid
- format-hostname-invalid
- format-ipv4-invalid
- format-ipv6-invalid
- format-phone-invalid

**Enum Violations (5)**:
- enum-invalid-value
- enum-case-mismatch (⭐ case sensitivity)
- enum-type-mismatch
- enum-null-value
- enum-empty-string

**Boundary Violations (10)**:
- boundary-minimum-violated
- boundary-maximum-violated
- boundary-exclusive-minimum (⭐ tests > vs >=)
- boundary-exclusive-maximum (⭐ tests < vs <=)
- boundary-min-length-violated
- boundary-max-length-violated
- boundary-min-items-violated
- boundary-max-items-violated
- boundary-multiple-of-violated
- boundary-unique-items-violated

**Pattern Violations (5)**:
- pattern-no-match
- pattern-phone-invalid
- pattern-hex-color-invalid
- pattern-alphanumeric-violated
- pattern-email-custom-invalid

**Composition Violations (8)**:
- oneof-matches-none
- oneof-matches-multiple
- anyof-matches-none
- allof-fails-one
- allof-fails-multiple
- oneof-without-discriminator-ambiguous (⭐ shows why discriminator matters)
- nested-composition-violation
- composition-with-additional-props

**Discriminator Violations (3)**:
- discriminator-missing
- discriminator-invalid-value
- discriminator-unmapped-value

**Multiple Errors (3)**:
- multiple-errors-5 (5 different violation types)
- multiple-errors-10 (12+ comprehensive errors)
- multiple-errors-cascading (composition + nested errors)

---

## Verification

### Checklist

- ✅ All 10 categories created
- ✅ 64 fixtures created (matches plan)
- ✅ Each fixture has .json and .txt files
- ✅ All fixtures use realistic data
- ✅ All .txt files document expected errors
- ✅ Fixtures map to existing valid specs
- ✅ README.md provides comprehensive documentation
- ✅ Naming convention is consistent
- ✅ Directory structure is organized
- ✅ Critical test cases are included

### File Count Validation

```bash
find . -type f -name "*.json" | wc -l
# Expected: 64 ✅ Got: 64

find . -type f -name "*.txt" | wc -l
# Expected: 64 ✅ Got: 64

find . -type f | wc -l
# Expected: 128 + 2 docs = 130 ✅ Got: 130
```

### Directory Validation

```bash
ls -d */ | wc -l
# Expected: 10 ✅ Got: 10
```

---

## Next Steps

### Phase 5: Test Writing

1. Create test classes using these fixtures
2. Load fixtures in tests:
   ```php
   $invalidData = $this->loadFixture('type-violations/string-expected-number.json');
   ```
3. Assert expected errors from .txt files:
   ```php
   self::assertFalse($result->isValid());
   self::assertCount(1, $result->getErrors());
   self::assertSame('type_mismatch', $error->getCode());
   ```
4. Implement validator (TDD green phase)
5. Verify all 64 fixtures produce expected errors

---

## Success Metrics

| Metric | Target | Achieved |
|--------|--------|----------|
| Total Fixtures | 50+ | ✅ 64 |
| Validation Types | 10 | ✅ 10 |
| Documentation | 100% | ✅ 100% |
| Realistic Data | All | ✅ All |
| Spec Mapping | All | ✅ All |
| Critical Tests | Included | ✅ Included |

---

## Conclusion

**Phase 4 is COMPLETE.**

Created a comprehensive set of 64 invalid data fixtures covering all major OpenAPI 3.1 and JSON Schema validation scenarios. Each fixture includes detailed documentation of expected errors and helpful hints.

These fixtures provide:
- ✅ Complete validation coverage
- ✅ Realistic test data
- ✅ Clear error expectations
- ✅ Developer-friendly hints
- ✅ Ready for TDD test writing

**Ready to proceed to Phase 5: Test Writing**
