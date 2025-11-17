<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Exception;

/**
 * Exception thrown when value is not in enum.
 *
 * This validates the "enum" keyword in JSON Schema:
 * - Value must exactly match one of the enum values
 * - Matching is case-sensitive
 * - Type must match exactly (no coercion)
 *
 * Examples:
 * - "red" is valid for enum: ["red", "green", "blue"]
 * - "Red" is INVALID (case mismatch)
 * - 1 is INVALID for enum: ["1", "2"] (type mismatch - string vs number)
 *
 * Hints provided for:
 * - Case mismatches (suggest correct case)
 * - Close matches (Levenshtein distance)
 * - Type conversion needed
 */
final class EnumViolationException extends SchemaViolationException
{
}
