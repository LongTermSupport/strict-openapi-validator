<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Exception;

/**
 * Exception thrown when data type doesn't match schema type.
 *
 * This validator enforces strict type checking with NO implicit coercion:
 * - "123" (string) does NOT match {type: "number"}
 * - 123 (number) does NOT match {type: "string"}
 * - 1 (number) does NOT match {type: "boolean"}
 * - "true" (string) does NOT match {type: "boolean"}
 * - 123.0 (float) does NOT match {type: "integer"}
 *
 * Examples:
 * - String value where number expected
 * - Number value where string expected
 * - Null value where non-nullable type expected
 * - Array value where object expected
 * - Object value where array expected
 */
final class TypeMismatchException extends SchemaViolationException
{
}
