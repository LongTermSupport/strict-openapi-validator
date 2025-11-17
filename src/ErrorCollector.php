<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator;

use LongTermSupport\StrictOpenApiValidator\Exception\ValidationError;

/**
 * Collects validation errors without failing fast.
 *
 * Allows collecting multiple errors before throwing exceptions,
 * providing comprehensive validation feedback.
 */
final class ErrorCollector
{
    /**
     * @var ValidationError[]
     */
    private array $errors = [];

    /**
     * Add a validation error to the collection.
     */
    public function addError(ValidationError $error): void
    {
        $this->errors[] = $error;
    }

    /**
     * Check if any errors have been collected.
     */
    public function hasErrors(): bool
    {
        return [] !== $this->errors;
    }

    /**
     * Get all collected errors.
     *
     * @return ValidationError[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get count of collected errors.
     */
    public function getErrorCount(): int
    {
        return \count($this->errors);
    }

    /**
     * Clear all collected errors.
     */
    public function clear(): void
    {
        $this->errors = [];
    }
}
