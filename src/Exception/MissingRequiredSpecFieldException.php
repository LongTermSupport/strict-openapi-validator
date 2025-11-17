<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Exception;

/**
 * Exception thrown when a required field is missing from the OpenAPI specification.
 *
 * Examples:
 * - Missing "openapi" field
 * - Missing "info" object
 * - Missing "info.title"
 * - Missing "info.version"
 * - Missing response descriptions
 */
final class MissingRequiredSpecFieldException extends InvalidSpecException
{
}
