<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator;

use InvalidArgumentException;
use LogicException;
use LongTermSupport\StrictOpenApiValidator\Exception\InvalidSpecException;
use LongTermSupport\StrictOpenApiValidator\Exception\InvalidSpecVersionException;
use LongTermSupport\StrictOpenApiValidator\Exception\MissingRequiredSpecFieldException;
use LongTermSupport\StrictOpenApiValidator\Exception\ValidationError;

use function Safe\file_get_contents;
use function Safe\json_decode;

/**
 * Represents an OpenAPI specification loaded from a file or array.
 *
 * Validates OpenAPI 3.1.0 specifications on construction.
 */
final readonly class Spec
{
    /**
     * Valid HTTP methods in OpenAPI.
     */
    private const array VALID_HTTP_METHODS = [
        'get',
        'post',
        'put',
        'delete',
        'patch',
        'options',
        'head',
        'trace',
    ];

    /**
     * Valid JSON Schema types.
     */
    private const array VALID_SCHEMA_TYPES = [
        'string',
        'number',
        'integer',
        'boolean',
        'array',
        'object',
        'null',
    ];

    /**
     * Valid JSON Schema string formats.
     */
    private const array VALID_STRING_FORMATS = [
        'date',
        'date-time',
        'time',
        'duration',
        'email',
        'idn-email',
        'hostname',
        'idn-hostname',
        'ipv4',
        'ipv6',
        'uri',
        'uri-reference',
        'iri',
        'iri-reference',
        'uuid',
        'uri-template',
        'json-pointer',
        'relative-json-pointer',
        'regex',
        'password',
        'byte',
        'binary',
        'int32',
        'int64',
        'float',
        'double',
    ];

    /**
     * @param array<mixed> $spec The OpenAPI specification array
     * @param string $sourceFile The source file path (for error reporting)
     */
    private function __construct(
        private array $spec,
        private string $sourceFile,
    ) {
        $this->validateSpec();
    }

    /**
     * Create a Spec instance from a file.
     *
     * Supports JSON and YAML files (though YAML is not yet implemented).
     *
     * @param string $path Path to the OpenAPI specification file
     *
     * @return self
     *
     * @throws InvalidArgumentException If file doesn't exist or is invalid
     * @throws LogicException If YAML file provided (not yet implemented)
     */
    public static function createFromFile(string $path): self
    {
        if (!\file_exists($path)) {
            throw new InvalidArgumentException(\sprintf('File not found: %s', $path));
        }

        if (!\is_readable($path)) {
            throw new InvalidArgumentException(\sprintf('File is not readable: %s', $path));
        }

        $content = file_get_contents($path);

        // Determine file type by extension
        $extension = \strtolower(\pathinfo($path, PATHINFO_EXTENSION));

        if (\in_array($extension, ['yml', 'yaml'], true)) {
            throw new LogicException('YAML parsing not yet implemented. Use JSON for now.');
        }

        if ('json' !== $extension) {
            throw new InvalidArgumentException(\sprintf(
                'Unsupported file extension: %s. Expected .json, .yml, or .yaml',
                $extension
            ));
        }

        // Parse JSON
        /** @var array<mixed> $spec */
        $spec = json_decode($content, true);

        return new self($spec, $path);
    }

    /**
     * Create a Spec instance from an array.
     *
     * @param array<mixed> $spec The OpenAPI specification as an array
     *
     * @return self
     */
    public static function createFromArray(array $spec): self
    {
        return new self($spec, '<array>');
    }

    /**
     * Get the OpenAPI specification array.
     *
     * @return array<mixed>
     */
    public function getSpec(): array
    {
        return $this->spec;
    }

    /**
     * Get the source file path (or '<array>' if created from array).
     *
     * @return string
     */
    public function getSourceFile(): string
    {
        return $this->sourceFile;
    }

    /**
     * Validate the OpenAPI specification.
     *
     * Collects all errors before throwing to provide comprehensive validation feedback.
     *
     * @throws InvalidSpecVersionException If OpenAPI version is invalid
     * @throws MissingRequiredSpecFieldException If required fields are missing
     * @throws InvalidSpecException If spec structure is invalid
     */
    private function validateSpec(): void
    {
        $errors = [];

        // Validate OpenAPI version
        $this->validateOpenApiVersion($errors);

        // Validate required root fields
        $this->validateRequiredRootFields($errors);

        // Validate paths (if present)
        if (isset($this->spec['paths']) && \is_array($this->spec['paths'])) {
            $this->validatePaths($this->spec['paths'], $errors);
        }

        // Check that spec is not completely empty
        $this->validateSpecHasContent($errors);

        // Throw appropriate exception based on error types
        $this->throwIfErrors($errors);
    }

    /**
     * Validate OpenAPI version field.
     *
     * @param ValidationError[] $errors
     */
    private function validateOpenApiVersion(array &$errors): void
    {
        // Check for Swagger 2.0 first (before checking for missing openapi field)
        if (isset($this->spec['swagger']) && '2.0' === $this->spec['swagger']) {
            $errors[] = new ValidationError(
                path: '$.swagger',
                specReference: $this->sourceFile,
                constraint: 'version',
                expectedValue: '3.1.0',
                receivedValue: '2.0',
                reason: 'Swagger 2.0 is not supported',
                hint: 'This library only supports OpenAPI 3.1.0. For Swagger 2.0 specs, use a migration tool.'
            );
            return;
        }

        // Check if openapi field exists
        if (!isset($this->spec['openapi'])) {
            $errors[] = new ValidationError(
                path: '$.openapi',
                specReference: $this->sourceFile,
                constraint: 'required',
                expectedValue: '3.1.0',
                receivedValue: null,
                reason: 'Missing required field: openapi',
                hint: 'The "openapi" field is required and must be set to "3.1.0"'
            );
            return;
        }

        $version = $this->spec['openapi'];

        // Validate version format and value
        if (!\is_string($version)) {
            $errors[] = new ValidationError(
                path: '$.openapi',
                specReference: $this->sourceFile,
                constraint: 'type',
                expectedValue: 'string',
                receivedValue: \get_debug_type($version),
                reason: 'OpenAPI version must be a string',
                hint: 'Set the "openapi" field to "3.1.0"'
            );
            return;
        }

        // Validate version is exactly 3.1.x
        if (!\str_starts_with($version, '3.1.')) {
            $errors[] = new ValidationError(
                path: '$.openapi',
                specReference: $this->sourceFile,
                constraint: 'version',
                expectedValue: '3.1.x',
                receivedValue: $version,
                reason: 'OpenAPI version must be 3.1.x',
                hint: 'This library only supports OpenAPI 3.1.0. For 3.0.x specs, use a migration tool.'
            );
        }
    }

    /**
     * Validate required root fields.
     *
     * @param ValidationError[] $errors
     */
    private function validateRequiredRootFields(array &$errors): void
    {
        // Validate info object exists
        if (!isset($this->spec['info'])) {
            $errors[] = new ValidationError(
                path: '$.info',
                specReference: $this->sourceFile,
                constraint: 'required',
                expectedValue: 'object',
                receivedValue: null,
                reason: 'Missing required field: info',
                hint: 'The "info" object is required and must contain "title" and "version"'
            );
            return;
        }

        if (!\is_array($this->spec['info'])) {
            $errors[] = new ValidationError(
                path: '$.info',
                specReference: $this->sourceFile,
                constraint: 'type',
                expectedValue: 'object',
                receivedValue: \get_debug_type($this->spec['info']),
                reason: 'info must be an object',
                hint: 'The "info" field must be an object with "title" and "version"'
            );
            return;
        }

        $info = $this->spec['info'];

        // Validate info.title
        if (!isset($info['title'])) {
            $errors[] = new ValidationError(
                path: '$.info.title',
                specReference: $this->sourceFile,
                constraint: 'required',
                expectedValue: 'string',
                receivedValue: null,
                reason: 'Missing required field: info.title',
                hint: 'The "info.title" field is required'
            );
        } elseif (!\is_string($info['title'])) {
            $errors[] = new ValidationError(
                path: '$.info.title',
                specReference: $this->sourceFile,
                constraint: 'type',
                expectedValue: 'string',
                receivedValue: \get_debug_type($info['title']),
                reason: 'info.title must be a string'
            );
        }

        // Validate info.version
        if (!isset($info['version'])) {
            $errors[] = new ValidationError(
                path: '$.info.version',
                specReference: $this->sourceFile,
                constraint: 'required',
                expectedValue: 'string',
                receivedValue: null,
                reason: 'Missing required field: info.version',
                hint: 'The "info.version" field is required'
            );
        } elseif (!\is_string($info['version'])) {
            $errors[] = new ValidationError(
                path: '$.info.version',
                specReference: $this->sourceFile,
                constraint: 'type',
                expectedValue: 'string',
                receivedValue: \get_debug_type($info['version']),
                reason: 'info.version must be a string'
            );
        }
    }

    /**
     * Validate that spec has at least one of: paths, components, or webhooks.
     *
     * @param ValidationError[] $errors
     */
    private function validateSpecHasContent(array &$errors): void
    {
        // Empty paths object is valid - it just means no endpoints yet
        $hasPaths = isset($this->spec['paths']) && \is_array($this->spec['paths']);
        $hasComponents = isset($this->spec['components']) && \is_array($this->spec['components']);
        $hasWebhooks = isset($this->spec['webhooks']) && \is_array($this->spec['webhooks']);

        if (!$hasPaths && !$hasComponents && !$hasWebhooks) {
            $errors[] = new ValidationError(
                path: '$',
                specReference: $this->sourceFile,
                constraint: 'required',
                expectedValue: 'paths, components, or webhooks',
                receivedValue: 'empty spec',
                reason: 'Spec must have at least one of: paths, components, or webhooks',
                hint: 'Add a "paths" object to define API endpoints'
            );
        }
    }

    /**
     * Validate paths object.
     *
     * @param array<mixed> $paths
     * @param ValidationError[] $errors
     */
    private function validatePaths(array $paths, array &$errors): void
    {
        $operationIds = [];

        foreach ($paths as $path => $pathItem) {
            if (!\is_string($path)) {
                continue;
            }

            // Validate path format
            if (!\str_starts_with($path, '/')) {
                $errors[] = new ValidationError(
                    path: "$.paths[{$path}]",
                    specReference: $this->sourceFile,
                    constraint: 'format',
                    expectedValue: 'path starting with /',
                    receivedValue: $path,
                    reason: 'Path must start with /',
                    hint: 'All paths must start with a forward slash (e.g., "/users")'
                );
            }

            if (!\is_array($pathItem)) {
                continue;
            }

            // Extract path parameters from template
            \preg_match_all('/\{([^}]+)\}/', $path, $matches);
            $pathParams = $matches[1];

            // Get path-level parameters
            $pathLevelParams = [];
            if (isset($pathItem['parameters']) && \is_array($pathItem['parameters'])) {
                foreach ($pathItem['parameters'] as $param) {
                    if (\is_array($param) && isset($param['name']) && isset($param['in']) && 'path' === $param['in']) {
                        $pathLevelParams[] = $param['name'];
                    }
                }
            }

            // Validate operations
            foreach ($pathItem as $method => $operation) {
                if ('parameters' === $method || '$ref' === $method) {
                    continue;
                }

                if (!\is_string($method)) {
                    continue;
                }

                // Validate HTTP method
                if (!\in_array(\strtolower($method), self::VALID_HTTP_METHODS, true)) {
                    $errors[] = new ValidationError(
                        path: "$.paths[{$path}].{$method}",
                        specReference: $this->sourceFile,
                        constraint: 'method',
                        expectedValue: \implode(', ', self::VALID_HTTP_METHODS),
                        receivedValue: $method,
                        reason: 'Invalid HTTP method',
                        hint: 'Valid methods are: ' . \implode(', ', self::VALID_HTTP_METHODS)
                    );
                    continue;
                }

                if (!\is_array($operation)) {
                    continue;
                }

                // Validate operationId uniqueness
                if (isset($operation['operationId'])) {
                    $opId = $operation['operationId'];
                    if (!\is_string($opId)) {
                        throw new LogicException('operationId must be a string, got: ' . \get_debug_type($opId));
                    }
                    if (\in_array($opId, $operationIds, true)) {
                        $errors[] = new ValidationError(
                            path: "$.paths[{$path}].{$method}.operationId",
                            specReference: $this->sourceFile,
                            constraint: 'unique',
                            expectedValue: 'unique operationId',
                            receivedValue: $opId,
                            reason: 'Duplicate operationId',
                            hint: "operationId '{$opId}' is already used elsewhere in the spec"
                        );
                    } else {
                        $operationIds[] = $opId;
                    }
                }

                // Validate responses exist
                if (!isset($operation['responses'])) {
                    $errors[] = new ValidationError(
                        path: "$.paths[{$path}].{$method}.responses",
                        specReference: $this->sourceFile,
                        constraint: 'required',
                        expectedValue: 'responses object',
                        receivedValue: null,
                        reason: 'Missing required field: responses',
                        hint: 'Every operation must define at least one response'
                    );
                    continue;
                }

                if (!\is_array($operation['responses'])) {
                    continue;
                }

                // Validate responses is not empty
                if ([] === $operation['responses']) {
                    $errors[] = new ValidationError(
                        path: "$.paths[{$path}].{$method}.responses",
                        specReference: $this->sourceFile,
                        constraint: 'required',
                        expectedValue: 'at least one response',
                        receivedValue: 'empty object',
                        reason: 'Responses object cannot be empty',
                        hint: 'Define at least one response (e.g., 200, 404)'
                    );
                }

                // Validate each response has description
                foreach ($operation['responses'] as $statusCode => $response) {
                    if (!\is_array($response)) {
                        continue;
                    }

                    if (!isset($response['description'])) {
                        $errors[] = new ValidationError(
                            path: "$.paths[{$path}].{$method}.responses[{$statusCode}].description",
                            specReference: $this->sourceFile,
                            constraint: 'required',
                            expectedValue: 'description',
                            receivedValue: null,
                            reason: 'Missing required field: description',
                            hint: 'Every response must have a description'
                        );
                    }
                }

                // Validate path parameters are defined
                $operationParams = $pathLevelParams;
                if (isset($operation['parameters']) && \is_array($operation['parameters'])) {
                    foreach ($operation['parameters'] as $param) {
                        if (\is_array($param) && isset($param['name']) && isset($param['in']) && 'path' === $param['in']) {
                            $operationParams[] = $param['name'];
                        }
                    }
                }

                // Check all path parameters are defined
                foreach ($pathParams as $paramName) {
                    if (!\in_array($paramName, $operationParams, true)) {
                        $errors[] = new ValidationError(
                            path: "$.paths[{$path}].{$method}.parameters",
                            specReference: $this->sourceFile,
                            constraint: 'required',
                            expectedValue: "parameter definition for {$paramName}",
                            receivedValue: 'missing',
                            reason: "Path parameter {$paramName} is not defined",
                            hint: "Add a parameter definition with name='{$paramName}' and in='path'"
                        );
                    }
                }

                // Validate request body schema (if present)
                if (isset($operation['requestBody']) && \is_array($operation['requestBody'])
                    && isset($operation['requestBody']['content']) && \is_array($operation['requestBody']['content'])) {
                    foreach ($operation['requestBody']['content'] as $mediaType => $mediaTypeObj) {
                        if (!\is_array($mediaTypeObj)) {
                            continue;
                        }
                        if (isset($mediaTypeObj['schema']) && \is_array($mediaTypeObj['schema'])) {
                            $this->validateSchema($mediaTypeObj['schema'], "$.paths[{$path}].{$method}.requestBody.content[{$mediaType}].schema", $errors);
                        }
                    }
                }

                // Validate response schemas
                foreach ($operation['responses'] as $statusCode => $response) {
                    if (!\is_array($response)) {
                        continue;
                    }
                    if (!isset($response['content']) || !\is_array($response['content'])) {
                        continue;
                    }

                    foreach ($response['content'] as $mediaType => $mediaTypeObj) {
                        if (!\is_array($mediaTypeObj)) {
                            continue;
                        }
                        if (isset($mediaTypeObj['schema']) && \is_array($mediaTypeObj['schema'])) {
                            $this->validateSchema($mediaTypeObj['schema'], "$.paths[{$path}].{$method}.responses[{$statusCode}].content[{$mediaType}].schema", $errors);
                        }
                    }
                }
            }
        }

        // Also validate component schemas if present
        if (isset($this->spec['components']) && \is_array($this->spec['components'])
            && isset($this->spec['components']['schemas']) && \is_array($this->spec['components']['schemas'])) {
            foreach ($this->spec['components']['schemas'] as $schemaName => $schema) {
                if (\is_array($schema)) {
                    $this->validateSchema($schema, "$.components.schemas[{$schemaName}]", $errors);
                }
            }
        }
    }

    /**
     * Validate a JSON schema.
     *
     * @param array<mixed> $schema
     * @param string $path
     * @param ValidationError[] $errors
     */
    private function validateSchema(array $schema, string $path, array &$errors): void
    {
        // Validate type (if present)
        if (isset($schema['type'])) {
            $type = $schema['type'];
            if (\is_string($type) && !\in_array($type, self::VALID_SCHEMA_TYPES, true)) {
                $errors[] = new ValidationError(
                    path: "{$path}.type",
                    specReference: $this->sourceFile,
                    constraint: 'type',
                    expectedValue: \implode(', ', self::VALID_SCHEMA_TYPES),
                    receivedValue: $type,
                    reason: 'Invalid schema type',
                    hint: 'Valid types are: ' . \implode(', ', self::VALID_SCHEMA_TYPES)
                );
            }
        }

        // Validate format (if present)
        if (isset($schema['format']) && \is_string($schema['format'])) {
            $format = $schema['format'];
            if (!\in_array($format, self::VALID_STRING_FORMATS, true)) {
                $errors[] = new ValidationError(
                    path: "{$path}.format",
                    specReference: $this->sourceFile,
                    constraint: 'format',
                    expectedValue: \implode(', ', self::VALID_STRING_FORMATS),
                    receivedValue: $format,
                    reason: 'Invalid format',
                    hint: 'Valid formats include: email, date-time, uri, uuid, etc.'
                );
            }
        }

        // Validate min/max constraints
        if (isset($schema['minimum'], $schema['maximum'])) {
            $min = $schema['minimum'];
            $max = $schema['maximum'];
            if (\is_numeric($min) && \is_numeric($max) && $min > $max) {
                $errors[] = new ValidationError(
                    path: "{$path}.minimum",
                    specReference: $this->sourceFile,
                    constraint: 'minimum',
                    expectedValue: "minimum <= maximum (maximum is {$max})",
                    receivedValue: $min,
                    reason: 'minimum must be <= maximum',
                    hint: "Adjust minimum ({$min}) to be less than or equal to maximum ({$max})"
                );
            }
        }

        // Validate minLength/maxLength constraints
        if (isset($schema['minLength'], $schema['maxLength'])) {
            $minLen = $schema['minLength'];
            $maxLen = $schema['maxLength'];
            if (\is_int($minLen) && \is_int($maxLen) && $minLen > $maxLen) {
                $errors[] = new ValidationError(
                    path: "{$path}.minLength",
                    specReference: $this->sourceFile,
                    constraint: 'minLength',
                    expectedValue: "minLength <= maxLength (maxLength is {$maxLen})",
                    receivedValue: $minLen,
                    reason: 'minLength must be <= maxLength',
                    hint: "Adjust minLength ({$minLen}) to be less than or equal to maxLength ({$maxLen})"
                );
            }
        }

        // Validate pattern (if present)
        if (isset($schema['pattern']) && \is_string($schema['pattern'])) {
            $pattern = $schema['pattern'];
            // Test if pattern is valid regex
            $result = @\preg_match('/' . \str_replace('/', '\\/', $pattern) . '/', '');
            if (false === $result) {
                $errors[] = new ValidationError(
                    path: "{$path}.pattern",
                    specReference: $this->sourceFile,
                    constraint: 'pattern',
                    expectedValue: 'valid ECMA-262 regex',
                    receivedValue: $pattern,
                    reason: 'Invalid regex pattern',
                    hint: 'Ensure the pattern is a valid regular expression'
                );
            }
        }

        // Recursively validate nested schemas
        if (isset($schema['properties']) && \is_array($schema['properties'])) {
            foreach ($schema['properties'] as $propName => $propSchema) {
                if (\is_array($propSchema)) {
                    $this->validateSchema($propSchema, "{$path}.properties[{$propName}]", $errors);
                }
            }
        }

        if (isset($schema['items']) && \is_array($schema['items'])) {
            $this->validateSchema($schema['items'], "{$path}.items", $errors);
        }

        if (isset($schema['additionalProperties']) && \is_array($schema['additionalProperties'])) {
            $this->validateSchema($schema['additionalProperties'], "{$path}.additionalProperties", $errors);
        }
    }

    /**
     * Throw appropriate exception if errors exist.
     *
     * @param ValidationError[] $errors
     */
    private function throwIfErrors(array $errors): void
    {
        if ([] === $errors) {
            return;
        }

        // Determine which exception to throw based on error types
        $hasVersionError = false;
        $hasMissingField = false;

        foreach ($errors as $error) {
            // Version errors include both openapi version and swagger version
            if ('version' === $error->constraint) {
                $hasVersionError = true;
            }
            if ('required' === $error->constraint) {
                $hasMissingField = true;
            }
        }

        // Priority: version errors > missing field errors > generic spec errors
        if ($hasVersionError) {
            throw new InvalidSpecVersionException($errors);
        }

        if ($hasMissingField) {
            throw new MissingRequiredSpecFieldException($errors);
        }

        throw new InvalidSpecException($errors);
    }
}
