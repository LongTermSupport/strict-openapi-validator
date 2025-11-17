# Strict OpenAPI Validator - Planning Documentation

## Overview

This directory contains comprehensive planning documentation for building a strict OpenAPI 3.1.0 validator using Test-Driven Development (TDD).

## Plan Status

**Current Phase**: Planning Complete ✓
**Next Phase**: Implementation (Execute Plan)

## Documents

### 1. [tdd-implementation.md](tdd-implementation.md)
**Primary planning document** - Comprehensive TDD implementation plan with:
- 10 implementation phases
- Public API design (NOOP implementations)
- Exception hierarchy design
- Test fixture specifications
- 155+ test case definitions
- Success criteria

**Read this first** for complete implementation roadmap.

### 2. [validation-scenarios.md](validation-scenarios.md)
**Validation reference** - Exhaustive catalog of validation scenarios:
- OpenAPI 3.1.0 spec validation rules
- Request/response data validation rules
- Type validation with examples
- Required field permutations
- Format validation requirements
- Edge cases and corner cases
- Error message format specifications

**Use this as reference** when writing tests and implementing validation logic.

## Key Principles

1. **TDD Approach**: Write failing tests first, implement later
2. **Comprehensive Coverage**: 155+ test cases covering all scenarios
3. **NOOP API**: Public API with empty implementations so tests can run
4. **Collect All Errors**: Never fail-fast, gather complete error context
5. **Strict Validation**: Zero tolerance for deviations from spec
6. **LLM-Optimized Errors**: Clear, helpful error messages with hints

## Implementation Phases

### Phase 1-2: Foundation (1-2 days)
- Exception hierarchy implementation
- Public API with NOOP implementations
- Basic project structure

### Phase 3-4: Test Fixtures (1 day)
- Create valid OpenAPI 3.1.0 specs
- Create invalid request/response JSONs
- Document all fixtures

### Phase 5-9: Test Suite (2-3 days)
- Write 155+ failing tests
- Organize tests by category
- Use data providers extensively
- Verify all tests run and fail predictably

### Phase 10: Verification (1 day)
- Run full test suite
- Verify all tests fail with clear expectations
- Document test output
- Ready for implementation

**Estimated Planning Phase**: 5-7 days

## After Planning: Implementation Priorities

Once test suite is complete (all tests failing), implement in this order:

1. **Foundation** (3-5 days)
   - Exception hierarchy
   - ValidationError value object
   - Error message formatting

2. **Spec Validation** (3-5 days)
   - Document structure validation
   - Path validation
   - Operation validation

3. **Schema Validation Core** (5-7 days)
   - Type validation (no coercion)
   - Required field validation
   - Additional properties validation
   - Format validation

4. **Schema Validation Extended** (3-5 days)
   - Boundary validation
   - Pattern validation
   - Enum validation
   - Array validation

5. **Composition** (5-7 days)
   - oneOf/anyOf/allOf
   - Discriminator
   - $ref resolution

6. **Request/Response Validation** (3-5 days)
   - JSON parsing
   - Schema lookup
   - Content-type matching
   - Parameter validation

7. **Error Collection** (2-3 days)
   - Error collector service
   - Hint generation
   - Error formatting

8. **Optimization** (2-3 days)
   - Schema caching
   - Performance profiling
   - Memory optimization

**Estimated Implementation**: 26-40 days

**Total Project Estimate**: 31-47 days

## Test Suite Structure

```
tests/
├── SpecValidationTest.php           # Spec validation (20+ tests)
├── RequestValidationTest.php        # Request validation (50+ tests)
├── ResponseValidationTest.php       # Response validation (40+ tests)
├── EdgeCaseTest.php                 # Edge cases (30+ tests)
├── ErrorCollectionTest.php          # Error handling (15+ tests)
└── Fixtures/
    ├── Specs/                       # Valid OpenAPI specs (6+)
    │   ├── minimal-valid.json
    │   ├── simple-crud.json
    │   ├── strict-schemas.json
    │   ├── composition-examples.json
    │   ├── edge-cases.json
    │   └── [existing fixtures]
    └── InvalidData/                 # Invalid JSONs (50+)
        ├── type-violations/
        ├── required-violations/
        ├── additional-properties/
        ├── format-violations/
        ├── enum-violations/
        ├── boundary-violations/
        ├── pattern-violations/
        ├── composition-violations/
        ├── discriminator-violations/
        └── edge-cases/
```

## Success Criteria

### Planning Phase Complete When:
- [x] Complete TDD plan documented
- [x] All validation scenarios cataloged
- [x] Public API designed
- [x] Exception hierarchy designed
- [x] Test structure defined
- [x] Fixture requirements specified
- [ ] Exception hierarchy implemented (NOOP)
- [ ] Public API implemented (NOOP)
- [ ] All fixtures created
- [ ] All 155+ tests written (failing)
- [ ] Test suite runs successfully (all fail)

### Ready for Implementation When:
- [ ] All tests run without fatal errors
- [ ] All tests fail predictably
- [ ] Each test expects specific exception
- [ ] Each test uses proper fixtures
- [ ] All fixtures documented

### Implementation Complete When:
- [ ] All 155+ tests pass
- [ ] 100% code coverage
- [ ] PHPStan level max
- [ ] Documentation complete
- [ ] Performance benchmarks met

## Resources

### OpenAPI 3.1.0 References
- [OpenAPI Specification 3.1.0](https://spec.openapis.org/oas/v3.1.0.html)
- [JSON Schema 2020-12 Core](https://json-schema.org/draft/2020-12/json-schema-core.html)
- [JSON Schema 2020-12 Validation](https://json-schema.org/draft/2020-12/json-schema-validation.html)

### Learning Resources
- [OpenAPI 3.1 and JSON Schema](https://apisyouwonthate.com/blog/openapi-v3-1-and-json-schema/)
- [Upgrading from 3.0 to 3.1](https://learn.openapis.org/upgrading/v3.0-to-v3.1.html)
- [How to use oneOf and anyOf](https://redocly.com/learn/openapi/any-of-one-of)

### Example Validators
- [league/openapi-psr7-validator](https://github.com/thephpleague/openapi-psr7-validator)
- [opis/json-schema](https://github.com/opis/json-schema)

## Development Commands

```bash
# Install dependencies
cd vendor/lts/strict-openapi-validator
composer install

# Run tests (will fail - that's expected!)
./bin/qa -t unit

# Run PHPStan
./bin/qa -t allStatic

# Run code style fixer
./bin/qa -t allCS
```

## Questions/Issues

For questions about the plan or implementation:
1. Review relevant planning document
2. Check validation-scenarios.md for specific cases
3. Consult OpenAPI 3.1.0 specification
4. Ask for clarification

## Next Steps

1. **Review plan** - Read tdd-implementation.md thoroughly
2. **Set up project** - Create directory structure
3. **Implement exceptions** - Build exception hierarchy
4. **Implement API** - Create NOOP public API
5. **Create fixtures** - Build test data
6. **Write tests** - All 155+ test cases
7. **Verify** - Ensure all tests run and fail
8. **Begin implementation** - Make tests pass one by one

## Notes

- This is a **planning phase** - no real implementation yet
- Focus is on comprehensive test coverage
- NOOP implementations ensure tests can run
- All tests should fail predictably
- Ready for implementation when all tests written and failing
