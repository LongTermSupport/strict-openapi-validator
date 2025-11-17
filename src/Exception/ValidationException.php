<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Exception;

/**
 * Base exception for all validation failures.
 *
 * This exception collects multiple validation errors before throwing,
 * ensuring that all validation issues are reported in a single exception
 * rather than failing fast on the first error.
 *
 * The error message is formatted to be LLM-optimized, providing clear
 * context about what failed, where it failed, and how to fix it.
 */
abstract class ValidationException extends \Exception
{
    /**
     * @param ValidationError[] $errors Array of validation errors
     * @param string $message Optional custom message (auto-generated if empty)
     */
    public function __construct(
        private readonly array $errors,
        string $message = '',
    ) {
        parent::__construct('' !== $message ? $message : $this->buildMessage());
    }

    /**
     * Get all validation errors.
     *
     * @return ValidationError[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Build LLM-optimized error message with all errors.
     *
     * Format:
     * Validation failed with N errors:
     *
     * [1] unexpected string at request.body.user.age, breaking openapi.yml line 142 expectations
     *     expected: integer
     *     received: "25"
     *     hint: this looks like a type confusion issue - received string "25" but spec requires integer 25
     *
     * [2] ...
     */
    private function buildMessage(): string
    {
        $count = \count($this->errors);
        if (0 === $count) {
            return 'Validation failed with no specific errors';
        }

        $lines = ["Validation failed with {$count} error" . (1 === $count ? '' : 's') . ':', ''];

        foreach ($this->errors as $index => $error) {
            $num = $index + 1;

            // Build main error line
            $mainLine = "[{$num}] {$error->reason} at {$error->path}";
            if ('' !== $error->specReference) {
                $mainLine .= ", breaking {$error->specReference} expectations";
            }
            $lines[] = $mainLine;

            // Add expected value
            $lines[] = '    expected: ' . $this->formatValue($error->expectedValue);

            // Add received value if different
            if ($error->expectedValue !== $error->receivedValue) {
                $lines[] = '    received: ' . $this->formatValue($error->receivedValue);
            }

            // Add hint if provided
            if (null !== $error->hint) {
                $lines[] = '    hint: ' . $error->hint;
            }

            // Add blank line between errors (except after last)
            if ($num < $count) {
                $lines[] = '';
            }
        }

        return \implode("\n", $lines);
    }

    /**
     * Format a value for display in error messages.
     */
    private function formatValue(mixed $value): string
    {
        if (\is_string($value)) {
            return '"' . $value . '"';
        }

        if (null === $value) {
            return 'null';
        }

        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (\is_array($value)) {
            return \Safe\json_encode($value);
        }

        if (\is_object($value)) {
            return \Safe\json_encode($value);
        }

        // Scalar types (int, float, resource) - safe to cast to string
        if (\is_scalar($value) || \is_resource($value)) {
            return (string) $value;
        }

        // Fallback for unexpected types
        return \Safe\json_encode($value);
    }
}
