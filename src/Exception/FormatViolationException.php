<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Exception;

/**
 * Exception thrown when value doesn't match schema format constraint.
 *
 * Supported formats (from OpenAPI 3.1.0 / JSON Schema):
 * - email (RFC 5322)
 * - uuid (RFC 4122)
 * - date (YYYY-MM-DD)
 * - date-time (RFC 3339)
 * - uri (RFC 3986)
 * - uri-reference (RFC 3986)
 * - hostname (RFC 1123)
 * - ipv4 (dotted quad)
 * - ipv6 (RFC 4291)
 * - int32 (32-bit signed integer)
 * - int64 (64-bit signed integer)
 * - float (single precision)
 * - double (double precision)
 *
 * Examples:
 * - "not-an-email" for format: email
 * - "12/25/2023" for format: date
 * - "2023-12-25 10:30:00" for format: date-time
 */
final class FormatViolationException extends SchemaViolationException
{
}
