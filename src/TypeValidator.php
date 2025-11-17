<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator;

use LongTermSupport\StrictOpenApiValidator\Exception\ValidationError;

/**
 * Validates data types against OpenAPI schema types.
 *
 * Implements STRICT type validation with NO coercion:
 * - "123" !== 123
 * - 3.0 !== 3 (for integer type)
 * - true !== "true"
 */
final readonly class TypeValidator
{
    /**
     * Validate data type against schema type.
     *
     * @param mixed $data Data to validate
     * @param array<string, mixed> $schema Schema to validate against
     * @param string $path Current path in the data (for error reporting)
     * @param ErrorCollector $errors Error collector
     */
    public static function validate(mixed $data, array $schema, string $path, ErrorCollector $errors): void
    {
        // If no type specified, skip type validation
        if (!isset($schema['type'])) {
            return;
        }

        $expectedType = $schema['type'];

        // Handle nullable types (type can be array like ["string", "null"])
        if (\is_array($expectedType)) {
            $valid = false;
            foreach ($expectedType as $type) {
                if (\is_string($type) && self::matchesType($data, $type)) {
                    $valid = true;
                    break;
                }
            }
            if (!$valid) {
                $typeStrings = [];
                foreach ($expectedType as $type) {
                    if (\is_string($type)) {
                        $typeStrings[] = $type;
                    }
                }
                $typeList = \implode(' or ', $typeStrings);
                $errors->addError(new ValidationError(
                    path: $path,
                    specReference: '#/schema/type',
                    constraint: 'type',
                    expectedValue: $typeList,
                    receivedValue: \get_debug_type($data),
                    reason: \sprintf('Expected type %s, got %s', $typeList, \get_debug_type($data)),
                    hint: null
                ));
            }
            return;
        }

        // Single type validation
        if (!\is_string($expectedType)) {
            return;
        }

        if (!self::matchesType($data, $expectedType)) {
            $errors->addError(new ValidationError(
                path: $path,
                specReference: '#/schema/type',
                constraint: 'type',
                expectedValue: $expectedType,
                receivedValue: \get_debug_type($data),
                reason: \sprintf('Expected type %s, got %s', $expectedType, \get_debug_type($data)),
                hint: self::getTypeHint($data, $expectedType)
            ));
        }
    }

    /**
     * Check if data matches expected type (strict, no coercion).
     */
    private static function matchesType(mixed $data, string $expectedType): bool
    {
        return match ($expectedType) {
            'string' => \is_string($data),
            'number' => \is_float($data) || \is_int($data),
            'integer' => \is_int($data),
            'boolean' => \is_bool($data),
            'array' => \is_array($data) && ([] !== $data && \array_is_list($data) || [] === $data),
            'object' => \is_array($data) && ([] === $data || !\array_is_list($data)),
            'null' => null === $data,
            default => false,
        };
    }

    /**
     * Get helpful hint for type mismatch.
     */
    private static function getTypeHint(mixed $data, string $expectedType): ?string
    {
        // String coercion hint
        if ('integer' === $expectedType && \is_string($data) && \is_numeric($data)) {
            return 'This validator does NOT coerce types. "35" ≠ 35. Use actual integer value.';
        }

        if ('number' === $expectedType && \is_string($data) && \is_numeric($data)) {
            return 'This validator does NOT coerce types. "3.14" ≠ 3.14. Use actual number value.';
        }

        if ('string' === $expectedType && \is_int($data)) {
            return 'This validator does NOT coerce types. 123 ≠ "123". Use actual string value.';
        }

        // Float vs integer
        if ('integer' === $expectedType && \is_float($data)) {
            return 'Integer type does NOT accept float values, even if mathematically equivalent (e.g., 3.0).';
        }

        return null;
    }
}
