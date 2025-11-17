<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Exception;

/**
 * Exception thrown when value violates boundary constraints.
 *
 * This validates boundary keywords from JSON Schema:
 *
 * Number boundaries:
 * - minimum (inclusive)
 * - maximum (inclusive)
 * - exclusiveMinimum (exclusive)
 * - exclusiveMaximum (exclusive)
 * - multipleOf
 *
 * String boundaries:
 * - minLength (inclusive)
 * - maxLength (inclusive)
 *
 * Array boundaries:
 * - minItems (inclusive)
 * - maxItems (inclusive)
 * - uniqueItems (boolean)
 *
 * Object boundaries:
 * - minProperties (inclusive)
 * - maxProperties (inclusive)
 *
 * Examples:
 * - Value -1 fails minimum: 0
 * - Value 101 fails maximum: 100
 * - Value 0 fails exclusiveMinimum: 0
 * - String "ab" fails minLength: 3
 * - Array [1, 2, 2] fails uniqueItems: true
 */
final class BoundaryViolationException extends SchemaViolationException
{
}
