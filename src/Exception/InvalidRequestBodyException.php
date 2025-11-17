<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Exception;

/**
 * Exception thrown when request body validation fails.
 *
 * Examples:
 * - Type mismatches in body
 * - Missing required fields
 * - Additional properties not allowed
 * - Format violations
 * - Invalid JSON structure
 */
final class InvalidRequestBodyException extends InvalidRequestException
{
}
