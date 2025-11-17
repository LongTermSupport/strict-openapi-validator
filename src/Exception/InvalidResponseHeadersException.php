<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Exception;

/**
 * Exception thrown when response header validation fails.
 *
 * Examples:
 * - Missing required response headers
 * - Invalid header values
 * - Type mismatches in header values
 * - Invalid Content-Type header
 */
final class InvalidResponseHeadersException extends InvalidResponseException
{
}
