<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Exception;

/**
 * Exception thrown when additional properties are not allowed.
 *
 * This validates the "additionalProperties" keyword in JSON Schema:
 * - additionalProperties: false - NO extra properties allowed
 * - additionalProperties: true - Extra properties allowed
 * - additionalProperties: {schema} - Extra properties must match schema
 *
 * Common causes:
 * - Typo in property name (e.g., "user_name" vs "userName")
 * - snake_case vs camelCase confusion
 * - Extra fields sent in request that aren't in spec
 * - Schema uses strict validation (additionalProperties: false)
 *
 * Hints provided for:
 * - Snake_case to camelCase conversion
 * - Similar property names in schema
 */
final class AdditionalPropertyException extends SchemaViolationException
{
}
