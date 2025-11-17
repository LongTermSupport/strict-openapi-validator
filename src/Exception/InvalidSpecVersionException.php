<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Exception;

/**
 * Exception thrown when OpenAPI version is invalid or unsupported.
 *
 * This validator strictly supports OpenAPI 3.1.x only.
 *
 * Examples of invalid versions:
 * - "3.0.0" (OpenAPI 3.0.x not supported)
 * - "2.0" (Swagger 2.0 not supported)
 * - "3.1" (must be fully qualified, e.g., "3.1.0")
 * - Missing version field
 */
final class InvalidSpecVersionException extends InvalidSpecException
{
}
