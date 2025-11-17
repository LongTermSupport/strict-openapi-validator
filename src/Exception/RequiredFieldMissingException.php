<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Exception;

/**
 * Exception thrown when a required field is missing.
 *
 * This validates the "required" keyword in JSON Schema:
 * - Required fields MUST be present
 * - Required fields can be null IF type includes "null"
 * - Required fields cannot be missing entirely
 *
 * Note: This is different from optional fields being null:
 * - Optional field missing: OK
 * - Optional field null (not nullable): FAIL (TypeMismatchException)
 * - Optional field null (nullable): OK
 * - Required field missing: FAIL (RequiredFieldMissingException)
 * - Required field null (not nullable): FAIL (TypeMismatchException)
 * - Required field null (nullable): OK
 */
final class RequiredFieldMissingException extends SchemaViolationException
{
}
