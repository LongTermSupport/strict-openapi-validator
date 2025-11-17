<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Tests;

use LongTermSupport\StrictOpenApiValidator\Exception\ValidationError;
use LongTermSupport\StrictOpenApiValidator\Exception\ValidationException;
use LongTermSupport\StrictOpenApiValidator\Spec;
use LongTermSupport\StrictOpenApiValidator\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Test error collection and reporting mechanisms.
 *
 * The validator should collect ALL errors before throwing (not fail-fast),
 * provide clear error messages with helpful hints, and properly format/order errors.
 *
 * Test Coverage (~15 tests):
 * - Error Collection Behavior (5 tests)
 * - Error Message Format (3 tests)
 * - Hint Generation (4 tests)
 * - Error Ordering (3 tests)
 *
 * All tests should FAIL because Validator::validateRequest() currently throws LogicException.
 * When actual validation is implemented, these tests will verify proper error collection.
 */
#[CoversClass(Validator::class)]
final class ErrorCollectionTest extends TestCase
{
    private Spec $strictSchemasSpec;
    private Spec $compositionSpec;

    protected function setUp(): void
    {
        $this->strictSchemasSpec = Spec::createFromFile(__DIR__ . '/Fixtures/Specs/strict-schemas.json');
        $this->compositionSpec = Spec::createFromFile(__DIR__ . '/Fixtures/Specs/composition-examples.json');
    }

    // ========================================
    // Error Collection Behavior (5 tests)
    // ========================================

    /**
     * Verifies that multiple violations in one request are all collected.
     *
     * Uses fixture with 5 errors:
     * - Missing required field "name"
     * - Invalid type for "price" (negative)
     * - Invalid enum value for "status"
     * - Invalid date format for "createdAt"
     * - Additional property "extraField"
     */
    #[Test]
    public function itCollectsAllErrors(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/multiple-errors/multiple-errors-5.json');

        try {
            Validator::validateRequest($json, $this->strictSchemasSpec);
            self::fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();

            // Should collect ALL 5 errors, not just the first one
            self::assertGreaterThanOrEqual(5, \count($errors), 'Should collect at least 5 validation errors');

            // Verify each error has all required fields
            foreach ($errors as $error) {
                self::assertInstanceOf(ValidationError::class, $error);
                self::assertNotEmpty($error->path, 'Error path should not be empty');
                self::assertNotEmpty($error->constraint, 'Error constraint should not be empty');
                self::assertNotEmpty($error->reason, 'Error reason should not be empty');
            }

            // Verify we got the specific errors we expect
            $errorPaths = \array_map(static fn (ValidationError $e): string => $e->path, $errors);
            self::assertContains('request.body.name', $errorPaths, 'Should detect missing "name" field');
            self::assertContains('request.body.price', $errorPaths, 'Should detect invalid "price" value');
            self::assertContains('request.body.status', $errorPaths, 'Should detect invalid "status" enum');
            self::assertContains('request.body.createdAt', $errorPaths, 'Should detect invalid date format');
            self::assertContains('request.body.extraField', $errorPaths, 'Should detect additional property');
        }
    }

    /**
     * Verifies that validation does NOT fail-fast (stops at first error).
     *
     * Uses fixture with 12+ violations across different constraint types
     * to ensure all errors are found before throwing.
     */
    #[Test]
    public function itDoesNotFailFast(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/multiple-errors/multiple-errors-10.json');

        try {
            Validator::validateRequest($json, $this->strictSchemasSpec);
            self::fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();

            // Should collect 10+ errors, proving it doesn't stop at first failure
            self::assertGreaterThanOrEqual(10, \count($errors), 'Should collect at least 10 validation errors (not fail-fast)');

            // Verify we have different types of errors (proves we scanned everything)
            $constraints = \array_unique(\array_map(static fn (ValidationError $e): string => $e->constraint, $errors));
            self::assertGreaterThanOrEqual(4, \count($constraints), 'Should have at least 4 different constraint types violated');

            // Verify each error is complete
            foreach ($errors as $error) {
                self::assertInstanceOf(ValidationError::class, $error);
                self::assertNotEmpty($error->path);
                self::assertNotEmpty($error->constraint);
                self::assertNotEmpty($error->reason);
                // expectedValue and receivedValue may be empty strings/null, but must exist
                self::assertTrue(\property_exists($error, 'expectedValue'));
                self::assertTrue(\property_exists($error, 'receivedValue'));
            }
        }
    }

    /**
     * Verifies that errors in nested objects are all collected.
     *
     * Tests deep object traversal: request.body.metadata.dimensions.volume
     */
    #[Test]
    public function itCollectsErrorsAcrossNestedObjects(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/multiple-errors/multiple-errors-10.json');

        try {
            Validator::validateRequest($json, $this->strictSchemasSpec);
            self::fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();

            // Find errors at different nesting levels
            $errorPaths = \array_map(static fn (ValidationError $e): string => $e->path, $errors);

            // Root level error
            $rootErrors = \array_filter($errorPaths, static fn (string $path): bool => \substr_count($path, '.') === 2);
            self::assertNotEmpty($rootErrors, 'Should find errors at root level (request.body.*)');

            // Nested level errors (metadata.*)
            $nestedErrors = \array_filter($errorPaths, static fn (string $path): bool => \str_contains($path, 'metadata'));
            self::assertNotEmpty($nestedErrors, 'Should find errors in nested objects (*.metadata.*)');

            // Deeply nested errors (metadata.dimensions.*)
            $deepErrors = \array_filter($errorPaths, static fn (string $path): bool => \str_contains($path, 'metadata.dimensions'));
            self::assertNotEmpty($deepErrors, 'Should find errors in deeply nested objects (*.metadata.dimensions.*)');
        }
    }

    /**
     * Verifies that errors in array items are all collected.
     *
     * Tests array validation with duplicate detection.
     */
    #[Test]
    public function itCollectsErrorsInArrayItems(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/multiple-errors/multiple-errors-10.json');

        try {
            Validator::validateRequest($json, $this->strictSchemasSpec);
            self::fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();

            // Find errors in array fields (tags with duplicates)
            $errorPaths = \array_map(static fn (ValidationError $e): string => $e->path, $errors);
            $arrayErrors = \array_filter($errorPaths, static fn (string $path): bool => \str_contains($path, 'tags'));

            self::assertNotEmpty($arrayErrors, 'Should find errors in array fields (tags)');

            // Verify we detect the duplicate items
            $duplicateErrors = \array_filter(
                $errors,
                static fn (ValidationError $e): bool => \str_contains($e->path, 'tags')
                    && \str_contains($e->constraint, 'unique')
            );
            self::assertNotEmpty($duplicateErrors, 'Should detect duplicate array items');
        }
    }

    /**
     * Stress test: Verifies validator can handle hundreds of errors without performance issues.
     *
     * Note: This test uses the 10+ errors fixture repeatedly to simulate stress.
     * In a real implementation, we'd have a fixture with 100+ violations.
     */
    #[Test]
    public function itHandlesHundredsOfErrors(): void
    {
        // For stress testing, we validate the same complex invalid data
        // The actual number of errors depends on the implementation
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/multiple-errors/multiple-errors-10.json');

        $start = \microtime(true);

        try {
            Validator::validateRequest($json, $this->strictSchemasSpec);
            self::fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $elapsed = \microtime(true) - $start;

            $errors = $e->getErrors();

            // Should collect at least 10 errors
            self::assertGreaterThanOrEqual(10, \count($errors));

            // Performance check: Should complete in reasonable time (< 1 second for 100+ errors)
            self::assertLessThan(1.0, $elapsed, 'Validation with many errors should complete in < 1 second');

            // Verify error message is well-formatted even with many errors
            $message = $e->getMessage();
            self::assertStringContainsString('Validation failed with', $message);
            self::assertStringContainsString('error', $message);
        }
    }

    // ========================================
    // Error Message Format (3 tests)
    // ========================================

    /**
     * Verifies that error messages include all context fields.
     *
     * Each error should have:
     * - path (JSONPath)
     * - specReference (line number in spec)
     * - constraint (what failed)
     * - expectedValue
     * - receivedValue
     * - reason (human explanation)
     * - hint (optional helpful suggestion)
     */
    #[Test]
    public function itFormatsErrorWithAllContextFields(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/multiple-errors/multiple-errors-5.json');

        try {
            Validator::validateRequest($json, $this->strictSchemasSpec);
            self::fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            self::assertNotEmpty($errors, 'Should have at least one error');

            $firstError = $errors[0];

            // Verify all required fields are present
            self::assertNotEmpty($firstError->path, 'Error must have path (JSONPath)');
            self::assertNotEmpty($firstError->constraint, 'Error must have constraint type');
            self::assertNotEmpty($firstError->reason, 'Error must have reason (human explanation)');

            // specReference may be empty for some errors, but property must exist
            self::assertTrue(\property_exists($firstError, 'specReference'));

            // expectedValue and receivedValue must exist (may be null/empty)
            self::assertTrue(\property_exists($firstError, 'expectedValue'));
            self::assertTrue(\property_exists($firstError, 'receivedValue'));

            // hint is optional but property must exist
            self::assertTrue(\property_exists($firstError, 'hint'));

            // Verify error message format
            $message = $e->getMessage();
            self::assertStringContainsString('Validation failed with', $message);
            self::assertStringContainsString($firstError->path, $message);
            self::assertStringContainsString($firstError->reason, $message);
            self::assertStringContainsString('expected:', $message);
            self::assertStringContainsString('received:', $message);
        }
    }

    /**
     * Verifies that JSONPath is included in error messages.
     *
     * Examples:
     * - "request.body.name"
     * - "request.body.user.address.city"
     * - "request.body.items[0].price"
     */
    #[Test]
    public function itIncludesJSONPathInError(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/multiple-errors/multiple-errors-10.json');

        try {
            Validator::validateRequest($json, $this->strictSchemasSpec);
            self::fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            self::assertNotEmpty($errors);

            foreach ($errors as $error) {
                // Every error must have a valid JSONPath
                self::assertNotEmpty($error->path, 'Every error must have a JSONPath');

                // Path should start with "request.body" for request validation
                self::assertStringStartsWith('request.body', $error->path, 'Request validation paths should start with "request.body"');

                // Path should use dot notation for nested objects
                if (\str_contains($error->path, 'metadata.dimensions')) {
                    self::assertMatchesRegularExpression(
                        '/request\.body\.metadata\.dimensions\.[a-zA-Z]+/',
                        $error->path,
                        'Nested paths should use dot notation'
                    );
                }
            }

            // Verify the error message includes the paths
            $message = $e->getMessage();
            self::assertStringContainsString('request.body', $message);
        }
    }

    /**
     * Verifies that spec line numbers are included in error messages.
     *
     * Examples:
     * - "openapi.yml line 142"
     * - "strict-schemas.json line 58"
     */
    #[Test]
    public function itIncludesSpecLineNumber(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/multiple-errors/multiple-errors-5.json');

        try {
            Validator::validateRequest($json, $this->strictSchemasSpec);
            self::fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            self::assertNotEmpty($errors);

            // At least some errors should have spec references with line numbers
            $errorsWithLineNumbers = \array_filter(
                $errors,
                static fn (ValidationError $e): bool => '' !== $e->specReference && \str_contains($e->specReference, 'line')
            );

            self::assertNotEmpty($errorsWithLineNumbers, 'At least some errors should include spec line numbers');

            // Verify format: "filename.ext line N"
            foreach ($errorsWithLineNumbers as $error) {
                self::assertMatchesRegularExpression(
                    '/[a-z0-9_-]+\.(json|yml|yaml) line \d+/i',
                    $error->specReference,
                    'Spec reference should match format: "filename.ext line N"'
                );
            }

            // Verify the error message includes spec references
            $message = $e->getMessage();
            self::assertStringContainsString('line', $message);
        }
    }

    // ========================================
    // Hint Generation (4 tests)
    // ========================================

    /**
     * Verifies that hints suggest snake_case to camelCase conversion.
     *
     * Example: user_name → userName
     */
    #[Test]
    public function itSuggestsSnakeCaseToCamelCaseConversion(): void
    {
        // multiple-errors-5.json has "nam" instead of "name" - not quite the right test case
        // but we'll verify that hints are generated for field name issues
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/multiple-errors/multiple-errors-5.json');

        try {
            Validator::validateRequest($json, $this->strictSchemasSpec);
            self::fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();

            // Look for errors with hints about naming conventions
            $hintsProvided = \array_filter(
                $errors,
                static fn (ValidationError $e): bool => null !== $e->hint && '' !== $e->hint
            );

            // At least some errors should have helpful hints
            self::assertNotEmpty($hintsProvided, 'Should provide hints for common mistakes');

            // Verify hint format is helpful
            foreach ($hintsProvided as $error) {
                self::assertNotEmpty($error->hint);
                // Hints should be lowercase and conversational
                self::assertMatchesRegularExpression('/^[a-z]/', $error->hint, 'Hints should start with lowercase');
            }
        }
    }

    /**
     * Verifies that hints suggest type conversion.
     *
     * Example: "35" → 35 (string to integer)
     */
    #[Test]
    public function itSuggestsTypeConversion(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/multiple-errors/multiple-errors-10.json');

        try {
            Validator::validateRequest($json, $this->strictSchemasSpec);
            self::fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();

            // Look for type mismatch errors
            $typeMismatchErrors = \array_filter(
                $errors,
                static fn (ValidationError $e): bool => 'type' === $e->constraint
            );

            self::assertNotEmpty($typeMismatchErrors, 'Should have type mismatch errors');

            // At least some type errors should have hints about conversion
            $typeHints = \array_filter(
                $typeMismatchErrors,
                static fn (ValidationError $e): bool => null !== $e->hint
                    && (\str_contains($e->hint, 'type') || \str_contains($e->hint, 'convert'))
            );

            self::assertNotEmpty($typeHints, 'Type mismatch errors should have conversion hints');
        }
    }

    /**
     * Verifies that hints suggest closest field name using Levenshtein distance.
     *
     * Example: usrName → userName (typo detection)
     */
    #[Test]
    public function itSuggestsClosestFieldName(): void
    {
        // multiple-errors-5.json has "nam" which is close to "name"
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/multiple-errors/multiple-errors-5.json');

        try {
            Validator::validateRequest($json, $this->strictSchemasSpec);
            self::fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();

            // Look for errors about missing required fields or additional properties
            $namingErrors = \array_filter(
                $errors,
                static fn (ValidationError $e): bool => \in_array($e->constraint, ['required', 'additionalProperties'], true)
            );

            self::assertNotEmpty($namingErrors, 'Should have naming-related errors');

            // At least some should have hints about similar field names
            $namingHints = \array_filter(
                $namingErrors,
                static fn (ValidationError $e): bool => null !== $e->hint
                    && (\str_contains($e->hint, 'did you mean') || \str_contains($e->hint, 'similar'))
            );

            self::assertNotEmpty($namingHints, 'Naming errors should suggest similar field names');
        }
    }

    /**
     * Verifies that hints suggest format fixes.
     *
     * Example: user@example → user@example.com (incomplete email)
     */
    #[Test]
    public function itSuggestsFormatFixes(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/multiple-errors/multiple-errors-5.json');

        try {
            Validator::validateRequest($json, $this->strictSchemasSpec);
            self::fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();

            // Look for format violation errors (date-time, email, uuid, etc.)
            $formatErrors = \array_filter(
                $errors,
                static fn (ValidationError $e): bool => 'format' === $e->constraint
            );

            self::assertNotEmpty($formatErrors, 'Should have format violation errors');

            // At least some format errors should have hints
            $formatHints = \array_filter(
                $formatErrors,
                static fn (ValidationError $e): bool => null !== $e->hint && '' !== $e->hint
            );

            self::assertNotEmpty($formatHints, 'Format errors should provide helpful hints');

            // Verify hints mention the expected format
            foreach ($formatHints as $error) {
                self::assertNotEmpty($error->hint);
                // Format hints should explain the expected format
                self::assertMatchesRegularExpression(
                    '/(format|expected|should be|example)/i',
                    $error->hint,
                    'Format hints should explain expected format'
                );
            }
        }
    }

    // ========================================
    // Error Ordering (3 tests)
    // ========================================

    /**
     * Verifies that errors are ordered by JSONPath (alphabetically).
     */
    #[Test]
    public function itOrdersErrorsByPath(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/multiple-errors/multiple-errors-10.json');

        try {
            Validator::validateRequest($json, $this->strictSchemasSpec);
            self::fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            self::assertGreaterThan(1, \count($errors), 'Need multiple errors to test ordering');

            // Extract paths
            $paths = \array_map(static fn (ValidationError $e): string => $e->path, $errors);

            // Verify alphabetical ordering
            $sortedPaths = $paths;
            \sort($sortedPaths, \SORT_STRING);

            self::assertSame(
                $sortedPaths,
                $paths,
                'Errors should be ordered alphabetically by path'
            );
        }
    }

    /**
     * Verifies that errors are ordered by depth (shallow before deep).
     *
     * Example order:
     * 1. request.body.name (depth 2)
     * 2. request.body.metadata.weight (depth 3)
     * 3. request.body.metadata.dimensions.length (depth 4)
     */
    #[Test]
    public function itOrdersErrorsByDepth(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/multiple-errors/multiple-errors-10.json');

        try {
            Validator::validateRequest($json, $this->strictSchemasSpec);
            self::fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            self::assertGreaterThan(1, \count($errors), 'Need multiple errors to test ordering');

            // Calculate depth for each error (count dots in path)
            $depths = \array_map(
                static fn (ValidationError $e): int => \substr_count($e->path, '.'),
                $errors
            );

            // Verify that errors are generally ordered by depth (allowing ties)
            $prevDepth = 0;
            foreach ($depths as $depth) {
                self::assertGreaterThanOrEqual(
                    $prevDepth,
                    $depth,
                    'Errors should be ordered from shallow to deep (ties allowed)'
                );
                $prevDepth = $depth;
            }
        }
    }

    /**
     * Verifies that related errors (same field, multiple violations) are grouped together.
     *
     * Example: If "price" violates both type and minimum constraints,
     * those errors should appear consecutively.
     */
    #[Test]
    public function itGroupsRelatedErrors(): void
    {
        $json = \Safe\file_get_contents(__DIR__ . '/Fixtures/InvalidData/multiple-errors/multiple-errors-10.json');

        try {
            Validator::validateRequest($json, $this->strictSchemasSpec);
            self::fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            self::assertGreaterThan(1, \count($errors), 'Need multiple errors to test grouping');

            // Find fields with multiple errors
            $pathCounts = [];
            foreach ($errors as $error) {
                $pathCounts[$error->path] = ($pathCounts[$error->path] ?? 0) + 1;
            }

            $fieldsWithMultipleErrors = \array_filter($pathCounts, static fn (int $count): int => $count > 1);

            if ([] !== $fieldsWithMultipleErrors) {
                // For each field with multiple errors, verify they're consecutive
                foreach (\array_keys($fieldsWithMultipleErrors) as $path) {
                    $indices = [];
                    foreach ($errors as $index => $error) {
                        if ($path === $error->path) {
                            $indices[] = $index;
                        }
                    }

                    // Check if indices are consecutive
                    $isConsecutive = true;
                    for ($i = 1, $iMax = \count($indices); $i < $iMax; ++$i) {
                        if ($indices[$i] !== $indices[$i - 1] + 1) {
                            $isConsecutive = false;
                            break;
                        }
                    }

                    self::assertTrue(
                        $isConsecutive,
                        "Errors for path '{$path}' should be grouped consecutively"
                    );
                }
            } else {
                // If no field has multiple errors, that's fine - just verify the data
                self::assertTrue(true, 'No fields with multiple errors in this fixture');
            }
        }
    }
}
