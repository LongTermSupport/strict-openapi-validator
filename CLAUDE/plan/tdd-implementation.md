# Strict OpenAPI Validator - TDD Implementation Plan

**READ THESE FIRST:**
- This document (planning phase)
- OpenAPI 3.1.0 Specification: https://spec.openapis.org/oas/v3.1.0.html
- JSON Schema 2020-12: https://json-schema.org/draft/2020-12/json-schema-core.html

## Progress

[ ] Phase 1: Exception Hierarchy Design & Implementation
[ ] Phase 2: Public API Design (NOOP implementations)
[✓] Phase 3: Test Fixtures - Valid OpenAPI Specs (COMPLETE - 5 fixtures, 25.3KB)
[ ] Phase 4: Test Fixtures - Invalid Request/Response JSONs
[ ] Phase 5: Spec Validation Tests (all FAILING)
[ ] Phase 6: Request Validation Tests (all FAILING)
[ ] Phase 7: Response Validation Tests (all FAILING)
[ ] Phase 8: Edge Case Tests (all FAILING)
[ ] Phase 9: Error Collection Tests (all FAILING)
[ ] Phase 10: Test Suite Verification (all tests run, all fail)

---

## Summary

Build a comprehensive TDD test suite for a strict OpenAPI 3.1.0 validator. This is **PLANNING PHASE ONLY** - we will:

1. Design complete exception hierarchy
2. Design public API with NOOP implementations
3. Create comprehensive test fixtures (valid specs, invalid data)
4. Write exhaustive test suite covering all validation scenarios
5. Ensure ALL tests run but FAIL (no actual validation logic yet)

**Goal**: 100+ failing tests demonstrating every validation scenario from the OpenAPI 3.1.0 spec.

---

## Core Philosophy (from README)

- **Collect all errors**: Never fail-fast, gather every validation issue
- **Detect, don't fix**: Identify violations precisely, never auto-correct
- **Complete validation**: Nothing incomplete, nothing extra, nothing unexpected
- **Strict types**: No implicit coercion - `"123"` ≠ `123`
- **Helpful hints**: Provide guidance for common issues (snake_case vs camelCase, type confusion)

---

## Phase 1: Exception Hierarchy Design

### Base Exception Structure

```php
namespace LongTermSupport\StrictOpenApiValidator\Exception;

/**
 * Base exception for all validation failures.
 *
 * Collects multiple validation errors before throwing.
 */
abstract class ValidationException extends \Exception
{
    /**
     * @param ValidationError[] $errors
     */
    public function __construct(
        private readonly array $errors,
        string $message = '',
    ) {
        parent::__construct($message ?: $this->buildMessage());
    }

    /**
     * @return ValidationError[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    private function buildMessage(): string
    {
        // Build LLM-optimized error message with all errors
        // Format: "Validation failed with N errors:\n\n[1] ...\n[2] ..."
    }
}
```

### Error Value Object

```php
/**
 * Represents a single validation error with full context.
 */
final readonly class ValidationError
{
    public function __construct(
        public string $path,              // JSONPath to problematic field
        public string $specReference,     // Line number in OpenAPI spec
        public string $constraint,        // What constraint was violated
        public mixed $expectedValue,      // What was expected
        public mixed $receivedValue,      // What was received
        public string $reason,            // Why it failed
        public ?string $hint = null,      // Helpful hint for common issues
    ) {}
}
```

### Exception Hierarchy

```php
// Spec-level exceptions (spec file invalid)
class InvalidSpecException extends ValidationException {}
class MissingRequiredSpecFieldException extends InvalidSpecException {}
class InvalidSpecVersionException extends InvalidSpecException {}

// Request validation exceptions
class InvalidRequestException extends ValidationException {}
class InvalidRequestBodyException extends InvalidRequestException {}
class InvalidRequestParametersException extends InvalidRequestException {}
class InvalidRequestHeadersException extends InvalidRequestException {}
class InvalidRequestPathException extends InvalidRequestException {}

// Response validation exceptions
class InvalidResponseException extends ValidationException {}
class InvalidResponseBodyException extends InvalidResponseException {}
class InvalidResponseHeadersException extends InvalidResponseException {}
class InvalidResponseStatusException extends InvalidResponseException {}

// Schema validation exceptions (detailed)
class SchemaViolationException extends ValidationException {}
class TypeMismatchException extends SchemaViolationException {}
class FormatViolationException extends SchemaViolationException {}
class RequiredFieldMissingException extends SchemaViolationException {}
class AdditionalPropertyException extends SchemaViolationException {}
class EnumViolationException extends SchemaViolationException {}
class BoundaryViolationException extends SchemaViolationException {}
class PatternViolationException extends SchemaViolationException {}
class CompositionViolationException extends SchemaViolationException {} // oneOf/anyOf/allOf
class DiscriminatorViolationException extends SchemaViolationException {}
```

---

## Phase 2: Public API Design (NOOP Implementation)

### Spec Class

```php
namespace LongTermSupport\StrictOpenApiValidator;

use LongTermSupport\StrictOpenApiValidator\Exception\InvalidSpecException;

/**
 * Represents a validated OpenAPI specification.
 *
 * Validates spec on creation, stores parsed structure.
 */
final readonly class Spec
{
    private function __construct(
        private array $spec,
        private string $sourceFile,
    ) {}

    /**
     * Load and validate OpenAPI spec from file.
     *
     * @throws InvalidSpecException When spec is invalid
     */
    public static function createFromFile(string $path): self
    {
        // NOOP for now - just load file and return
        // TODO: Implement full spec validation

        if (!file_exists($path)) {
            throw new \InvalidArgumentException("Spec file not found: {$path}");
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $content = \Safe\file_get_contents($path);

        if ($extension === 'json') {
            $spec = \Safe\json_decode($content, true);
        } elseif (in_array($extension, ['yaml', 'yml'])) {
            // TODO: YAML parsing (requires symfony/yaml)
            throw new \LogicException('YAML parsing not yet implemented');
        } else {
            throw new \InvalidArgumentException("Unsupported file extension: {$extension}");
        }

        return new self($spec, $path);
    }

    /**
     * Load and validate OpenAPI spec from array.
     *
     * @throws InvalidSpecException When spec is invalid
     */
    public static function createFromArray(array $spec): self
    {
        // NOOP for now
        // TODO: Implement full spec validation
        return new self($spec, '<array>');
    }

    /**
     * Get the raw spec array.
     */
    public function getSpec(): array
    {
        return $this->spec;
    }

    /**
     * Get the source file path.
     */
    public function getSourceFile(): string
    {
        return $this->sourceFile;
    }
}
```

### Validator Class

```php
namespace LongTermSupport\StrictOpenApiValidator;

use LongTermSupport\StrictOpenApiValidator\Exception\InvalidRequestException;
use LongTermSupport\StrictOpenApiValidator\Exception\InvalidResponseException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Strict OpenAPI validator.
 *
 * Validates requests and responses against OpenAPI specification.
 */
final class Validator
{
    /**
     * Validate JSON request string against spec.
     *
     * @throws InvalidRequestException When validation fails
     */
    public static function validateRequest(string $json, Spec $spec): void
    {
        // NOOP for now - all tests will fail
        // TODO: Implement request validation
    }

    /**
     * Validate JSON response string against spec.
     *
     * @throws InvalidResponseException When validation fails
     */
    public static function validateResponse(string $json, Spec $spec): void
    {
        // NOOP for now - all tests will fail
        // TODO: Implement response validation
    }

    /**
     * Validate Symfony Request object against spec.
     *
     * @throws InvalidRequestException When validation fails
     */
    public static function validate(Request $request, Spec $spec): void
    {
        // NOOP for now
        // TODO: Extract JSON from request, validate
    }

    /**
     * Validate Symfony Response object against spec.
     *
     * @throws InvalidResponseException When validation fails
     */
    public static function validateSymfonyResponse(Response $response, Spec $spec): void
    {
        // NOOP for now
        // TODO: Extract JSON from response, validate
    }
}
```

---

## Phase 3: Test Fixtures - Valid OpenAPI Specs

### Location
`tests/Fixtures/Specs/`

### Fixtures to Create

1. **`minimal-valid.json`** - Absolute minimum valid OpenAPI 3.1.0 spec
   ```json
   {
     "openapi": "3.1.0",
     "info": {
       "title": "Minimal API",
       "version": "1.0.0"
     },
     "paths": {}
   }
   ```

2. **`simple-crud.json`** - Simple CRUD API with basic types
   - User resource (id, name, email)
   - GET /users, GET /users/{id}, POST /users, PUT /users/{id}, DELETE /users/{id}
   - Required fields, type validation, format validation

3. **`strict-schemas.json`** - Demonstrates strict validation requirements
   - `additionalProperties: false` on all schemas
   - Required fields
   - Strict types (no nullable unless explicit)
   - Format constraints (email, uuid, date-time)

4. **`composition-examples.json`** - oneOf/anyOf/allOf examples
   - Discriminator usage
   - oneOf for polymorphic types
   - anyOf for optional combinations
   - allOf for composition

5. **`edge-cases.json`** - Edge case scenarios
   - Nullable vs optional fields
   - Empty arrays/objects
   - Boundary values (min/max)
   - Pattern constraints

6. **Use existing fixtures** from agent research:
   - `tictactoe-3.1.0.yaml` (security schemes, $ref)
   - `ecommerce-3.1.0.json` (complex schemas)
   - `todo-3.1.1.yaml` (CRUD operations)
   - `redocly-example-3.1.0.yaml` (vendor extensions)

---

## Phase 4: Test Fixtures - Invalid Request/Response JSONs

### Location
`tests/Fixtures/InvalidData/`

### Categories

#### Type Violations
- `type-string-expected-number.json` - `{"age": "25"}` vs `{"age": 25}`
- `type-number-expected-string.json` - `{"name": 123}` vs `{"name": "John"}`
- `type-null-not-nullable.json` - `{"name": null}` when not nullable
- `type-array-expected-object.json`
- `type-object-expected-array.json`

#### Required Field Violations
- `required-field-missing.json` - Missing required field entirely
- `required-field-null.json` - Required field present but null
- `required-field-empty-string.json` - Required field present but empty string
- `required-multiple-missing.json` - Multiple required fields missing

#### Additional Properties
- `additional-property-not-allowed.json` - Extra field when `additionalProperties: false`
- `additional-property-snake-case.json` - `user_name` instead of `userName`
- `additional-nested-property.json` - Extra nested property

#### Format Violations
- `format-email-invalid.json` - Invalid email format
- `format-uuid-invalid.json` - Invalid UUID format
- `format-date-time-invalid.json` - Invalid date-time format
- `format-uri-invalid.json` - Invalid URI format
- `format-date-invalid.json` - Invalid date format

#### Enum Violations
- `enum-invalid-value.json` - Value not in enum
- `enum-case-mismatch.json` - Correct value, wrong case
- `enum-type-mismatch.json` - String enum with number value

#### Boundary Violations
- `boundary-minimum-violated.json` - Value below minimum
- `boundary-maximum-violated.json` - Value above maximum
- `boundary-exclusive-minimum.json` - Value equals exclusiveMinimum
- `boundary-min-length-violated.json` - String too short
- `boundary-max-length-violated.json` - String too long
- `boundary-min-items-violated.json` - Array too small
- `boundary-max-items-violated.json` - Array too large

#### Pattern Violations
- `pattern-no-match.json` - String doesn't match pattern
- `pattern-phone-invalid.json` - Invalid phone number format

#### Composition Violations
- `oneof-matches-none.json` - Doesn't match any oneOf schema
- `oneof-matches-multiple.json` - Matches multiple oneOf schemas
- `anyof-matches-none.json` - Doesn't match any anyOf schema
- `allof-fails-one.json` - Fails one schema in allOf

#### Discriminator Violations
- `discriminator-missing.json` - Discriminator property missing
- `discriminator-invalid-value.json` - Discriminator value doesn't map to schema

---

## Phase 5: Spec Validation Tests

### Test Class: `SpecValidationTest`

**Goal**: Ensure `Spec::createFromFile()` and `Spec::createFromArray()` properly validate OpenAPI specs.

#### Test Cases

1. **Valid Specs (should PASS - but NOOP will fail)**
   - `itAcceptsMinimalValidSpec()`
   - `itAcceptsCompleteValidSpec()`
   - `itAcceptsYamlFormat()`
   - `itAcceptsJsonFormat()`

2. **Missing Required Fields (should throw InvalidSpecException)**
   - `itRejectsMissingOpenApiVersion()`
   - `itRejectsMissingInfoObject()`
   - `itRejectsMissingInfoTitle()`
   - `itRejectsMissingInfoVersion()`
   - `itRejectsEmptySpec()` (no paths, components, or webhooks)

3. **Invalid OpenAPI Version**
   - `itRejectsOpenApi20()`
   - `itRejectsOpenApi30()` (we only support 3.1)
   - `itRejectsInvalidVersionFormat()`

4. **Invalid Structure**
   - `itRejectsInvalidPathFormat()` (paths not starting with /)
   - `itRejectsDuplicateOperationIds()`
   - `itRejectsPathParameterMismatch()` (template param without definition)
   - `itRejectsInvalidHttpMethod()`

5. **Schema Validation**
   - `itRejectsInvalidSchemaType()`
   - `itRejectsInvalidFormat()`
   - `itRejectsConflictingConstraints()` (min > max)

**Data Provider Pattern**:
```php
/**
 * @return Iterator<string, array{spec: array, shouldPass: bool, expectedError?: class-string}>
 */
public static function provideSpecValidationCases(): Iterator
{
    yield 'minimal valid spec' => [
        'spec' => ['openapi' => '3.1.0', 'info' => ['title' => 'API', 'version' => '1.0.0'], 'paths' => []],
        'shouldPass' => true,
    ];

    yield 'missing openapi version' => [
        'spec' => ['info' => ['title' => 'API', 'version' => '1.0.0'], 'paths' => []],
        'shouldPass' => false,
        'expectedError' => MissingRequiredSpecFieldException::class,
    ];

    // ... 50+ more cases
}
```

---

## Phase 6: Request Validation Tests

### Test Class: `RequestValidationTest`

**Goal**: Test `Validator::validateRequest()` against all request validation scenarios.

#### Test Categories

1. **Body Validation**
   - Type mismatches (25+ cases)
   - Required fields (10+ cases)
   - Additional properties (10+ cases)
   - Format violations (15+ cases)
   - Enum violations (5+ cases)
   - Boundary violations (10+ cases)
   - Pattern violations (5+ cases)

2. **Parameter Validation**
   - Query parameters
   - Path parameters
   - Header parameters
   - Cookie parameters

3. **Content Type Validation**
   - Missing content-type
   - Invalid content-type
   - Content-type mismatch with spec

4. **Error Collection**
   - `itCollectsMultipleErrors()` - Request with 5+ validation errors
   - `itProvidesDetailedErrorContext()` - Check error message format
   - `itIncludesHelpfulHints()` - Check snake_case vs camelCase hint

#### Example Test Structure

```php
#[Test]
public function itRejectsTypeViolationStringExpectedNumber(): void
{
    $spec = Spec::createFromFile(__DIR__ . '/Fixtures/Specs/simple-crud.json');
    $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/type-string-expected-number.json');

    $this->expectException(InvalidRequestException::class);
    $this->expectExceptionMessage('type mismatch at request.body.age');

    Validator::validateRequest($json, $spec);
}

#[Test]
public function itCollectsAllErrorsInSingleException(): void
{
    $spec = Spec::createFromFile(__DIR__ . '/Fixtures/Specs/simple-crud.json');

    // JSON with 5 validation errors:
    // 1. Missing required field "name"
    // 2. Type mismatch "age" (string instead of number)
    // 3. Invalid email format
    // 4. Additional property "user_name"
    // 5. Enum violation for "status"
    $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/multiple-errors.json');

    try {
        Validator::validateRequest($json, $spec);
        self::fail('Expected InvalidRequestException');
    } catch (InvalidRequestException $e) {
        $errors = $e->getErrors();
        self::assertCount(5, $errors);

        // Verify each error has proper context
        self::assertSame('request.body.name', $errors[0]->path);
        self::assertSame('required', $errors[0]->constraint);

        self::assertSame('request.body.age', $errors[1]->path);
        self::assertSame('type', $errors[1]->constraint);
        self::assertStringContainsString('type confusion', $errors[1]->hint);

        self::assertSame('request.body.user_name', $errors[3]->path);
        self::assertStringContainsString('snake_case/camelCase', $errors[3]->hint);
    }
}
```

---

## Phase 7: Response Validation Tests

### Test Class: `ResponseValidationTest`

Similar structure to RequestValidationTest, but for responses.

#### Test Categories

1. **Status Code Validation**
   - Undocumented status code
   - Status code format
   - Default responses

2. **Body Validation** (same as request)
3. **Header Validation**
4. **Content Type Validation**

---

## Phase 8: Edge Case Tests

### Test Class: `EdgeCaseTest`

Special test cases for subtle validation issues.

#### Test Cases

1. **Null vs Missing vs Empty**
   - `itDistinguishesMissingFromNull()`
   - `itDistinguishesNullFromEmptyString()`
   - `itHandlesOptionalNullableField()`
   - `itHandlesRequiredNullableField()`
   - `itHandlesRequiredNonNullableField()`
   - `itHandlesOptionalNonNullableField()`

2. **Schema Composition Edge Cases**
   - `itHandlesAllOfWithAdditionalPropertiesFalse()`
   - `itHandlesOneOfWithoutDiscriminator()`
   - `itHandlesNestedComposition()`

3. **Numeric Precision**
   - `itHandlesFloatPrecision()`
   - `itHandlesIntegerBoundaries()`
   - `itHandlesExclusiveMinimum()`
   - `itHandlesExclusiveMaximum()`

4. **String Edge Cases**
   - `itHandlesEmptyStringWithMinLength()`
   - `itHandlesUnicodeInPattern()`
   - `itHandlesCaseSensitiveEnums()`

5. **Array Edge Cases**
   - `itHandlesEmptyArrayWithMinItems()`
   - `itHandlesUniqueItemsValidation()`
   - `itHandlesNestedArrays()`

---

## Phase 9: Error Collection Tests

### Test Class: `ErrorCollectionTest`

Test the error collection and reporting mechanism.

#### Test Cases

1. **Error Message Format**
   - `itFormatsErrorWithAllContextFields()`
   - `itIncludesJSONPathInError()`
   - `itIncludesSpecLineNumber()`
   - `itIncludesExpectedVsReceived()`

2. **Hint Generation**
   - `itSuggestsSnakeCaseToCamelCaseConversion()`
   - `itSuggestsTypeConversion()`
   - `itSuggestsClosestEnumValue()`

3. **Error Order**
   - `itOrdersErrorsByPath()`
   - `itGroupsRelatedErrors()`

4. **Error Count**
   - `itCollectsAllErrors()` (verify no early exit)
   - `itHandlesHundredsOfErrors()` (performance test)

---

## Phase 10: Test Suite Verification

### Final Checklist

**Test Execution**:
- [ ] All test files created
- [ ] All tests run without fatal errors
- [ ] All tests currently FAIL (because NOOP implementations)
- [ ] Each test has clear expectation of what should pass/fail
- [ ] Data providers used extensively

**Coverage**:
- [ ] Spec validation: 20+ test cases
- [ ] Request validation: 50+ test cases
- [ ] Response validation: 40+ test cases
- [ ] Edge cases: 30+ test cases
- [ ] Error collection: 15+ test cases
- [ ] **Total: 155+ test cases**

**Test Quality**:
- [ ] Each test has clear docblock
- [ ] Each test uses fixtures (not inline data)
- [ ] Each test expects specific exception type
- [ ] Each test verifies error context (path, constraint, hint)
- [ ] Data providers with descriptive keys

**Fixtures**:
- [ ] 6+ valid OpenAPI specs
- [ ] 50+ invalid request/response JSONs
- [ ] All fixtures documented
- [ ] All fixtures follow naming convention

---

## Expected Test Output

When running the test suite after Phase 10:

```
PHPUnit 11.x.x by Sebastian Bergmann and contributors.

Runtime:       PHP 8.4.x
Configuration: phpunit.xml

FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF  55 / 155 ( 35%)
FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF 110 / 155 ( 70%)
FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF           155 / 155 (100%)

Time: 00:02.123, Memory: 18.00 MB

There were 155 failures:

1) LongTermSupport\StrictOpenApiValidator\Tests\SpecValidationTest::itAcceptsMinimalValidSpec
Expected no exception, but validation not implemented (NOOP)

2) LongTermSupport\StrictOpenApiValidator\Tests\SpecValidationTest::itRejectsMissingOpenApiVersion
Expected InvalidSpecException, but validation not implemented (NOOP)

...

FAILURES!
Tests: 155, Assertions: 155, Failures: 155.
```

---

## Implementation Priorities After Test Suite

Once test suite is complete, implement in this order:

### Priority 1: Foundation
1. Exception hierarchy (fully implement all exception classes)
2. ValidationError value object
3. Error message formatting

### Priority 2: Spec Validation
1. Basic structure validation (openapi, info, paths)
2. Path validation (format, templates)
3. Operation validation (methods, operationIds)

### Priority 3: Schema Validation Core
1. Type validation (no coercion)
2. Required field validation
3. Additional properties validation
4. Format validation

### Priority 4: Schema Validation Extended
1. Boundary validation (min/max)
2. Pattern validation
3. Enum validation
4. Array validation

### Priority 5: Composition
1. oneOf/anyOf/allOf
2. Discriminator
3. $ref resolution

### Priority 6: Request/Response Validation
1. JSON parsing
2. Schema lookup
3. Content-type matching
4. Parameter validation

### Priority 7: Error Collection
1. Error collector service
2. Hint generation
3. Error formatting

### Priority 8: Optimization
1. Schema caching
2. Performance profiling
3. Memory optimization

---

## Development Tools

### PHPUnit Configuration

Create `phpunit.xml`:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         cacheDirectory=".phpunit.cache"
         colors="true">
    <testsuites>
        <testsuite name="Strict OpenAPI Validator Test Suite">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
</phpunit>
```

### Test Bootstrap

Create `tests/bootstrap.php`:
```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
```

---

## Success Criteria

**Planning Phase Complete When**:
1. ✅ All test classes defined
2. ✅ All test methods defined with clear expectations
3. ✅ All test fixtures created
4. ✅ Public API defined with NOOP implementations
5. ✅ Exception hierarchy fully designed
6. ✅ Test suite runs (155+ tests, all failing)
7. ✅ Each failing test has clear reason why it should pass
8. ✅ Documentation complete

**Ready for Implementation When**:
- All 155+ tests fail with predictable behavior
- Each test expects specific exception type
- Each test has proper fixtures
- No fatal errors or missing classes
- All tests have assertions about error context

---

## Notes

- This is a **planning document** - no implementation yet
- Tests should be written with expectation they will fail
- NOOP implementations ensure tests can run
- Focus on comprehensive coverage, not implementation
- LLM-optimized error messages are key differentiator
- Strict validation means zero tolerance for deviations

---

## Next Steps

After plan approval:
1. Create exception hierarchy
2. Create public API (NOOP)
3. Create test fixtures
4. Write test suite (expect all to fail)
5. Verify all tests run and fail predictably
6. Begin implementation (Phase 1: Foundation)
