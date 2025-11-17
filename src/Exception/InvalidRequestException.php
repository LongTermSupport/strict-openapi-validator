<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Exception;

/**
 * Exception thrown when request validation fails.
 *
 * This covers all request validation failures, including:
 * - Body validation errors
 * - Parameter validation errors
 * - Header validation errors
 * - Path validation errors
 * - Content-type mismatches
 *
 * More specific request validation failures use dedicated exception types.
 */
class InvalidRequestException extends ValidationException
{
}
