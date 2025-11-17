<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Exception;

/**
 * Exception thrown when schema composition constraint is violated.
 *
 * This validates composition keywords from JSON Schema:
 *
 * oneOf:
 * - Value must match EXACTLY ONE of the schemas
 * - Matching zero schemas: FAIL
 * - Matching multiple schemas: FAIL
 *
 * anyOf:
 * - Value must match AT LEAST ONE of the schemas
 * - Matching zero schemas: FAIL
 * - Matching one or more schemas: PASS
 *
 * allOf:
 * - Value must match ALL of the schemas
 * - Failing any schema: FAIL
 * - Matching all schemas: PASS
 *
 * not:
 * - Value must NOT match the schema
 * - Matching schema: FAIL
 * - Not matching schema: PASS
 *
 * Examples:
 * - oneOf: [{type: "string"}, {type: "number"}] with true - matches neither
 * - oneOf: [{type: "string"}, {type: "string", minLength: 5}] with "hello" - matches both
 * - anyOf: [{type: "string"}, {type: "number"}] with true - matches neither
 * - allOf: [{type: "string"}, {minLength: 5}] with "hi" - fails second schema
 */
final class CompositionViolationException extends SchemaViolationException
{
}
