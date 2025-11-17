<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Exception;

/**
 * Exception thrown when response validation fails.
 *
 * This covers all response validation failures, including:
 * - Body validation errors
 * - Header validation errors
 * - Status code validation errors
 * - Content-type mismatches
 *
 * More specific response validation failures use dedicated exception types.
 */
class InvalidResponseException extends ValidationException
{
}
