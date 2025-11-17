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

        // Composition validation (oneOf, anyOf, allOf)
        if (isset($schema['oneOf'])) {
            self::validateOneOf($data, $schema, $path, $spec, $errors);
        }
        if (isset($schema['anyOf'])) {
            self::validateAnyOf($data, $schema, $path, $spec, $errors);
        }
        if (isset($schema['allOf'])) {
            self::validateAllOf($data, $schema, $path, $spec, $errors);
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

    /**
     * Validate oneOf constraint.
     *
     * Data must match EXACTLY ONE schema from the list.
     * If discriminator is present, use it to select schema.
     *
     * @param array<string, mixed> $schema
     * @param array<string, mixed> $spec
     */
    private static function validateOneOf(mixed $data, array $schema, string $path, array $spec, ErrorCollector $errors): void
    {
        if (!\is_array($schema['oneOf']) || [] === $schema['oneOf']) {
            return;
        }

        // Handle discriminator if present and can be used
        if (isset($schema['discriminator']) && \is_array($schema['discriminator'])) {
            $discriminatorUsed = self::handleDiscriminator($data, $schema, $path, $spec, $errors);

            // If discriminator was successfully used, we're done
            if ($discriminatorUsed) {
                return;
            }
            // Otherwise, fall through to regular oneOf validation
        }

        // Try each schema and collect matches/errors
        $matchCount = 0;
        $allSchemaErrors = [];

        foreach ($schema['oneOf'] as $index => $subSchema) {
            if (!\is_array($subSchema)) {
                continue;
            }

            // Resolve $ref if present
            if (isset($subSchema['$ref']) && \is_string($subSchema['$ref'])) {
                $subSchema = self::resolveRef($subSchema['$ref'], $spec);
            }

            // Create temporary error collector for this schema
            $schemaErrors = new ErrorCollector();
            self::validate($data, $subSchema, $path, $schemaErrors, $spec);

            if (!$schemaErrors->hasErrors()) {
                $matchCount++;
            } else {
                $allSchemaErrors[$index] = $schemaErrors->getErrors();
            }
        }

        // oneOf requires EXACTLY one match
        if (0 === $matchCount) {
            $errors->addError(new ValidationError(
                path: $path,
                specReference: '#/schema/oneOf',
                constraint: 'oneOf',
                expectedValue: 'data must match exactly one schema',
                receivedValue: 'matches none',
                reason: \sprintf('oneOf validation failed: data matches 0 schemas (must match exactly 1)'),
                hint: 'Check that data conforms to at least one of the allowed schemas'
            ));
        } elseif ($matchCount > 1) {
            $errors->addError(new ValidationError(
                path: $path,
                specReference: '#/schema/oneOf',
                constraint: 'oneOf',
                expectedValue: 'data must match exactly one schema',
                receivedValue: \sprintf('matches %d schemas', $matchCount),
                reason: \sprintf('oneOf validation failed: data matches %d schemas (must match exactly 1)', $matchCount),
                hint: 'Data is ambiguous - it matches multiple schemas. Consider adding a discriminator field.'
            ));
        }
    }

    /**
     * Validate anyOf constraint.
     *
     * Data must match AT LEAST ONE schema from the list.
     *
     * @param array<string, mixed> $schema
     * @param array<string, mixed> $spec
     */
    private static function validateAnyOf(mixed $data, array $schema, string $path, array $spec, ErrorCollector $errors): void
    {
        if (!\is_array($schema['anyOf']) || [] === $schema['anyOf']) {
            return;
        }

        // Try each schema
        $matchFound = false;

        foreach ($schema['anyOf'] as $subSchema) {
            if (!\is_array($subSchema)) {
                continue;
            }

            // Resolve $ref if present
            if (isset($subSchema['$ref']) && \is_string($subSchema['$ref'])) {
                $subSchema = self::resolveRef($subSchema['$ref'], $spec);
            }

            // Create temporary error collector for this schema
            $schemaErrors = new ErrorCollector();
            self::validate($data, $subSchema, $path, $schemaErrors, $spec);

            if (!$schemaErrors->hasErrors()) {
                $matchFound = true;
                break; // At least one match is enough for anyOf
            }
        }

        // anyOf requires at least one match
        if (!$matchFound) {
            $errors->addError(new ValidationError(
                path: $path,
                specReference: '#/schema/anyOf',
                constraint: 'anyOf',
                expectedValue: 'data must match at least one schema',
                receivedValue: 'matches none',
                reason: 'anyOf validation failed: data matches 0 schemas (must match at least 1)',
                hint: 'Check that data conforms to at least one of the allowed schemas'
            ));
        }
    }

    /**
     * Validate allOf constraint.
     *
     * Data must match ALL schemas from the list.
     *
     * @param array<string, mixed> $schema
     * @param array<string, mixed> $spec
     */
    private static function validateAllOf(mixed $data, array $schema, string $path, array $spec, ErrorCollector $errors): void
    {
        if (!\is_array($schema['allOf']) || [] === $schema['allOf']) {
            return;
        }

        // Validate against each schema
        foreach ($schema['allOf'] as $subSchema) {
            if (!\is_array($subSchema)) {
                continue;
            }

            // Resolve $ref if present
            if (isset($subSchema['$ref']) && \is_string($subSchema['$ref'])) {
                $subSchema = self::resolveRef($subSchema['$ref'], $spec);
            }

            // Validate against this schema (errors added directly to main collector)
            self::validate($data, $subSchema, $path, $errors, $spec);
        }

        // No special error handling needed - if any schema fails, errors are already collected
    }

    /**
     * Handle discriminator-based schema selection for oneOf/anyOf.
     *
     * When a discriminator is present, use it to determine which schema to validate against.
     *
     * @param array<string, mixed> $schema
     * @param array<string, mixed> $spec
     * @return bool True if discriminator was successfully used, false if it should fall back to regular validation
     */
    private static function handleDiscriminator(mixed $data, array $schema, string $path, array $spec, ErrorCollector $errors): bool
    {
        $discriminator = $schema['discriminator'];

        // Get discriminator property name
        if (!isset($discriminator['propertyName']) || !\is_string($discriminator['propertyName'])) {
            return false;  // Invalid discriminator config - fall back to regular validation
        }

        $propertyName = $discriminator['propertyName'];

        // Data must be an object with the discriminator property
        if (!\is_array($data) || \array_is_list($data)) {
            // Not an object - fall back to regular validation which will catch the type error
            return false;
        }

        // Check if discriminator property exists
        if (!\array_key_exists($propertyName, $data)) {
            // Missing discriminator field - fall back to regular validation
            // The regular validation will catch this as either:
            // - required field missing (if petType is required in the individual schemas)
            // - oneOf/anyOf matches none/multiple
            return false;
        }

        $discriminatorValue = $data[$propertyName];

        // Discriminator value must be a string
        if (!\is_string($discriminatorValue)) {
            $errors->addError(new ValidationError(
                path: "{$path}.{$propertyName}",
                specReference: '#/schema/discriminator',
                constraint: 'discriminator',
                expectedValue: 'string',
                receivedValue: \get_debug_type($discriminatorValue),
                reason: \sprintf('Discriminator field "%s" must be a string, got %s', $propertyName, \get_debug_type($discriminatorValue)),
                hint: null
            ));
            return true;  // We used discriminator (and found wrong type)
        }

        // Get mapping (if present)
        $mapping = isset($discriminator['mapping']) && \is_array($discriminator['mapping'])
            ? $discriminator['mapping']
            : [];

        // Find schema reference from mapping
        // If discriminator value is not in mapping, fall back to regular oneOf/anyOf validation
        if ([] !== $mapping && !isset($mapping[$discriminatorValue])) {
            // Don't add error here - let oneOf/anyOf handle it without discriminator optimization
            return false;
        }

        // Get schema reference
        $schemaRef = $mapping[$discriminatorValue] ?? null;

        if (null === $schemaRef || !\is_string($schemaRef)) {
            // No valid schema reference - fall back to regular validation
            return false;
        }

        // Resolve and validate against the selected schema
        $selectedSchema = self::resolveRef($schemaRef, $spec);

        if ([] === $selectedSchema) {
            // Could not resolve schema - fall back to regular validation
            return false;
        }

        // Validate against the selected schema
        self::validate($data, $selectedSchema, $path, $errors, $spec);

        // Successfully used discriminator
        return true;
    }
}
