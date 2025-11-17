<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Exception;

/**
 * Exception thrown when schema validation fails.
 *
 * This covers general schema validation failures.
 * More specific schema violations use dedicated exception types:
 * - TypeMismatchException
 * - FormatViolationException
 * - RequiredFieldMissingException
 * - AdditionalPropertyException
 * - EnumViolationException
 * - BoundaryViolationException
 * - PatternViolationException
 * - CompositionViolationException
 * - DiscriminatorViolationException
 */
class SchemaViolationException extends ValidationException
{
}
