# Strict OpenAPI Validator - Implementation Phase Plan

**READ THESE FIRST:**
- @CLAUDE/plan/tdd-implementation.md (planning phase - completed)
- @docs/phase-10-completion-summary.md (test suite overview)
- OpenAPI 3.1.0 Specification: https://spec.openapis.org/oas/v3.1.0.html
- JSON Schema 2020-12: https://json-schema.org/draft/2020-12/json-schema-core.html

## Progress

[✓] Phase 1: Spec Validation Implementation (COMPLETE - 31 tests pass)
[⏳] Phase 2: Schema Validation Core (IN PROGRESS)
[ ] Phase 3: Schema Validation Extended
[ ] Phase 4: Composition Validation
[ ] Phase 5: Request/Response Validation
[ ] Phase 6: Error Collection Service
[ ] Phase 7: Optimization

---

## Summary

Implement the actual validation logic to make all 185 tests pass. We're moving from the TDD **RED phase** (all tests failing) to the **GREEN phase** (tests passing).

**Starting Point**:
- ✅ 185 tests written and verified
- ✅ 69 fixtures created
- ✅ 25 exception classes implemented
- ✅ Public API with NOOP implementations

**Goal**: Make all tests pass by implementing strict OpenAPI 3.1.0 validation logic.

---

## Core Principles

1. **No Type Coercion**: "123" ≠ 123
2. **Collect All Errors**: Never fail-fast
3. **Strict Validation**: additionalProperties: false enforced
4. **LLM-Optimized Errors**: Clear messages with hints
5. **PHPStan Level Max**: Maintain strict typing

---

## Phase 1: Spec Validation Implementation

**Goal**: Implement Spec class validation to make SpecValidationTest pass (31 tests)

**Current State**: 10 tests pass (valid specs load), 21 tests fail (no validation)

### Tasks

#### 1.1: Spec Structure Validation
- [ ] Validate OpenAPI version (must be 3.1.0)
- [ ] Validate required root fields (openapi, info, paths)
- [ ] Validate info object structure (title, version)
- [ ] Throw InvalidSpecException for missing/invalid fields

#### 1.2: Path Validation
- [ ] Validate paths object exists
- [ ] Validate path format (must start with /)
- [ ] Validate operation objects (get, post, put, delete, etc.)
- [ ] Throw InvalidPathFormatException for invalid paths

#### 1.3: Operation Validation
- [ ] Validate operationId (if present)
- [ ] Validate responses object exists
- [ ] Validate requestBody structure
- [ ] Validate parameters array

#### 1.4: Schema References
- [ ] Validate components.schemas structure
- [ ] Validate $ref format
- [ ] Basic schema structure validation
- [ ] Throw InvalidSpecException for malformed schemas

### Expected Outcome

After Phase 1:
- All 31 SpecValidationTest tests should PASS
- Spec class validates OpenAPI structure on creation
- Clear error messages for invalid specs

### Files to Modify

- `src/Spec.php` - Add validation logic to constructors
- Create helper classes if needed for validation

---

## Phase 2: Schema Validation Core

**Goal**: Implement basic schema validation for type, required, additionalProperties, format

**Tests Affected**: RequestValidationTest, ResponseValidationTest

### Tasks

#### 2.1: Type Validation (No Coercion)
- [ ] Implement strict type checking (string, number, integer, boolean, array, object, null)
- [ ] Reject type mismatches (no coercion)
- [ ] Throw TypeMismatchException with clear message
- [ ] Tests: type-violations/ fixtures (10 tests)

#### 2.2: Required Field Validation
- [ ] Check all required fields present
- [ ] Handle nested required fields
- [ ] Throw RequiredFieldMissingException
- [ ] Tests: required-violations/ fixtures (8 tests)

#### 2.3: Additional Properties Validation
- [ ] Enforce additionalProperties: false
- [ ] Detect extra fields not in schema
- [ ] Throw AdditionalPropertyException
- [ ] Tests: additional-properties/ fixtures (6 tests)

#### 2.4: Format Validation
- [ ] Validate email format
- [ ] Validate uri format
- [ ] Validate uuid format
- [ ] Validate date-time format
- [ ] Throw FormatViolationException
- [ ] Tests: format-violations/ fixtures (8 tests)

### Expected Outcome

After Phase 2:
- 32 tests should pass (type, required, additionalProperties, format)
- Basic schema validation working
- Clear error messages with hints

---

## Phase 3: Schema Validation Extended

**Goal**: Implement boundary, pattern, enum, array validation

### Tasks

#### 3.1: Boundary Validation
- [ ] Validate minimum/maximum (inclusive)
- [ ] Validate exclusiveMinimum/exclusiveMaximum
- [ ] Validate minLength/maxLength
- [ ] Validate minItems/maxItems
- [ ] Validate minProperties/maxProperties
- [ ] Throw BoundaryViolationException
- [ ] Tests: boundary-violations/ fixtures (8 tests)

#### 3.2: Pattern Validation
- [ ] Validate regex patterns
- [ ] Handle invalid regex gracefully
- [ ] Throw PatternViolationException
- [ ] Tests: pattern-violations/ fixtures (4 tests)

#### 3.3: Enum Validation
- [ ] Validate value in enum
- [ ] Handle null in enum
- [ ] Throw EnumViolationException
- [ ] Tests: enum-violations/ fixtures (4 tests)

#### 3.4: Array Validation
- [ ] Validate items schema
- [ ] Validate uniqueItems
- [ ] Validate array constraints
- [ ] Throw ArrayViolationException

### Expected Outcome

After Phase 3:
- 16 additional tests pass (boundary, pattern, enum)
- Extended schema validation working

---

## Phase 4: Composition Validation

**Goal**: Implement oneOf, anyOf, allOf, discriminator

### Tasks

#### 4.1: oneOf Validation
- [ ] Validate exactly one schema matches
- [ ] Collect errors from all schemas
- [ ] Throw CompositionViolationException
- [ ] Tests: composition-violations/ oneOf fixtures

#### 4.2: anyOf Validation
- [ ] Validate at least one schema matches
- [ ] Collect errors from all schemas
- [ ] Throw CompositionViolationException
- [ ] Tests: composition-violations/ anyOf fixtures

#### 4.3: allOf Validation
- [ ] Validate all schemas match
- [ ] Merge schema constraints
- [ ] Throw CompositionViolationException
- [ ] Tests: composition-violations/ allOf fixtures

#### 4.4: Discriminator Validation
- [ ] Validate discriminator property exists
- [ ] Validate discriminator value matches mapping
- [ ] Throw DiscriminatorViolationException
- [ ] Tests: discriminator-violations/ fixtures (4 tests)

### Expected Outcome

After Phase 4:
- 10 additional tests pass (composition, discriminator)
- Complex schema validation working

---

## Phase 5: Request/Response Validation

**Goal**: Implement Validator methods for actual request/response validation

### Tasks

#### 5.1: JSON Parsing
- [ ] Parse JSON request/response
- [ ] Handle invalid JSON
- [ ] Throw InvalidRequestException/InvalidResponseException

#### 5.2: Schema Lookup
- [ ] Find operation in spec by path and method
- [ ] Lookup requestBody schema
- [ ] Lookup response schema by status code
- [ ] Handle missing operations

#### 5.3: Content-Type Matching
- [ ] Match content-type to media type in spec
- [ ] Default to application/json
- [ ] Handle missing content-type

#### 5.4: Parameter Validation
- [ ] Validate path parameters
- [ ] Validate query parameters
- [ ] Validate headers
- [ ] Throw InvalidRequestParametersException

#### 5.5: Symfony Request/Response
- [ ] Implement validate(Request) method
- [ ] Implement validateSymfonyResponse(Response) method
- [ ] Extract JSON from Symfony objects
- [ ] Extract path, method, status code

### Expected Outcome

After Phase 5:
- All RequestValidationTest tests pass (64 tests)
- All ResponseValidationTest tests pass (45 tests)
- Full request/response validation working

---

## Phase 6: Error Collection Service

**Goal**: Implement error collection, hints, formatting

### Tasks

#### 6.1: Error Collector
- [ ] Create ErrorCollector service
- [ ] Collect errors instead of throwing immediately
- [ ] Track error context (path, spec reference)
- [ ] Throw ValidationException with all errors at end

#### 6.2: Hint Generation
- [ ] Detect common mistakes (snake_case vs camelCase)
- [ ] Suggest corrections for type mismatches
- [ ] Provide helpful hints in errors
- [ ] Tests: ErrorCollectionTest hint tests

#### 6.3: Error Formatting
- [ ] Format multiple errors clearly
- [ ] Order errors logically (by path)
- [ ] Include spec references
- [ ] LLM-optimized output

#### 6.4: Performance
- [ ] Handle hundreds of errors efficiently
- [ ] Tests: ErrorCollectionTest performance tests

### Expected Outcome

After Phase 6:
- All ErrorCollectionTest tests pass (15 tests)
- Error collection working correctly
- Helpful hints generated

---

## Phase 7: Optimization

**Goal**: Performance tuning and caching

### Tasks

#### 7.1: Schema Caching
- [ ] Cache parsed schemas
- [ ] Cache resolved $refs
- [ ] Avoid re-parsing same schemas

#### 7.2: Performance Profiling
- [ ] Profile validation performance
- [ ] Identify bottlenecks
- [ ] Optimize hot paths

#### 7.3: Memory Optimization
- [ ] Minimize memory usage
- [ ] Handle large specs efficiently
- [ ] Stream validation if needed

### Expected Outcome

After Phase 7:
- All tests still pass
- Performance improved
- Memory usage optimized

---

## Phase 8: Edge Cases

**Goal**: Ensure all edge case tests pass

### Current State

EdgeCaseTest: 30 tests, 18 pass, 10 fail (NOOP), 2 skipped

### Tasks

#### 8.1: The Four Combinations
- [ ] Required + Non-Nullable - must be present and non-null
- [ ] Required + Nullable - must be present, can be null
- [ ] Optional + Non-Nullable - if present, must be non-null
- [ ] Optional + Nullable - if present, can be null
- [ ] Tests: EdgeCaseTest combination tests

#### 8.2: Boundary Edge Cases
- [ ] Inclusive vs exclusive boundaries
- [ ] Minimum === value (inclusive, should pass)
- [ ] Minimum < value (exclusive, should fail)
- [ ] Tests: EdgeCaseTest boundary tests

#### 8.3: Empty Value Edge Cases
- [ ] Empty string without constraints (should pass)
- [ ] Empty array without constraints (should pass)
- [ ] Empty object without constraints (should pass)
- [ ] Tests: EdgeCaseTest empty value tests

#### 8.4: Pattern Edge Cases
- [ ] Anchored patterns (^$)
- [ ] Unanchored patterns
- [ ] Special regex characters
- [ ] Tests: EdgeCaseTest pattern tests

### Expected Outcome

After Phase 8:
- All EdgeCaseTest tests pass (28 tests, 2 skipped)
- Edge cases handled correctly

---

## Success Criteria

### All Tests Pass

- [✓] SpecValidationTest: 31 tests (10 currently pass)
- [ ] RequestValidationTest: 64 tests (all currently fail)
- [ ] ResponseValidationTest: 45 tests (all currently fail)
- [ ] EdgeCaseTest: 30 tests (18 currently pass, 2 skipped)
- [ ] ErrorCollectionTest: 15 tests (all currently fail)
- [ ] **Total: 185 tests, all PASS**

### Quality Metrics

- [ ] 100% test coverage
- [ ] PHPStan level max (no errors)
- [ ] All tests pass
- [ ] Performance benchmarks met (< 100ms for typical validation)
- [ ] Documentation complete

### Code Quality

- [ ] No type coercion
- [ ] All errors collected
- [ ] Clear error messages with hints
- [ ] Strict typing throughout
- [ ] Proper separation of concerns

---

## Implementation Notes

### Architecture

Create service classes:
- `SchemaValidator` - Core schema validation
- `TypeValidator` - Type checking
- `FormatValidator` - Format validation
- `CompositionValidator` - oneOf/anyOf/allOf
- `ErrorCollector` - Error collection
- `PathResolver` - $ref resolution

### Testing Strategy

After each phase:
1. Run relevant test suite
2. Fix any failures
3. Run full test suite to prevent regressions
4. Run PHPStan to maintain strict typing
5. Commit working code

### Commands

```bash
# Run tests
cd vendor/lts/strict-openapi-validator
export CI=true && vendor/bin/phpunit --testdox

# Run specific test
export CI=true && vendor/bin/phpunit --filter=SpecValidationTest

# Run PHPStan
vendor/bin/phpstan analyse src tests --level=max

# Run all QA
./bin/qa
```

---

## Estimated Timeline

- Phase 1 (Spec Validation): 3-5 days
- Phase 2 (Schema Core): 5-7 days
- Phase 3 (Schema Extended): 3-5 days
- Phase 4 (Composition): 5-7 days
- Phase 5 (Request/Response): 3-5 days
- Phase 6 (Error Collection): 2-3 days
- Phase 7 (Optimization): 2-3 days
- Phase 8 (Edge Cases): 1-2 days

**Total: 24-37 days**

Note: These are estimates only, actual time may vary.

---

## Current Focus

**Starting with Phase 1: Spec Validation**

Making SpecValidationTest pass by implementing OpenAPI spec structure validation.
