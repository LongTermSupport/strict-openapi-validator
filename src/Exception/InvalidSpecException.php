<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Exception;

/**
 * Exception thrown when OpenAPI specification is invalid.
 *
 * This covers general spec validation failures, including:
 * - Invalid structure
 * - Invalid path formats
 * - Duplicate operation IDs
 * - Invalid schema definitions
 * - Conflicting constraints
 *
 * More specific spec validation failures use dedicated exception types.
 */
class InvalidSpecException extends ValidationException
{
}
