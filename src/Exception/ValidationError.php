<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Exception;

/**
 * Represents a single validation error with full context.
 *
 * This value object encapsulates all information needed to understand
 * and debug a validation failure, including:
 * - Where the error occurred (path)
 * - What part of the spec was violated (specReference)
 * - What constraint failed (constraint)
 * - Expected vs received values
 * - Human-readable explanation
 * - Optional hints for common mistakes
 */
final readonly class ValidationError
{
    public function __construct(
        /**
         * JSONPath to the problematic field (e.g., "request.body.user.age")
         */
        public string $path,
        /**
         * Line number in OpenAPI spec where constraint is defined (e.g., "openapi.yml line 142")
         */
        public string $specReference,
        /**
         * What constraint was violated (e.g., "type", "required", "format", "pattern")
         */
        public string $constraint,
        /**
         * What was expected according to the spec
         */
        public mixed $expectedValue,
        /**
         * What was actually received in the data
         */
        public mixed $receivedValue,
        /**
         * Why the validation failed (human-readable explanation)
         */
        public string $reason,
        /**
         * Helpful hint for common issues (e.g., "this looks like a snake_case/camelCase confusion")
         */
        public ?string $hint = null,
    ) {
    }
}
