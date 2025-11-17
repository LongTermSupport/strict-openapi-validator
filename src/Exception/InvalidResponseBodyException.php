<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Exception;

/**
 * Exception thrown when response body validation fails.
 *
 * Examples:
 * - Type mismatches in response body
 * - Missing required fields
 * - Additional properties not allowed
 * - Format violations
 * - Invalid JSON structure
 */
final class InvalidResponseBodyException extends InvalidResponseException
{
}
