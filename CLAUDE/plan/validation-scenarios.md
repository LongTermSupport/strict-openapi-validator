# Validation Scenarios - Comprehensive Reference

This document catalogs every validation scenario that must be tested.

## OpenAPI 3.1.0 Spec Validation

### Document Structure (REQUIRED)

| Scenario | Valid Example | Invalid Example | Expected Error |
|----------|---------------|-----------------|----------------|
| OpenAPI version present | `{"openapi": "3.1.0", ...}` | `{"info": {...}}` | MissingRequiredSpecFieldException |
| OpenAPI version 3.1.x | `{"openapi": "3.1.0"}` | `{"openapi": "3.0.0"}` | InvalidSpecVersionException |
| Info object present | `{"info": {"title": "API", "version": "1.0.0"}}` | `{"openapi": "3.1.0"}` | MissingRequiredSpecFieldException |
| Info.title present | `{"info": {"title": "API", "version": "1.0.0"}}` | `{"info": {"version": "1.0.0"}}` | MissingRequiredSpecFieldException |
| Info.version present | `{"info": {"title": "API", "version": "1.0.0"}}` | `{"info": {"title": "API"}}` | MissingRequiredSpecFieldException |
| At least one: paths/components/webhooks | `{"paths": {}}` or `{"components": {}}` | `{}` | InvalidSpecException |

### Path Validation

| Scenario | Valid Example | Invalid Example | Expected Error |
|----------|---------------|-----------------|----------------|
| Path starts with / | `"/users": {...}` | `"users": {...}` | InvalidSpecException |
| Path parameters match template | `/users/{id}` with parameter `{name: "id"}` | `/users/{id}` without parameter | InvalidSpecException |
| No duplicate templated paths | `/users/{id}` and `/products/{id}` | `/users/{userId}` and `/users/{id}` (same structure) | InvalidSpecException |
| Template expressions valid | `/users/{id}` | `/users/{id?}` (invalid char) | InvalidSpecException |

### Operation Validation

| Scenario | Valid Example | Invalid Example | Expected Error |
|----------|---------------|-----------------|----------------|
| Valid HTTP methods | `get`, `post`, `put`, `delete`, `patch`, `options`, `head`, `trace` | `fetch`, `query` | InvalidSpecException |
| Unique operationId | All operations have different IDs | Two operations with `operationId: "getUser"` | InvalidSpecException |
| Path parameters required=true | `{in: "path", name: "id", required: true}` | `{in: "path", name: "id", required: false}` | InvalidSpecException |

### Response Validation

| Scenario | Valid Example | Invalid Example | Expected Error |
|----------|---------------|-----------------|----------------|
| At least one response | `responses: {"200": {...}}` | `responses: {}` | InvalidSpecException |
| Response has description | `"200": {description: "OK"}` | `"200": {}` | MissingRequiredSpecFieldException |
| Status codes are strings | `"200": {...}` | `200: {...}` | InvalidSpecException |

### Schema Validation

| Scenario | Valid Example | Invalid Example | Expected Error |
|----------|---------------|-----------------|----------------|
| Valid type | `{type: "string"}` | `{type: "varchar"}` | InvalidSpecException |
| Valid format | `{type: "string", format: "email"}` | `{type: "string", format: "electronic-mail"}` | InvalidSpecException |
| Min <= Max | `{minimum: 0, maximum: 100}` | `{minimum: 100, maximum: 0}` | InvalidSpecException |
| Pattern is valid regex | `{pattern: "^[a-z]+$"}` | `{pattern: "["}` | InvalidSpecException |

---

## Request/Response Data Validation

### Type Validation

#### String Type

| Scenario | Schema | Valid Data | Invalid Data | Error Type |
|----------|--------|------------|--------------|------------|
| Basic string | `{type: "string"}` | `"hello"` | `123` | TypeMismatchException |
| String not number string | `{type: "string"}` | `"hello"` | `"123"` when number expected | N/A - this IS valid |
| Number not string number | `{type: "number"}` | `123` | `"123"` | TypeMismatchException |
| Empty string allowed | `{type: "string"}` | `""` | N/A | N/A |
| Empty string with minLength | `{type: "string", minLength: 1}` | `"a"` | `""` | BoundaryViolationException |

#### Number Type

| Scenario | Schema | Valid Data | Invalid Data | Error Type |
|----------|--------|------------|--------------|------------|
| Number (int or float) | `{type: "number"}` | `123` or `123.45` | `"123"` | TypeMismatchException |
| Integer only | `{type: "integer"}` | `123` | `123.45` | TypeMismatchException |
| Integer not float-int | `{type: "integer"}` | `123` | `123.0` | TypeMismatchException |

#### Boolean Type

| Scenario | Schema | Valid Data | Invalid Data | Error Type |
|----------|--------|------------|--------------|------------|
| Boolean true/false | `{type: "boolean"}` | `true`, `false` | `1`, `0`, `"true"`, `"false"` | TypeMismatchException |

#### Null Type

| Scenario | Schema | Valid Data | Invalid Data | Error Type |
|----------|--------|------------|--------------|------------|
| Null allowed | `{type: ["string", "null"]}` | `null` | - | - |
| Null not allowed | `{type: "string"}` | `"hello"` | `null` | TypeMismatchException |

#### Array Type

| Scenario | Schema | Valid Data | Invalid Data | Error Type |
|----------|--------|------------|--------------|------------|
| Array | `{type: "array"}` | `[]`, `[1, 2, 3]` | `"array"`, `{}` | TypeMismatchException |
| Array items | `{type: "array", items: {type: "string"}}` | `["a", "b"]` | `[1, 2]` | TypeMismatchException |

#### Object Type

| Scenario | Schema | Valid Data | Invalid Data | Error Type |
|----------|--------|------------|--------------|------------|
| Object | `{type: "object"}` | `{}`, `{"key": "value"}` | `[]`, `"object"` | TypeMismatchException |

### Required Fields

| Scenario | Schema | Valid Data | Invalid Data | Error Type |
|----------|--------|------------|--------------|------------|
| Required present non-null | `{required: ["name"], properties: {name: {type: "string"}}}` | `{name: "John"}` | `{}` | RequiredFieldMissingException |
| Required present but null (not nullable) | Same | `{name: "John"}` | `{name: null}` | TypeMismatchException |
| Required present but null (nullable) | `{required: ["name"], properties: {name: {type: ["string", "null"]}}}` | `{name: null}` | N/A | N/A |
| Optional missing | `{properties: {name: {type: "string"}}}` | `{}` | N/A | N/A |
| Optional present non-null | Same | `{name: "John"}` | N/A | N/A |
| Optional present null (not nullable) | Same | `{}` | `{name: null}` | TypeMismatchException |
| Optional present null (nullable) | `{properties: {name: {type: ["string", "null"]}}}` | `{name: null}` | N/A | N/A |

### Additional Properties

| Scenario | Schema | Valid Data | Invalid Data | Error Type |
|----------|--------|------------|--------------|------------|
| Additional allowed (default) | `{type: "object", properties: {name: {}}}` | `{name: "John", age: 25}` | N/A | N/A |
| Additional not allowed | `{type: "object", properties: {name: {}}, additionalProperties: false}` | `{name: "John"}` | `{name: "John", age: 25}` | AdditionalPropertyException |
| Additional matches schema | `{type: "object", additionalProperties: {type: "string"}}` | `{extra: "value"}` | `{extra: 123}` | TypeMismatchException |

### Format Validation

| Format | Valid Examples | Invalid Examples | Notes |
|--------|---------------|------------------|-------|
| `email` | `"user@example.com"` | `"not-an-email"`, `"user@"`, `"@example.com"` | RFC 5322 |
| `uuid` | `"550e8400-e29b-41d4-a716-446655440000"` | `"not-a-uuid"`, `"550e8400"` | RFC 4122 |
| `date` | `"2023-12-25"` | `"12/25/2023"`, `"2023-25-12"` | YYYY-MM-DD |
| `date-time` | `"2023-12-25T10:30:00Z"` | `"2023-12-25 10:30:00"` | RFC 3339 |
| `uri` | `"https://example.com"` | `"not a uri"`, `"ht!tp://bad"` | RFC 3986 |
| `uri-reference` | `"/path/to/resource"` | `"ht!tp://bad"` | RFC 3986 |
| `hostname` | `"example.com"` | `"exam ple.com"`, `"-example.com"` | RFC 1123 |
| `ipv4` | `"192.168.1.1"` | `"999.999.999.999"`, `"192.168.1"` | Dotted quad |
| `ipv6` | `"2001:0db8:85a3::8a2e:0370:7334"` | `"not-ipv6"` | RFC 4291 |
| `int32` | `-2147483648` to `2147483647` | `2147483648` | 32-bit signed |
| `int64` | Large integers | Values > 64-bit | 64-bit signed |
| `float` | `123.45` | N/A | Single precision |
| `double` | `123.456789012345` | N/A | Double precision |

### Enum Validation

| Scenario | Schema | Valid Data | Invalid Data | Error Type |
|----------|--------|------------|--------------|------------|
| Value in enum | `{enum: ["red", "green", "blue"]}` | `"red"` | `"yellow"` | EnumViolationException |
| Case sensitive | `{enum: ["Red", "Green"]}` | `"Red"` | `"red"` | EnumViolationException |
| Type matches | `{type: "string", enum: ["1", "2"]}` | `"1"` | `1` | TypeMismatchException |

### Boundary Validation (Numbers)

| Scenario | Schema | Valid Data | Invalid Data | Error Type |
|----------|--------|------------|--------------|------------|
| Minimum inclusive | `{minimum: 0}` | `0`, `1` | `-1` | BoundaryViolationException |
| Maximum inclusive | `{maximum: 100}` | `100`, `99` | `101` | BoundaryViolationException |
| Exclusive minimum | `{exclusiveMinimum: 0}` | `0.1`, `1` | `0`, `-1` | BoundaryViolationException |
| Exclusive maximum | `{exclusiveMaximum: 100}` | `99`, `99.9` | `100`, `101` | BoundaryViolationException |
| Multiple of | `{multipleOf: 5}` | `10`, `15` | `12`, `13` | BoundaryViolationException |

### Boundary Validation (Strings)

| Scenario | Schema | Valid Data | Invalid Data | Error Type |
|----------|--------|------------|--------------|------------|
| Min length | `{minLength: 3}` | `"abc"`, `"abcd"` | `""`, `"ab"` | BoundaryViolationException |
| Max length | `{maxLength: 5}` | `"abc"`, `"abcde"` | `"abcdef"` | BoundaryViolationException |

### Boundary Validation (Arrays)

| Scenario | Schema | Valid Data | Invalid Data | Error Type |
|----------|--------|------------|--------------|------------|
| Min items | `{minItems: 2}` | `[1, 2]`, `[1, 2, 3]` | `[]`, `[1]` | BoundaryViolationException |
| Max items | `{maxItems: 3}` | `[]`, `[1, 2, 3]` | `[1, 2, 3, 4]` | BoundaryViolationException |
| Unique items | `{uniqueItems: true}` | `[1, 2, 3]` | `[1, 2, 2]` | BoundaryViolationException |

### Pattern Validation

| Scenario | Schema | Valid Data | Invalid Data | Error Type |
|----------|--------|------------|--------------|------------|
| Pattern match | `{pattern: "^[A-Z]{3}$"}` | `"ABC"` | `"abc"`, `"AB"`, `"ABCD"` | PatternViolationException |
| Email pattern | `{pattern: "^[^@]+@[^@]+\\.[^@]+$"}` | `"a@b.c"` | `"not-email"` | PatternViolationException |

### Schema Composition - oneOf

| Scenario | Schema | Valid Data | Invalid Data | Error Type |
|----------|--------|------------|--------------|------------|
| Matches exactly one | `{oneOf: [{type: "string"}, {type: "number"}]}` | `"text"` or `123` | `true` (matches neither) | CompositionViolationException |
| Matches none | Same | N/A | `true`, `null`, `[]` | CompositionViolationException |
| Matches multiple | `{oneOf: [{type: "string"}, {type: "string", minLength: 5}]}` | `"hello"` matches both! | `"hello"` | CompositionViolationException |

### Schema Composition - anyOf

| Scenario | Schema | Valid Data | Invalid Data | Error Type |
|----------|--------|------------|--------------|------------|
| Matches at least one | `{anyOf: [{type: "string"}, {type: "number"}]}` | `"text"`, `123` | `true` | CompositionViolationException |
| Matches none | Same | N/A | `true`, `null` | CompositionViolationException |

### Schema Composition - allOf

| Scenario | Schema | Valid Data | Invalid Data | Error Type |
|----------|--------|------------|--------------|------------|
| Matches all schemas | `{allOf: [{type: "string"}, {minLength: 5}]}` | `"hello"` | `"hi"` | CompositionViolationException |
| Fails one schema | Same | N/A | `"hi"`, `123` | CompositionViolationException |

### Discriminator

| Scenario | Schema | Valid Data | Invalid Data | Error Type |
|----------|--------|------------|--------------|------------|
| Discriminator present | `{discriminator: {propertyName: "type"}, oneOf: [...]}` | `{type: "dog", ...}` | `{...}` (missing type) | DiscriminatorViolationException |
| Discriminator maps | With mapping `{dog: "#/Dog"}` | `{type: "dog"}` → Dog schema | `{type: "cat"}` (not in mapping) | DiscriminatorViolationException |

---

## Edge Cases

### The Four Combinations

Schema permutations of required + nullable:

| Required? | Nullable? | Missing | `null` | Non-null | Notes |
|-----------|-----------|---------|--------|----------|-------|
| ✓ | ✓ | ❌ FAIL | ✓ PASS | ✓ PASS | Must be present, can be null |
| ✓ | ✗ | ❌ FAIL | ❌ FAIL | ✓ PASS | Must be present, must be non-null |
| ✗ | ✓ | ✓ PASS | ✓ PASS | ✓ PASS | Can be omitted, can be null |
| ✗ | ✗ | ✓ PASS | ❌ FAIL | ✓ PASS | Can be omitted, if present must be non-null |

### Empty Values

| Type | Empty Value | With minLength/minItems | Without | Notes |
|------|-------------|-------------------------|---------|-------|
| String | `""` | FAIL if `minLength: 1` | PASS | Empty string is valid unless minLength specified |
| Array | `[]` | FAIL if `minItems: 1` | PASS | Empty array is valid unless minItems specified |
| Object | `{}` | FAIL if `minProperties: 1` | PASS | Empty object is valid unless minProperties specified |

### Boundary Edge Cases

| Scenario | Example | Should Pass? | Notes |
|----------|---------|--------------|-------|
| Value equals minimum | `{minimum: 0}` with `0` | ✓ PASS | Minimum is inclusive |
| Value equals maximum | `{maximum: 100}` with `100` | ✓ PASS | Maximum is inclusive |
| Value equals exclusiveMinimum | `{exclusiveMinimum: 0}` with `0` | ❌ FAIL | Must be strictly greater |
| Value equals exclusiveMaximum | `{exclusiveMaximum: 100}` with `100` | ❌ FAIL | Must be strictly less |
| String length equals minLength | `{minLength: 3}` with `"abc"` | ✓ PASS | MinLength is inclusive |
| String length equals maxLength | `{maxLength: 5}` with `"abcde"` | ✓ PASS | MaxLength is inclusive |

### Pattern Edge Cases

| Scenario | Pattern | Test String | Should Pass? | Notes |
|----------|---------|-------------|--------------|-------|
| Empty string with pattern | `^[a-z]+$` | `""` | ❌ FAIL | + requires at least one |
| Empty string with optional pattern | `^[a-z]*$` | `""` | ✓ PASS | * allows zero |
| Pattern doesn't anchor | `[a-z]+` | `"123abc456"` | ✓ PASS | Matches substring |
| Pattern anchored | `^[a-z]+$` | `"123abc456"` | ❌ FAIL | Must match entire string |

### Composition Edge Cases

#### allOf with additionalProperties

```json
{
  "allOf": [
    {"type": "object", "properties": {"name": {"type": "string"}}},
    {"type": "object", "properties": {"age": {"type": "number"}}, "additionalProperties": false}
  ]
}
```

**Issue**: `{name: "John", age: 25}` FAILS because second schema sees `name` as additional property!

**Solution**: Use `unevaluatedProperties: false` instead (OpenAPI 3.1).

#### oneOf without discriminator

```json
{
  "oneOf": [
    {"type": "object", "properties": {"id": {"type": "number"}}},
    {"type": "object", "properties": {"name": {"type": "string"}}}
  ]
}
```

**Issue**: `{id: 1, name: "John"}` might match BOTH due to default `additionalProperties: true`.

**Solution**: Use discriminator or `additionalProperties: false`.

---

## Common Issues - Hints to Provide

### snake_case vs camelCase

**Trigger**: Additional property with similar name to expected property.

```
Expected: userName
Received: user_name
Hint: This looks like a snake_case/camelCase confusion - did you mean "userName"?
```

### Type Confusion (String Numbers)

**Trigger**: String value where number expected, or vice versa, and values are equivalent.

```
Expected: integer
Received: "25"
Hint: This looks like a type confusion issue - received string "25" but spec requires integer 25
```

### Invalid But Close Format

**Trigger**: Format validation fails but value is "close" to valid.

```
Expected: email format (RFC 5322)
Received: "user@example"
Hint: Missing top-level domain - did you mean "user@example.com"?
```

### Missing Required Field

**Trigger**: Required field not present.

```
Expected: field "name" is required
Received: field is missing
Spec reference: openapi.yaml line 142
Hint: Check request body structure - required field "name" must be present
```

---

## Test Fixture Requirements

### Valid Specs (6+)

1. `minimal-valid.json` - Absolute minimum
2. `simple-crud.json` - Basic CRUD API
3. `strict-schemas.json` - All strict validation features
4. `composition-examples.json` - oneOf/anyOf/allOf/discriminator
5. `edge-cases.json` - Edge case scenarios
6. Existing: `tictactoe-3.1.0.yaml`, `ecommerce-3.1.0.json`, `todo-3.1.1.yaml`

### Invalid Data (50+)

Organized by category in `tests/Fixtures/InvalidData/`:
- `type-violations/` (10 files)
- `required-violations/` (5 files)
- `additional-properties/` (5 files)
- `format-violations/` (10 files)
- `enum-violations/` (5 files)
- `boundary-violations/` (10 files)
- `pattern-violations/` (5 files)
- `composition-violations/` (8 files)
- `discriminator-violations/` (3 files)
- `edge-cases/` (10 files)

---

## Error Message Format

### Standard Error Format

```
Validation failed with N errors:

[1] unexpected string at request.body.user.age, breaking openapi.yml line 142 expectations
    expected: integer
    received: "25"
    hint: this looks like a type confusion issue - received string "25" but spec requires integer 25

[2] unexpected property at request.body.user_name, breaking openapi.yml line 156 expectations
    expected: userName
    reason: additionalProperties not allowed
    hint: this looks like a snake_case/camelCase confusion - did you mean "userName"?

[3] invalid format at request.body.email, breaking openapi.yml line 89 expectations
    expected: email format (RFC 5322)
    received: "not-an-email"
```

### Error Context Fields

Each error must include:
- **path**: JSONPath to field (e.g., `request.body.user.age`)
- **specReference**: Line number in spec (e.g., `openapi.yml line 142`)
- **constraint**: What was violated (e.g., `type`, `required`, `format`)
- **expectedValue**: What spec expected
- **receivedValue**: What was provided
- **reason**: Why it failed
- **hint** (optional): Suggestion for common issues

---

## Success Metrics

**Test Coverage**:
- [ ] 155+ test cases
- [ ] All OpenAPI 3.1.0 validation scenarios covered
- [ ] All JSON Schema 2020-12 validation scenarios covered
- [ ] All edge cases documented and tested

**Test Quality**:
- [ ] Each test expects specific exception type
- [ ] Each test verifies error context (path, constraint, hint)
- [ ] Each test uses fixtures (no inline data)
- [ ] Data providers with descriptive keys

**Fixture Quality**:
- [ ] 6+ valid OpenAPI 3.1.0 specs
- [ ] 50+ invalid request/response JSONs
- [ ] All fixtures documented
- [ ] All fixtures follow naming convention
