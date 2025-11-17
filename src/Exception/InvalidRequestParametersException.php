<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Exception;

/**
 * Exception thrown when request parameter validation fails.
 *
 * Examples:
 * - Invalid query parameters
 * - Invalid path parameters
 * - Missing required parameters
 * - Type mismatches in parameters
 * - Parameter constraint violations
 */
final class InvalidRequestParametersException extends InvalidRequestException
{
}
