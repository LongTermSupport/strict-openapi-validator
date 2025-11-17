<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator;

use LongTermSupport\StrictOpenApiValidator\Exception\ValidationError;

/**
 * Core schema validation logic.
 *
 * Validates data against OpenAPI/JSON Schema with:
 * - Type validation (strict, no coercion)
 * - Required fields
 * - Additional properties
 * - Format validation
 * - Boundary constraints (min/max, minLength/maxLength, etc.)
 */
final readonly class SchemaValidator
{
    /**
     * Validate data against schema.
     *
     * @param mixed $data Data to validate
     * @param array<string, mixed> $schema Schema to validate against
     * @param string $path Current path in the data (for error reporting)
     * @param ErrorCollector $errors Error collector
     * @param array<string, mixed> $spec Full OpenAPI spec (for resolving $refs)
     */
    public static function validate(mixed $data, array $schema, string $path, ErrorCollector $errors, array $spec): void
    {
        // Resolve $ref if present
        if (isset($schema['$ref']) && \is_string($schema['$ref'])) {
            $schema = self::resolveRef($schema['$ref'], $spec);
        }

        // Type validation (strict, no coercion)
        TypeValidator::validate($data, $schema, $path, $errors);

        // If type validation failed, skip further validation
        // (avoid cascade of errors from wrong type)
        if ($errors->hasErrors() && isset($schema['type'])) {
            return;
        }

        // Required fields (for objects)
        if (\is_array($data) && !\array_is_list($data)) {
            self::validateRequired($data, $schema, $path, $errors);
        }

        // Additional properties (for objects)
        if (\is_array($data) && !\array_is_list($data)) {
            self::validateAdditionalProperties($data, $schema, $path, $errors, $spec);
        }

        // Format validation (for strings)
        FormatValidator::validate($data, $schema, $path, $errors);

        // Boundary validation
        self::validateBoundaries($data, $schema, $path, $errors);

        // Enum validation
        self::validateEnum($data, $schema, $path, $errors);

        // Pattern validation (for strings)
        self::validatePattern($data, $schema, $path, $errors);

        // Nested object properties
        if (\is_array($data) && !\array_is_list($data) && isset($schema['properties']) && \is_array($schema['properties'])) {
            foreach ($schema['properties'] as $propName => $propSchema) {
                if (\array_key_exists($propName, $data)) {
                    self::validate($data[$propName], $propSchema, "{$path}.{$propName}", $errors, $spec);
                }
            }
        }

        // Array items
        if (\is_array($data) && \array_is_list($data) && isset($schema['items']) && \is_array($schema['items'])) {
            foreach ($data as $index => $item) {
                self::validate($item, $schema['items'], "{$path}[{$index}]", $errors, $spec);
            }
        }
    }

    /**
     * Validate required fields.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $schema
     */
    private static function validateRequired(array $data, array $schema, string $path, ErrorCollector $errors): void
    {
        if (!isset($schema['required']) || !\is_array($schema['required'])) {
            return;
        }

        foreach ($schema['required'] as $requiredField) {
            if (!\is_string($requiredField)) {
                continue;
            }

            if (!\array_key_exists($requiredField, $data)) {
                $errors->addError(new ValidationError(
                    path: "{$path}.{$requiredField}",
                    specReference: '#/schema/required',
                    constraint: 'required',
                    expectedValue: 'field must be present',
                    receivedValue: 'missing',
                    reason: \sprintf('Required field "%s" is missing', $requiredField),
                    hint: null
                ));
            }
        }
    }

    /**
     * Validate additional properties.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $schema
     * @param array<string, mixed> $spec
     */
    private static function validateAdditionalProperties(array $data, array $schema, string $path, ErrorCollector $errors, array $spec): void
    {
        // If additionalProperties is not set or is true, allow any additional properties
        if (!isset($schema['additionalProperties'])) {
            return;
        }

        // If additionalProperties is false, no additional properties allowed
        if (false === $schema['additionalProperties']) {
            $allowedProps = isset($schema['properties']) && \is_array($schema['properties'])
                ? \array_keys($schema['properties'])
                : [];

            foreach ($data as $key => $value) {
                if (!\in_array($key, $allowedProps, true)) {
                    $errors->addError(new ValidationError(
                        path: "{$path}.{$key}",
                        specReference: '#/schema/additionalProperties',
                        constraint: 'additionalProperties',
                        expectedValue: 'no additional properties',
                        receivedValue: $key,
                        reason: \sprintf('Additional property "%s" is not allowed', $key),
                        hint: self::getSuggestion($key, $allowedProps)
                    ));
                }
            }
        }
    }

    /**
     * Validate boundary constraints (min/max, minLength/maxLength, minItems/maxItems, etc.).
     *
     * @param array<string, mixed> $schema
     */
    private static function validateBoundaries(mixed $data, array $schema, string $path, ErrorCollector $errors): void
    {
        // String length boundaries
        if (\is_string($data)) {
            if (isset($schema['minLength']) && \is_int($schema['minLength'])) {
                $length = \mb_strlen($data);
                if ($length < $schema['minLength']) {
                    $errors->addError(new ValidationError(
                        path: $path,
                        specReference: '#/schema/minLength',
                        constraint: 'minLength',
                        expectedValue: "length >= {$schema['minLength']}",
                        receivedValue: $length,
                        reason: \sprintf('String length %d is less than minLength %d', $length, $schema['minLength']),
                        hint: null
                    ));
                }
            }

            if (isset($schema['maxLength']) && \is_int($schema['maxLength'])) {
                $length = \mb_strlen($data);
                if ($length > $schema['maxLength']) {
                    $errors->addError(new ValidationError(
                        path: $path,
                        specReference: '#/schema/maxLength',
                        constraint: 'maxLength',
                        expectedValue: "length <= {$schema['maxLength']}",
                        receivedValue: $length,
                        reason: \sprintf('String length %d exceeds maxLength %d', $length, $schema['maxLength']),
                        hint: null
                    ));
                }
            }
        }

        // Numeric boundaries
        if (\is_int($data) || \is_float($data)) {
            if (isset($schema['minimum']) && \is_numeric($schema['minimum'])) {
                if ($data < $schema['minimum']) {
                    $errors->addError(new ValidationError(
                        path: $path,
                        specReference: '#/schema/minimum',
                        constraint: 'minimum',
                        expectedValue: ">= {$schema['minimum']}",
                        receivedValue: $data,
                        reason: \sprintf('Value %s is less than minimum %s', $data, $schema['minimum']),
                        hint: null
                    ));
                }
            }

            if (isset($schema['maximum']) && \is_numeric($schema['maximum'])) {
                if ($data > $schema['maximum']) {
                    $errors->addError(new ValidationError(
                        path: $path,
                        specReference: '#/schema/maximum',
                        constraint: 'maximum',
                        expectedValue: "<= {$schema['maximum']}",
                        receivedValue: $data,
                        reason: \sprintf('Value %s exceeds maximum %s', $data, $schema['maximum']),
                        hint: null
                    ));
                }
            }

            if (isset($schema['exclusiveMinimum']) && \is_numeric($schema['exclusiveMinimum'])) {
                if ($data <= $schema['exclusiveMinimum']) {
                    $errors->addError(new ValidationError(
                        path: $path,
                        specReference: '#/schema/exclusiveMinimum',
                        constraint: 'exclusiveMinimum',
                        expectedValue: "> {$schema['exclusiveMinimum']}",
                        receivedValue: $data,
                        reason: \sprintf('Value %s is not greater than exclusiveMinimum %s', $data, $schema['exclusiveMinimum']),
                        hint: null
                    ));
                }
            }

            if (isset($schema['exclusiveMaximum']) && \is_numeric($schema['exclusiveMaximum'])) {
                if ($data >= $schema['exclusiveMaximum']) {
                    $errors->addError(new ValidationError(
                        path: $path,
                        specReference: '#/schema/exclusiveMaximum',
                        constraint: 'exclusiveMaximum',
                        expectedValue: "< {$schema['exclusiveMaximum']}",
                        receivedValue: $data,
                        reason: \sprintf('Value %s is not less than exclusiveMaximum %s', $data, $schema['exclusiveMaximum']),
                        hint: null
                    ));
                }
            }

            if (isset($schema['multipleOf']) && \is_numeric($schema['multipleOf'])) {
                $remainder = \fmod((float)$data, (float)$schema['multipleOf']);
                if (0.0 !== $remainder) {
                    $errors->addError(new ValidationError(
                        path: $path,
                        specReference: '#/schema/multipleOf',
                        constraint: 'multipleOf',
                        expectedValue: "multiple of {$schema['multipleOf']}",
                        receivedValue: $data,
                        reason: \sprintf('Value %s is not a multiple of %s', $data, $schema['multipleOf']),
                        hint: null
                    ));
                }
            }
        }

        // Array boundaries
        if (\is_array($data) && \array_is_list($data)) {
            $itemCount = \count($data);

            if (isset($schema['minItems']) && \is_int($schema['minItems'])) {
                if ($itemCount < $schema['minItems']) {
                    $errors->addError(new ValidationError(
                        path: $path,
                        specReference: '#/schema/minItems',
                        constraint: 'minItems',
                        expectedValue: "count >= {$schema['minItems']}",
                        receivedValue: $itemCount,
                        reason: \sprintf('Array has %d items, less than minItems %d', $itemCount, $schema['minItems']),
                        hint: null
                    ));
                }
            }

            if (isset($schema['maxItems']) && \is_int($schema['maxItems'])) {
                if ($itemCount > $schema['maxItems']) {
                    $errors->addError(new ValidationError(
                        path: $path,
                        specReference: '#/schema/maxItems',
                        constraint: 'maxItems',
                        expectedValue: "count <= {$schema['maxItems']}",
                        receivedValue: $itemCount,
                        reason: \sprintf('Array has %d items, exceeds maxItems %d', $itemCount, $schema['maxItems']),
                        hint: null
                    ));
                }
            }

            if (isset($schema['uniqueItems']) && true === $schema['uniqueItems']) {
                $unique = \array_unique($data, SORT_REGULAR);
                if (\count($unique) !== $itemCount) {
                    $errors->addError(new ValidationError(
                        path: $path,
                        specReference: '#/schema/uniqueItems',
                        constraint: 'uniqueItems',
                        expectedValue: 'all items unique',
                        receivedValue: 'duplicate items found',
                        reason: 'Array contains duplicate items',
                        hint: null
                    ));
                }
            }
        }
    }

    /**
     * Validate enum constraint.
     *
     * @param array<string, mixed> $schema
     */
    private static function validateEnum(mixed $data, array $schema, string $path, ErrorCollector $errors): void
    {
        if (!isset($schema['enum']) || !\is_array($schema['enum'])) {
            return;
        }

        // Empty enum array is a spec error, but we'll allow validation to continue
        if ([] === $schema['enum']) {
            return;
        }

        // Use strict comparison (no type coercion)
        // This handles null values correctly - if enum contains null, null will match
        if (!\in_array($data, $schema['enum'], true)) {
            // Format enum values for display
            $validValues = \array_map('json_encode', $schema['enum']);
            $validValuesStr = \implode(', ', $validValues);

            // Provide helpful hint for common mistakes
            $hint = self::getEnumHint($data, $schema['enum']);

            $errors->addError(new ValidationError(
                path: $path,
                specReference: '#/schema/enum',
                constraint: 'enum',
                expectedValue: "one of: {$validValuesStr}",
                receivedValue: \json_encode($data),
                reason: \sprintf('Value %s is not in enum. Valid values: %s', \json_encode($data), $validValuesStr),
                hint: $hint
            ));
        }
    }

    /**
     * Get helpful hint for enum validation failures.
     *
     * Provides suggestions for common mistakes:
     * - Case mismatch ("Active" vs "active")
     * - Type mismatch (1 vs "1")
     * - Similar values (typos)
     *
     * @param array<int, mixed> $enumValues
     */
    private static function getEnumHint(mixed $data, array $enumValues): ?string
    {
        // Check for case mismatch (only for strings)
        if (\is_string($data)) {
            foreach ($enumValues as $validValue) {
                if (\is_string($validValue) && \strcasecmp($data, $validValue) === 0) {
                    return "Did you mean '{$validValue}'? (case-sensitive comparison)";
                }
            }
        }

        // Check for type mismatch with string representation
        $dataStr = (string)$data;
        foreach ($enumValues as $validValue) {
            if (\is_string($validValue) && $validValue === $dataStr && !\is_string($data)) {
                return "Did you mean \"{$validValue}\" (string)? Received " . \get_debug_type($data);
            }
        }

        // Check for numeric type mismatch
        if (\is_numeric($data)) {
            foreach ($enumValues as $validValue) {
                if (\is_numeric($validValue) && (float)$data === (float)$validValue) {
                    $dataType = \get_debug_type($data);
                    $validType = \get_debug_type($validValue);
                    if ($dataType !== $validType) {
                        return "Did you mean {$validValue} ({$validType})? Received {$dataType}";
                    }
                }
            }
        }

        // Check for null vs empty string confusion
        if (null === $data && \in_array('', $enumValues, true)) {
            return 'Did you mean "" (empty string)? null is not in enum';
        }
        if ('' === $data && \in_array(null, $enumValues, true)) {
            return 'Did you mean null? Empty string is not in enum';
        }

        return null;
    }

    /**
     * Validate pattern constraint.
     *
     * @param array<string, mixed> $schema
     */
    private static function validatePattern(mixed $data, array $schema, string $path, ErrorCollector $errors): void
    {
        if (!isset($schema['pattern']) || !\is_string($data)) {
            return;
        }

        $pattern = $schema['pattern'];
        if (!\is_string($pattern)) {
            return;
        }

        // Test pattern match
        $result = @\preg_match('/' . \str_replace('/', '\\/', $pattern) . '/', $data);
        if (false === $result || 0 === $result) {
            $errors->addError(new ValidationError(
                path: $path,
                specReference: '#/schema/pattern',
                constraint: 'pattern',
                expectedValue: "pattern: {$pattern}",
                receivedValue: $data,
                reason: \sprintf('String does not match pattern: %s', $pattern),
                hint: null
            ));
        }
    }

    /**
     * Resolve $ref to actual schema.
     *
     * @param string $ref Reference like "#/components/schemas/User"
     * @param array<string, mixed> $spec Full OpenAPI spec
     * @return array<string, mixed>
     */
    private static function resolveRef(string $ref, array $spec): array
    {
        // Simple $ref resolution for #/components/schemas/SchemaName
        if (\str_starts_with($ref, '#/components/schemas/')) {
            $schemaName = \substr($ref, \strlen('#/components/schemas/'));

            if (!isset($spec['components']) || !\is_array($spec['components'])) {
                return [];
            }

            $components = $spec['components'];
            if (!isset($components['schemas']) || !\is_array($components['schemas'])) {
                return [];
            }

            $schemas = $components['schemas'];
            if (!isset($schemas[$schemaName])) {
                return [];
            }

            $schema = $schemas[$schemaName];
            if (!\is_array($schema)) {
                return [];
            }

            /** @var array<string, mixed> $schema */
            return $schema;
        }

        // Return empty schema if ref not found (will fail validation)
        return [];
    }

    /**
     * Get suggestion for unknown property (typo detection).
     *
     * @param array<int, string> $allowed
     */
    private static function getSuggestion(string $actual, array $allowed): ?string
    {
        $minDistance = PHP_INT_MAX;
        $suggestion = null;

        foreach ($allowed as $allowedProp) {
            if (!\is_string($allowedProp)) {
                continue;
            }

            $distance = \levenshtein($actual, $allowedProp);
            if ($distance < $minDistance && $distance <= 2) {
                $minDistance = $distance;
                $suggestion = $allowedProp;
            }
        }

        // Also check for snake_case/camelCase confusion
        $camelCase = \lcfirst(\str_replace('_', '', \ucwords($actual, '_')));
        if (\in_array($camelCase, $allowed, true)) {
            return "Did you mean '{$camelCase}'? (snake_case/camelCase confusion)";
        }

        if (null !== $suggestion) {
            return "Did you mean '{$suggestion}'?";
        }

        return null;
    }
}
