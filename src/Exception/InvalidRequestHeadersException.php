<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Exception;

/**
 * Exception thrown when request header validation fails.
 *
 * Examples:
 * - Missing required headers
 * - Invalid header values
 * - Type mismatches in header values
 * - Invalid Content-Type header
 * - Missing Authorization header
 */
final class InvalidRequestHeadersException extends InvalidRequestException
{
}
