<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Exception;

/**
 * Exception thrown when response status code validation fails.
 *
 * Examples:
 * - Undocumented status code returned
 * - Status code not defined in spec
 * - Invalid status code format
 * - Response doesn't match status code schema
 */
final class InvalidResponseStatusException extends InvalidResponseException
{
}
