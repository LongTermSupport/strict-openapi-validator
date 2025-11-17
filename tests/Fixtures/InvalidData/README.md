# Invalid Data Fixtures

This directory contains comprehensive invalid request/response JSON fixtures that violate OpenAPI specs in specific ways. These fixtures are used to test that the strict validator catches all types of validation failures.

## Purpose

Each fixture demonstrates a specific type of validation failure. Every JSON file has a companion `.txt` file that documents:
- Which spec it violates
- What the violation is
- What the expected error should be
- Suggested hint message (if applicable)

## Directory Structure

```
InvalidData/
├── type-violations/           (10 files)
├── required-violations/       (5 files)
├── additional-properties/     (5 files)
├── format-violations/         (10 files)
├── enum-violations/           (5 files)
├── boundary-violations/       (10 files)
├── pattern-violations/        (5 files)
├── composition-violations/    (8 files)
├── discriminator-violations/  (3 files)
└── multiple-errors/           (3 files)
```

**Total: 64 fixtures (128 files including .txt documentation)**

---

## Fixture Catalog

### 1. Type Violations (10 fixtures)

Tests that validator enforces strict type checking without coercion.

| Fixture | Violation | Key Test |
|---------|-----------|----------|
| `string-expected-number.json` | String where number expected | "thirty-five" instead of 35 |
| `number-expected-string.json` | Number where string expected | 12345 instead of "12345" |
| `null-not-nullable.json` | Null for non-nullable field | null for required non-nullable |
| `array-expected-object.json` | Array where object expected | ["weight", "100g"] instead of {"weight": "100g"} |
| `object-expected-array.json` | Object where array expected | {"0": "tag1"} instead of ["tag1"] |
| `boolean-expected-string.json` | Boolean where string expected | true instead of "active" |
| `integer-expected-float.json` | Float where integer expected | 35.5 instead of 35 |
| `string-number-coercion.json` | String number not coerced | "35" is NOT accepted for integer (STRICT) |
| `mixed-type-array.json` | Array with mixed types | ["string", 123, true, null] |
| `type-null-vs-missing.json` | Null vs missing distinction | Tests Four Combinations (required/optional × nullable/non-nullable) |

**Specs Used**: `simple-crud.json`, `strict-schemas.json`, `edge-cases.json`

---

### 2. Required Violations (5 fixtures)

Tests required field validation and distinction between missing, null, and empty.

| Fixture | Violation | Key Test |
|---------|-----------|----------|
| `required-field-missing.json` | Required field omitted | Missing 'name' field |
| `required-field-null.json` | Required field is null | 'name': null (not nullable) |
| `required-field-empty-string.json` | Required field empty string | "" with minLength: 1 |
| `required-multiple-missing.json` | Multiple required fields missing | Missing both 'name' and 'email' |
| `all-fields-missing.json` | Empty object | {} when fields required |

**Specs Used**: `simple-crud.json`, `edge-cases.json`

**Key Concepts**:
- Missing vs null vs empty string are different
- Validator should collect ALL missing required fields
- minLength applies even when field is present

---

### 3. Additional Properties (5 fixtures)

Tests `additionalProperties: false` enforcement and typo detection.

| Fixture | Violation | Key Test |
|---------|-----------|----------|
| `additional-property-not-allowed.json` | Extra field with strict schema | 'extraField' not in schema |
| `additional-property-snake-case.json` | Naming convention mismatch | 'created_at' vs 'createdAt' |
| `additional-nested-property.json` | Extra field in nested object | metadata.dimensions.volume not allowed |
| `multiple-additional-properties.json` | Multiple extra fields | 3 additional properties |
| `additional-property-typo.json` | Typo creates two errors | 'nam' instead of 'name' → missing + additional |

**Specs Used**: `strict-schemas.json`

**Key Features**:
- Levenshtein distance for typo detection hints
- snake_case vs camelCase detection
- Nested object validation
- Reports both missing required AND additional property for typos

---

### 4. Format Violations (10 fixtures)

Tests format validation for common formats.

| Fixture | Format | Invalid Value | Valid Example |
|---------|--------|---------------|---------------|
| `format-email-invalid.json` | email | "not-an-email" | user@domain.com |
| `format-uuid-invalid.json` | uuid | "not-a-uuid" | 123e4567-e89b-12d3-a456-426614174000 |
| `format-date-time-invalid.json` | date-time | "2024-11-17 10:00:00" | 2024-11-17T10:00:00Z |
| `format-date-invalid.json` | date | "11/17/2024" | 2024-11-17 |
| `format-uri-invalid.json` | uri | "not a url" | https://example.com |
| `format-uri-reference-invalid.json` | uri-reference | "://invalid" | /path/to/resource |
| `format-hostname-invalid.json` | hostname | "invalid_hostname!" | example.com |
| `format-ipv4-invalid.json` | ipv4 | "192.168.1.999" | 192.168.1.1 |
| `format-ipv6-invalid.json` | ipv6 | "::1::2" | 2001:db8::1 |
| `format-phone-invalid.json` | pattern (E.164) | "123-456-7890" | +12125551234 |

**Specs Used**: `simple-crud.json`, `strict-schemas.json`, `edge-cases.json`

**Standards**:
- RFC 3339 for date-time
- RFC 4122 for UUID
- E.164 for phone numbers (via pattern)

---

### 5. Enum Violations (5 fixtures)

Tests enum validation including case sensitivity.

| Fixture | Violation | Key Test |
|---------|-----------|----------|
| `enum-invalid-value.json` | Value not in enum | "pending" not in ["active", "inactive", "discontinued"] |
| `enum-case-mismatch.json` | Wrong case | "Active" vs "active" (case-sensitive) |
| `enum-type-mismatch.json` | Wrong type | 1 instead of "active" |
| `enum-null-value.json` | Null for enum | null not in enum values |
| `enum-empty-string.json` | Empty string | "" not automatically valid for string enums |

**Specs Used**: `strict-schemas.json`

**Key Concepts**:
- Enum validation is case-sensitive
- Type checking happens before enum checking
- Empty string is not special for enums

---

### 6. Boundary Violations (10 fixtures)

Tests numeric, string, and array constraints.

| Fixture | Constraint | Violation |
|---------|------------|-----------|
| `boundary-minimum-violated.json` | minimum: 0 | -10.50 |
| `boundary-maximum-violated.json` | maximum: 150 | 200 |
| `boundary-exclusive-minimum.json` | exclusiveMinimum: 0 | 0 (must be > 0, not >=) |
| `boundary-exclusive-maximum.json` | exclusiveMaximum: 100 | 100 (must be < 100, not <=) |
| `boundary-min-length-violated.json` | minLength: 1 | "" (empty string) |
| `boundary-max-length-violated.json` | maxLength: 200 | 280 character string |
| `boundary-min-items-violated.json` | minItems: 1 | [] (empty array) |
| `boundary-max-items-violated.json` | maxItems: 5 | 6 items |
| `boundary-multiple-of-violated.json` | multipleOf: 5 | 7 |
| `boundary-unique-items-violated.json` | uniqueItems: true | ["electronics", "widget", "electronics"] |

**Specs Used**: `simple-crud.json`, `strict-schemas.json`, `edge-cases.json`

**Key Distinctions**:
- `minimum` vs `exclusiveMinimum` (>= vs >)
- `maximum` vs `exclusiveMaximum` (<= vs <)
- Empty values vs missing values

---

### 7. Pattern Violations (5 fixtures)

Tests regex pattern matching.

| Fixture | Pattern | Violation |
|---------|---------|-----------|
| `pattern-no-match.json` | `^[A-Z]{3}$` | "abc" (lowercase) |
| `pattern-phone-invalid.json` | `^\+[1-9]\d{1,14}$` | "555-1234" (missing +country code) |
| `pattern-hex-color-invalid.json` | `^#[0-9A-Fa-f]{6}$` | "#GGGGGG" (invalid hex) |
| `pattern-alphanumeric-violated.json` | `^[a-zA-Z0-9]+$` | "ABC_123" (underscore not allowed) |
| `pattern-email-custom-invalid.json` | Email pattern with TLD | "user@domain" (missing .com) |

**Specs Used**: `edge-cases.json`

**Features**:
- Anchored vs unanchored patterns
- Character class validation
- Pattern stricter than format

---

### 8. Composition Violations (8 fixtures)

Tests oneOf, anyOf, allOf, and discriminator.

| Fixture | Composition | Violation |
|---------|-------------|-----------|
| `oneof-matches-none.json` | oneOf | Matches 0 schemas (must match 1) |
| `oneof-matches-multiple.json` | oneOf | Matches 2 schemas (must match exactly 1) |
| `anyof-matches-none.json` | anyOf | Matches 0 schemas (must match at least 1) |
| `allof-fails-one.json` | allOf | Fails 1 of 3 schemas (must match all) |
| `allof-fails-multiple.json` | allOf | Fails all 3 schemas |
| `oneof-without-discriminator-ambiguous.json` | oneOf (no discriminator) | Ambiguous data matches multiple |
| `nested-composition-violation.json` | oneOf + enum | Enum error within oneOf branch |
| `composition-with-additional-props.json` | oneOf + additionalProperties | Extra field in oneOf branch |

**Specs Used**: `composition-examples.json`

**Key Tests**:
- oneOf requires EXACTLY one match
- anyOf requires AT LEAST one match
- allOf requires ALL matches
- Nested validation within composition
- discriminator simplifies oneOf

---

### 9. Discriminator Violations (3 fixtures)

Tests discriminator-based oneOf validation.

| Fixture | Violation | Key Test |
|---------|-----------|----------|
| `discriminator-missing.json` | Discriminator field missing | Missing 'petType' field |
| `discriminator-invalid-value.json` | Invalid discriminator value | "bird" not in ["dog", "cat"] |
| `discriminator-unmapped-value.json` | Case mismatch | "Dog" vs "dog" (case-sensitive) |

**Specs Used**: `composition-examples.json`

**Key Concepts**:
- Discriminator field is required
- Discriminator value must be in mapping
- Discriminator values are case-sensitive

---

### 10. Multiple Errors (3 fixtures)

Tests error collection across multiple violation types.

| Fixture | Error Count | Tests |
|---------|-------------|-------|
| `multiple-errors-5.json` | 5 errors | Type, required, additional, enum, format |
| `multiple-errors-10.json` | 12+ errors | Comprehensive - all violation types |
| `multiple-errors-cascading.json` | 4 errors | Errors within composition + nested validation |

**Specs Used**: `strict-schemas.json`, `composition-examples.json`

**Key Tests**:
- Validator collects ALL errors, not just first
- Errors across different types are reported
- Nested errors within composition are collected
- Clear error paths for nested violations

---

## Usage in Tests

### Example Test Pattern

```php
use LTS\StrictOpenApiValidator\Validator;

#[Test]
public function itRejectsStringWhenNumberExpected(): void
{
    $spec = $this->loadSpec('simple-crud.json');
    $invalidData = $this->loadFixture('type-violations/string-expected-number.json');

    $validator = new Validator($spec);
    $result = $validator->validateRequest('POST', '/users', $invalidData);

    self::assertFalse($result->isValid());
    self::assertCount(1, $result->getErrors());

    $error = $result->getErrors()[0];
    self::assertSame('age', $error->getPath());
    self::assertSame('type_mismatch', $error->getCode());
    self::assertStringContainsString('expected integer, got string', $error->getMessage());
    self::assertStringContainsString('Did you mean to use a numeric value', $error->getHint());
}
```

### Loading Fixtures

```php
private function loadFixture(string $path): array
{
    $json = file_get_contents(__DIR__ . '/Fixtures/InvalidData/' . $path);
    return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
}

private function loadExpectedErrors(string $path): string
{
    return file_get_contents(__DIR__ . '/Fixtures/InvalidData/' . str_replace('.json', '.txt', $path));
}
```

---

## Fixture Design Principles

### 1. Realistic Data

All fixtures use realistic data, not placeholder "foo/bar" values:
- Real names: "John Doe", "Max", "Premium Widget"
- Real emails: "john@example.com"
- Real prices: 99.99, -10.50
- Real dates: "2024-11-17T10:00:00Z"

### 2. Single Responsibility

Each fixture tests ONE primary violation (except multiple-errors):
- `string-expected-number.json` → Type mismatch only
- `required-field-missing.json` → Missing required only
- `enum-invalid-value.json` → Invalid enum only

### 3. Clear Documentation

Every `.txt` file includes:
- **Spec**: Which OpenAPI spec is violated
- **Path**: Which API endpoint
- **Violation**: What's wrong
- **Expected Error**: What the validator should report
- **Hint**: Helpful message for developers
- **Note**: Additional context if needed

### 4. Spec Mapping

Fixtures are designed to work with existing valid specs:
- `simple-crud.json` - User CRUD operations
- `strict-schemas.json` - Product with strict validation
- `composition-examples.json` - Pet with discriminator
- `edge-cases.json` - Boundary and pattern tests

### 5. Error Message Testing

Expected errors follow consistent format:
```
[ViolationType] at path '[path]': [description]
Hint: [helpful message]
```

Examples:
- Type mismatch at path 'age': expected integer, got string
- Missing required field 'name'
- Additional property 'extraField' is not allowed
- Enum violation at path 'status': value 'pending' is not in allowed values

---

## Coverage Matrix

| Validation Type | Fixture Count | Coverage |
|----------------|---------------|----------|
| Type checking | 10 | All JSON types + coercion prevention |
| Required fields | 5 | Missing, null, empty, multiple |
| Additional properties | 5 | Single, multiple, nested, typos |
| Format validation | 10 | All common formats (email, UUID, date-time, etc.) |
| Enum validation | 5 | Invalid, case, type, null, empty |
| Boundaries | 10 | Numeric, string, array constraints |
| Patterns | 5 | Regex matching, character classes |
| Composition | 8 | oneOf, anyOf, allOf, nested |
| Discriminator | 3 | Missing, invalid, case |
| Multiple errors | 3 | Error collection, cascading |

**Total: 64 fixtures covering 10 validation categories**

---

## Testing Checklist

When writing tests using these fixtures:

- [ ] Load fixture and companion .txt file
- [ ] Verify error count matches expected
- [ ] Check error codes are correct
- [ ] Validate error paths are accurate
- [ ] Confirm error messages are clear
- [ ] Test hint messages are helpful
- [ ] Verify no false positives (valid data passes)
- [ ] Test error collection (multiple errors reported)

---

## Maintenance

### Adding New Fixtures

When adding new invalid data fixtures:

1. Choose appropriate category directory
2. Create both `.json` (data) and `.txt` (documentation)
3. Use realistic data
4. Document which spec, path, violation, expected error, hint
5. Update this README with new fixture in table
6. Ensure fixture maps to existing valid spec
7. Test that validator produces expected errors

### Naming Convention

```
[category]/[description].json
[category]/[description].txt
```

Examples:
- `type-violations/string-expected-number.json`
- `enum-violations/enum-case-mismatch.json`
- `composition-violations/oneof-matches-none.json`

---

## Related Documentation

- [validation-scenarios.md](../../../CLAUDE/plan/validation-scenarios.md) - Comprehensive validation scenario catalog
- [tdd-implementation.md](../../../CLAUDE/plan/tdd-implementation.md) - TDD implementation plan (Phase 4)
- [../Specs/README.md](../Specs/README.md) - Valid OpenAPI spec fixtures
- [OpenAPI 3.1.0 Specification](https://spec.openapis.org/oas/v3.1.0.html)
- [JSON Schema 2020-12](https://json-schema.org/draft/2020-12/json-schema-core.html)

---

## Statistics

```
Total Fixtures: 64
Total Files: 128 (64 JSON + 64 TXT)
Total Size: ~45KB
Categories: 10
Validation Types Covered: All major OpenAPI 3.1/JSON Schema constraints
```

---

## Next Steps (Phase 5)

With these fixtures in place, Phase 5 will:

1. Write test classes using these fixtures
2. Implement validator to make tests pass (TDD)
3. Ensure all 64 fixtures produce expected errors
4. Verify error messages match documentation
5. Test hint generation for common mistakes

**Ready for Phase 5: Test Writing**
