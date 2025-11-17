<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Exception;

/**
 * Exception thrown when request path validation fails.
 *
 * Examples:
 * - Path doesn't match any operation in spec
 * - Path template parameters missing or invalid
 * - HTTP method not supported for path
 * - Path parameter type mismatches
 */
final class InvalidRequestPathException extends InvalidRequestException
{
}
