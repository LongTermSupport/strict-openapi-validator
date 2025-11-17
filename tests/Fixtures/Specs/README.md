# OpenAPI 3.1.0 Test Fixtures

This directory contains valid OpenAPI 3.1.0 specification fixtures used for testing the strict validator.

## Fixture Catalog

### 1. minimal-valid.json

**Purpose**: Absolute minimum valid OpenAPI 3.1.0 specification

**Contents**:
- `openapi: 3.1.0`
- `info: {title, version}`
- `paths: {}` (empty but present)

**Test Scenarios**:
- Validates spec parsing works with minimal requirements
- Tests that empty paths object is acceptable
- Used as baseline for spec validation tests

**Size**: ~100 bytes

---

### 2. simple-crud.json

**Purpose**: Basic CRUD API demonstrating standard validation patterns

**Contents**:
- **User Resource**: id (integer), name (string, required), email (string, format: email, required), age (integer, optional)
- **Operations**:
  - `GET /users` - List all users
  - `POST /users` - Create user (requires name, email)
  - `GET /users/{id}` - Get user by ID
  - `PUT /users/{id}` - Update user
  - `DELETE /users/{id}` - Delete user
- **Responses**: 200, 201, 204, 404, 400
- **Error Schema**: message (required), code (optional)

**Test Scenarios**:
- Request body validation (POST/PUT)
- Path parameter validation (integer ID)
- Required field validation
- Format validation (email)
- Boundary validation (age: 0-150)
- Basic response validation
- Content-type validation (application/json)

**Use Cases**:
- Type mismatch tests (string vs integer)
- Required field missing tests
- Invalid email format tests
- Boundary violation tests (negative age, age > 150)
- Additional property tests

**Size**: ~6KB

---

### 3. strict-schemas.json

**Purpose**: Demonstrates strict validation with `additionalProperties: false`

**Contents**:
- **Product Resource**:
  - id (uuid, required)
  - name (string, 1-200 chars, required)
  - description (string, max 1000 chars, optional)
  - price (number, >= 0, required)
  - status (enum: active/inactive/discontinued, required)
  - tags (array of unique strings, optional)
  - metadata (nested object with weight, dimensions)
  - createdAt (date-time, required)
  - updatedAt (date-time, optional)
- **ALL schemas use `additionalProperties: false`**
- **Nested validation** (metadata.dimensions with required fields)

**Test Scenarios**:
- Additional property rejection (strict mode)
- UUID format validation
- Enum validation (case-sensitive)
- Nested object validation
- Array uniqueness validation
- Date-time format validation
- Min/max length constraints
- Nested `additionalProperties: false` enforcement

**Use Cases**:
- Extra field rejection tests
- snake_case vs camelCase detection
- Nested validation failures
- Multiple validation errors in single request
- Strict object structure enforcement

**Size**: ~5KB

---

### 4. composition-examples.json

**Purpose**: Schema composition (oneOf, anyOf, allOf, discriminator)

**Contents**:
- **Pet (oneOf with discriminator)**:
  - Dog: petType="dog", name, breed (enum), isGoodBoy
  - Cat: petType="cat", name, indoor, livesRemaining
  - Discriminator field: `petType`
  - Mapping: dog → Dog schema, cat → Cat schema
- **FlexibleValue (anyOf)**:
  - Can have stringValue OR numericValue OR booleanValue (or multiple)
- **ExtendedUser (allOf)**:
  - Combines BaseUser + UserDetails + role property
  - Tests inheritance/composition pattern

**Test Scenarios**:
- oneOf: matches exactly one schema
- oneOf: matches none (fails)
- oneOf: matches multiple (fails)
- anyOf: matches at least one
- anyOf: matches none (fails)
- allOf: matches all schemas
- allOf: fails one schema
- Discriminator: property present and maps correctly
- Discriminator: missing discriminator field
- Discriminator: invalid discriminator value

**Use Cases**:
- Polymorphic type validation
- Discriminator-based routing
- Schema inheritance
- Flexible input validation
- Composition edge cases

**Size**: ~4KB

---

### 5. edge-cases.json

**Purpose**: Edge cases for nullable, empty values, boundaries, patterns

**Contents**:
- **NullableCombinations**: All 4 permutations
  - requiredNullable: required + nullable (must be present, can be null)
  - requiredNonNullable: required + non-nullable (must be present, must be non-null)
  - optionalNullable: optional + nullable (can omit, can be null)
  - optionalNonNullable: optional + non-nullable (can omit, if present must be non-null)
- **EmptyValues**:
  - emptyStringAllowed (no minLength)
  - emptyStringNotAllowed (minLength: 1)
  - emptyArrayAllowed (no minItems)
  - emptyArrayNotAllowed (minItems: 1)
  - emptyObjectAllowed (no minProperties)
  - emptyObjectNotAllowed (minProperties: 1)
- **BoundaryTests**:
  - minimumInclusive (0 is valid)
  - maximumInclusive (100 is valid)
  - exclusiveMinimum (0 is NOT valid)
  - exclusiveMaximum (100 is NOT valid)
  - minLength/maxLength edge cases
  - minItems/maxItems edge cases
  - multipleOf constraint
  - uniqueItems constraint
- **PatternTests**:
  - anchoredPattern (`^[A-Z]{3}$`)
  - unanchoredPattern (`[0-9]+`)
  - emptyStringPattern (fails with `+`)
  - optionalPattern (passes with `*`)
  - phonePattern (E.164 format)
  - hexColorPattern

**Test Scenarios**:
- Null vs missing vs empty string distinction
- Boundary inclusivity/exclusivity
- Empty value validation
- Pattern anchoring behavior
- Unicode in patterns
- Complex regex patterns

**Use Cases**:
- The Four Combinations tests (nullable matrix)
- Empty value edge cases
- Boundary edge cases (equals min/max)
- Pattern matching edge cases
- Comprehensive edge case coverage

**Size**: ~4KB

---

## Fixture Usage Map

| Test Suite | Fixtures Used | Purpose |
|------------|---------------|---------|
| SpecValidationTest | minimal-valid.json | Test spec parsing and validation |
| RequestValidationTest | simple-crud.json, strict-schemas.json | Request body validation |
| ResponseValidationTest | simple-crud.json | Response validation |
| CompositionTest | composition-examples.json | oneOf/anyOf/allOf/discriminator |
| EdgeCaseTest | edge-cases.json | Nullable, boundaries, patterns |
| ErrorCollectionTest | strict-schemas.json | Multiple error collection |

---

## Validation Status

All fixtures have been validated for OpenAPI 3.1.0 compliance:

- ✅ **minimal-valid.json**: Valid minimal spec
- ✅ **simple-crud.json**: Valid CRUD API spec
- ✅ **strict-schemas.json**: Valid with strict schemas
- ✅ **composition-examples.json**: Valid composition examples
- ✅ **edge-cases.json**: Valid edge case scenarios

---

## Adding New Fixtures

When adding new fixtures:

1. **Validate** using OpenAPI 3.1.0 validator
2. **Document** purpose and test scenarios in this README
3. **Name** descriptively (e.g., `security-schemes.json`, `webhooks-example.json`)
4. **Follow pattern**: Use JSON format for consistency
5. **Include descriptions**: Add description fields for documentation
6. **Update table**: Add to Fixture Usage Map

---

## Related Documentation

- [validation-scenarios.md](../../../CLAUDE/plan/validation-scenarios.md) - Comprehensive validation scenario catalog
- [tdd-implementation.md](../../../CLAUDE/plan/tdd-implementation.md) - TDD implementation plan
- [OpenAPI 3.1.0 Specification](https://spec.openapis.org/oas/v3.1.0.html)
- [JSON Schema 2020-12](https://json-schema.org/draft/2020-12/json-schema-core.html)

---

## Notes

- All fixtures use JSON format (easier to work with than YAML)
- Fixtures include helpful description fields for clarity
- Each fixture targets specific validation scenarios
- Fixtures are designed to be combined in tests
- No invalid specs here - only valid OpenAPI 3.1.0 documents
- Invalid data fixtures will be in `tests/Fixtures/InvalidData/` (Phase 4)
