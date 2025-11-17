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
                $receivedType = self::getJsonSchemaTypeName($data);
                $errors->addError(new ValidationError(
                    path: $path,
                    specReference: '#/schema/type',
                    constraint: 'type',
                    expectedValue: $typeList,
                    receivedValue: $receivedType,
                    reason: \sprintf('Expected type %s, got %s', $typeList, $receivedType),
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
            $receivedType = self::getJsonSchemaTypeName($data);
            $errors->addError(new ValidationError(
                path: $path,
                specReference: '#/schema/type',
                constraint: 'type',
                expectedValue: $expectedType,
                receivedValue: $receivedType,
                reason: \sprintf('Expected type %s, got %s', $expectedType, $receivedType),
                hint: self::getTypeHint($data, $expectedType)
            ));
        }
    }

    /**
     * Check if data matches expected type (strict, no coercion).
     *
     * Note: Data comes from json_decode($json, false) so objects are stdClass.
     */
    private static function matchesType(mixed $data, string $expectedType): bool
    {
        return match ($expectedType) {
            'string' => \is_string($data),
            'number' => \is_float($data) || \is_int($data),
            'integer' => \is_int($data),
            'boolean' => \is_bool($data),
            'array' => \is_array($data),
            'object' => $data instanceof \stdClass,
            'null' => null === $data,
            default => false,
        };
    }

    /**
     * Get JSON Schema type name from PHP value.
     *
     * Converts PHP debug types to JSON Schema type names for error messages.
     * PHP uses "bool" and "int", but JSON Schema uses "boolean" and "integer".
     */
    private static function getJsonSchemaTypeName(mixed $data): string
    {
        return match (true) {
            \is_bool($data) => 'boolean',
            \is_int($data) => 'integer',
            \is_float($data) => 'number',
            \is_string($data) => 'string',
            \is_array($data) => 'array',
            $data instanceof \stdClass => 'object',
            null === $data => 'null',
            default => \get_debug_type($data),
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
