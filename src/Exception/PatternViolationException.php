<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Exception;

/**
 * Exception thrown when string doesn't match pattern constraint.
 *
 * This validates the "pattern" keyword in JSON Schema:
 * - Pattern is a regular expression (ECMA-262)
 * - String must match the entire pattern
 * - Pattern matching is case-sensitive
 *
 * Examples:
 * - "abc" matches pattern: "^[a-z]+$"
 * - "ABC" FAILS pattern: "^[a-z]+$" (case mismatch)
 * - "ab" FAILS pattern: "^[a-z]{3}$" (too short)
 * - "123abc456" FAILS pattern: "^[a-z]+$" (contains numbers)
 *
 * Common patterns:
 * - Phone numbers: "^\+?[1-9]\d{1,14}$"
 * - Alphanumeric: "^[a-zA-Z0-9]+$"
 * - Uppercase only: "^[A-Z]+$"
 */
final class PatternViolationException extends SchemaViolationException
{
}
