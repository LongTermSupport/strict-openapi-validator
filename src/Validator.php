<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator;

use LongTermSupport\StrictOpenApiValidator\Exception\AdditionalPropertyException;
use LongTermSupport\StrictOpenApiValidator\Exception\BoundaryViolationException;
use LongTermSupport\StrictOpenApiValidator\Exception\CompositionViolationException;
use LongTermSupport\StrictOpenApiValidator\Exception\DiscriminatorViolationException;
use LongTermSupport\StrictOpenApiValidator\Exception\EnumViolationException;
use LongTermSupport\StrictOpenApiValidator\Exception\FormatViolationException;
use LongTermSupport\StrictOpenApiValidator\Exception\InvalidRequestPathException;
use LongTermSupport\StrictOpenApiValidator\Exception\InvalidResponseStatusException;
use LongTermSupport\StrictOpenApiValidator\Exception\PatternViolationException;
use LongTermSupport\StrictOpenApiValidator\Exception\RequiredFieldMissingException;
use LongTermSupport\StrictOpenApiValidator\Exception\SchemaViolationException;
use LongTermSupport\StrictOpenApiValidator\Exception\TypeMismatchException;
use LongTermSupport\StrictOpenApiValidator\Exception\ValidationError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates HTTP requests and responses against OpenAPI specifications.
 *
 * Phase 2 Implementation: Schema validation core with:
 * - Type validation (strict, no coercion)
 * - Required field validation
 * - Additional properties validation
 * - Format validation
 * - Boundary constraints
 */
final readonly class Validator
{
    /**
     * Validate a JSON request string against the OpenAPI spec.
     *
     * Phase 5: Supports both backward-compatible (first schema) and path/method matching.
     *
     * @param string $json JSON request body to validate
     * @param Spec $spec OpenAPI specification to validate against
     * @param string $path Request path (e.g., "/users", "/users/{id}") - empty uses first schema
     * @param string $method HTTP method (e.g., "post", "get") - empty uses first schema
     *
     * @return void
     * @throws TypeMismatchException When type validation fails
     * @throws RequiredFieldMissingException When required fields are missing
     * @throws AdditionalPropertyException When additional properties are not allowed
     * @throws FormatViolationException When format validation fails
     * @throws BoundaryViolationException When boundary constraints are violated
     * @throws PatternViolationException When pattern validation fails
     * @throws SchemaViolationException When multiple validation errors occur
     * @throws InvalidRequestPathException When path/method not found in spec
     */
    public static function validateRequest(string $json, Spec $spec, string $path = '', string $method = ''): void
    {
        // Parse JSON (returns mixed, validated before use)
        $data = \Safe\json_decode($json, true);

        // Backward compatibility: if path/method not provided, use first schema
        if ('' === $path || '' === $method) {
            $schema = self::findFirstRequestBodySchema($spec);
        } else {
            // New behavior: find exact schema for path/method
            $schema = self::findRequestBodySchema($spec, $path, $method);
        }

        if ([] === $schema) {
            // No schema found - nothing to validate
            return;
        }

        // Validate data against schema
        $errors = new ErrorCollector();
        SchemaValidator::validate($data, $schema, '$', $errors, $spec->getSpec());

        // Throw appropriate exception based on error types
        self::throwIfErrors($errors);
    }

    /**
     * Validate a JSON response string against the OpenAPI spec.
     *
     * Phase 5: Supports both backward-compatible (first schema) and path/method/status matching.
     *
     * @param string $json JSON response body to validate
     * @param Spec $spec OpenAPI specification to validate against
     * @param string $path Response path (e.g., "/users/{id}") - empty uses first schema
     * @param string $method HTTP method (e.g., "get", "post") - empty uses first schema
     * @param int $statusCode HTTP status code (e.g., 200, 404) - 0 uses first schema
     *
     * @return void
     * @throws TypeMismatchException When type validation fails
     * @throws RequiredFieldMissingException When required fields are missing
     * @throws AdditionalPropertyException When additional properties are not allowed
     * @throws FormatViolationException When format validation fails
     * @throws BoundaryViolationException When boundary constraints are violated
     * @throws PatternViolationException When pattern validation fails
     * @throws SchemaViolationException When multiple validation errors occur
     * @throws InvalidResponseStatusException When status code not found in spec
     */
    public static function validateResponse(string $json, Spec $spec, string $path = '', string $method = '', int $statusCode = 0): void
    {
        // Parse JSON (returns mixed, validated before use)
        $data = \Safe\json_decode($json, true);

        // Backward compatibility: if path/method/status not provided, use first schema
        if ('' === $path || '' === $method || 0 === $statusCode) {
            $schema = self::findFirstResponseSchema($spec);
        } else {
            // New behavior: find exact schema for path/method/status
            $schema = self::findResponseSchema($spec, $path, $method, $statusCode);
        }

        if ([] === $schema) {
            // No schema found - nothing to validate
            return;
        }

        // Validate data against schema
        $errors = new ErrorCollector();
        SchemaValidator::validate($data, $schema, '$', $errors, $spec->getSpec());

        // Throw appropriate exception based on error types
        self::throwIfErrors($errors);
    }

    /**
     * Validate a Symfony Request object against the OpenAPI spec.
     *
     * Phase 6: Extracts data from Symfony Request and delegates to validateRequest().
     *
     * @param Request $request Symfony Request object to validate
     * @param Spec $spec OpenAPI specification to validate against
     *
     * @return void
     * @throws TypeMismatchException When type validation fails
     * @throws RequiredFieldMissingException When required fields are missing
     * @throws AdditionalPropertyException When additional properties are not allowed
     * @throws FormatViolationException When format validation fails
     * @throws BoundaryViolationException When boundary constraints are violated
     * @throws PatternViolationException When pattern validation fails
     * @throws SchemaViolationException When multiple validation errors occur
     * @throws InvalidRequestPathException When path/method not found in spec
     */
    public static function validate(Request $request, Spec $spec): void
    {
        // Extract path from request
        $path = $request->getPathInfo();

        // Extract method from request (lowercase to match spec keys)
        $method = \strtolower($request->getMethod());

        // Extract JSON body from request (empty string for GET requests)
        $json = $request->getContent();
        if (false === $json) {
            $json = '';
        }

        // Handle empty content (GET requests, etc.)
        if ('' === $json) {
            $json = '{}';
        }

        // Delegate to existing validateRequest() method
        self::validateRequest($json, $spec, $path, $method);
    }

    /**
     * Validate a Symfony Response object against the OpenAPI spec.
     *
     * Phase 6: Extracts data from Symfony Response and delegates to validateResponse().
     *
     * Note: Response objects don't contain path/method information, so these must be
     * provided by the caller. If not provided, falls back to first response schema.
     *
     * @param Response $response Symfony Response object to validate
     * @param Spec $spec OpenAPI specification to validate against
     * @param string $path Response path (e.g., "/users/{id}") - empty uses first schema
     * @param string $method HTTP method (e.g., "get", "post") - empty uses first schema
     *
     * @return void
     * @throws TypeMismatchException When type validation fails
     * @throws RequiredFieldMissingException When required fields are missing
     * @throws AdditionalPropertyException When additional properties are not allowed
     * @throws FormatViolationException When format validation fails
     * @throws BoundaryViolationException When boundary constraints are violated
     * @throws PatternViolationException When pattern validation fails
     * @throws SchemaViolationException When multiple validation errors occur
     * @throws InvalidResponseStatusException When status code not found in spec
     */
    public static function validateSymfonyResponse(
        Response $response,
        Spec $spec,
        string $path = '',
        string $method = ''
    ): void {
        // Extract status code from response
        $statusCode = $response->getStatusCode();

        // Extract JSON body from response (empty string for no content responses)
        $json = $response->getContent();
        if (false === $json) {
            $json = '';
        }

        // Handle empty content
        if ('' === $json) {
            $json = '{}';
        }

        // Delegate to existing validateResponse() method
        self::validateResponse($json, $spec, $path, $method, $statusCode);
    }

    /**
     * Find first request body schema in the spec.
     *
     * Phase 2 simplification: Returns first requestBody schema found.
     * Phase 5 will add proper path/method matching.
     *
     * @return array<string, mixed>
     */
    private static function findFirstRequestBodySchema(Spec $spec): array
    {
        $specArray = $spec->getSpec();

        if (!isset($specArray['paths']) || !\is_array($specArray['paths'])) {
            return [];
        }

        foreach ($specArray['paths'] as $path => $pathItem) {
            if (!\is_array($pathItem)) {
                continue;
            }

            foreach ($pathItem as $method => $operation) {
                if (!\is_array($operation)) {
                    continue;
                }

                if (!isset($operation['requestBody']) || !\is_array($operation['requestBody'])) {
                    continue;
                }

                $requestBody = $operation['requestBody'];
                if (!isset($requestBody['content']) || !\is_array($requestBody['content'])) {
                    continue;
                }

                $content = $requestBody['content'];
                if (!isset($content['application/json']) || !\is_array($content['application/json'])) {
                    continue;
                }

                $jsonContent = $content['application/json'];
                if (!isset($jsonContent['schema']) || !\is_array($jsonContent['schema'])) {
                    continue;
                }

                /** @var array<string, mixed> $schema */
                $schema = $jsonContent['schema'];
                return $schema;
            }
        }

        return [];
    }

    /**
     * Find first response schema in the spec.
     *
     * Phase 2 simplification: Returns first response schema found.
     * Phase 5 will add proper path/method/status matching.
     *
     * @return array<string, mixed>
     */
    private static function findFirstResponseSchema(Spec $spec): array
    {
        $specArray = $spec->getSpec();

        if (!isset($specArray['paths']) || !\is_array($specArray['paths'])) {
            return [];
        }

        foreach ($specArray['paths'] as $path => $pathItem) {
            if (!\is_array($pathItem)) {
                continue;
            }

            foreach ($pathItem as $method => $operation) {
                if (!\is_array($operation)) {
                    continue;
                }

                if (!isset($operation['responses']) || !\is_array($operation['responses'])) {
                    continue;
                }

                foreach ($operation['responses'] as $statusCode => $response) {
                    if (!\is_array($response)) {
                        continue;
                    }

                    if (!isset($response['content']) || !\is_array($response['content'])) {
                        continue;
                    }

                    $content = $response['content'];
                    if (!isset($content['application/json']) || !\is_array($content['application/json'])) {
                        continue;
                    }

                    $jsonContent = $content['application/json'];
                    if (!isset($jsonContent['schema']) || !\is_array($jsonContent['schema'])) {
                        continue;
                    }

                    /** @var array<string, mixed> $schema */
                    $schema = $jsonContent['schema'];
                    return $schema;
                }
            }
        }

        return [];
    }

    /**
     * Find request body schema for specific path and method.
     *
     * Phase 5: Proper path/method matching with support for path parameters.
     *
     * @param Spec $spec OpenAPI specification
     * @param string $path Request path (e.g., "/users", "/users/123")
     * @param string $method HTTP method (lowercase, e.g., "post", "get")
     *
     * @return array<string, mixed>
     * @throws InvalidRequestPathException When path/method not found in spec
     */
    private static function findRequestBodySchema(Spec $spec, string $path, string $method): array
    {
        $specArray = $spec->getSpec();

        if (!isset($specArray['paths']) || !\is_array($specArray['paths'])) {
            throw new InvalidRequestPathException([
                new ValidationError(
                    path: '$.path',
                    specReference: '#/paths',
                    constraint: 'path',
                    expectedValue: 'valid path in spec',
                    receivedValue: $path,
                    reason: 'No paths defined in OpenAPI spec',
                    hint: 'OpenAPI spec must define at least one path'
                ),
            ]);
        }

        // Normalize method to lowercase
        $method = \strtolower($method);

        // Try exact path match first
        if (isset($specArray['paths'][$path])) {
            $pathItem = $specArray['paths'][$path];
            if (\is_array($pathItem) && isset($pathItem[$method])) {
                $operation = $pathItem[$method];
                if (\is_array($operation)) {
                    $schema = self::extractRequestBodySchema($operation);
                    if ([] !== $schema) {
                        return $schema;
                    }
                }
            }
        }

        // Try path parameter matching (e.g., /users/{id} matches /users/123)
        foreach ($specArray['paths'] as $specPath => $pathItem) {
            if (!\is_array($pathItem)) {
                continue;
            }

            // Convert path template to regex
            if (self::pathMatches($specPath, $path)) {
                if (isset($pathItem[$method])) {
                    $operation = $pathItem[$method];
                    if (\is_array($operation)) {
                        $schema = self::extractRequestBodySchema($operation);
                        if ([] !== $schema) {
                            return $schema;
                        }
                    }
                }
            }
        }

        // Path/method not found - throw exception with helpful message
        $availablePaths = \array_keys($specArray['paths']);
        throw new InvalidRequestPathException([
            new ValidationError(
                path: '$.path',
                specReference: '#/paths',
                constraint: 'path',
                expectedValue: 'valid path in spec',
                receivedValue: $path . ' [' . $method . ']',
                reason: \sprintf('Path "%s" with method "%s" not found in OpenAPI spec', $path, $method),
                hint: 'Available paths: ' . \implode(', ', $availablePaths)
            ),
        ]);
    }

    /**
     * Find response schema for specific path, method, and status code.
     *
     * Phase 5: Proper path/method/status matching with fallback to 'default'.
     *
     * @param Spec $spec OpenAPI specification
     * @param string $path Response path (e.g., "/users/{id}")
     * @param string $method HTTP method (lowercase, e.g., "get", "post")
     * @param int $statusCode HTTP status code (e.g., 200, 404)
     *
     * @return array<string, mixed>
     * @throws InvalidResponseStatusException When status code not found in spec
     */
    private static function findResponseSchema(Spec $spec, string $path, string $method, int $statusCode): array
    {
        $specArray = $spec->getSpec();

        if (!isset($specArray['paths']) || !\is_array($specArray['paths'])) {
            throw new InvalidResponseStatusException([
                new ValidationError(
                    path: '$.path',
                    specReference: '#/paths',
                    constraint: 'path',
                    expectedValue: 'valid path in spec',
                    receivedValue: $path,
                    reason: 'No paths defined in OpenAPI spec',
                    hint: 'OpenAPI spec must define at least one path'
                ),
            ]);
        }

        // Normalize method to lowercase
        $method = \strtolower($method);
        $statusCodeStr = (string)$statusCode;

        // Try exact path match first
        if (isset($specArray['paths'][$path])) {
            $pathItem = $specArray['paths'][$path];
            if (\is_array($pathItem) && isset($pathItem[$method])) {
                $operation = $pathItem[$method];
                if (\is_array($operation)) {
                    $schema = self::extractResponseSchema($operation, $statusCodeStr);
                    if ([] !== $schema) {
                        return $schema;
                    }
                }
            }
        }

        // Try path parameter matching (e.g., /users/{id} matches /users/123)
        foreach ($specArray['paths'] as $specPath => $pathItem) {
            if (!\is_array($pathItem)) {
                continue;
            }

            // Convert path template to regex
            if (self::pathMatches($specPath, $path)) {
                if (isset($pathItem[$method])) {
                    $operation = $pathItem[$method];
                    if (\is_array($operation)) {
                        $schema = self::extractResponseSchema($operation, $statusCodeStr);
                        if ([] !== $schema) {
                            return $schema;
                        }
                    }
                }
            }
        }

        // Path/method/status not found - throw exception with helpful message
        $availablePaths = \array_keys($specArray['paths']);
        throw new InvalidResponseStatusException([
            new ValidationError(
                path: '$.path',
                specReference: '#/paths',
                constraint: 'status',
                expectedValue: 'valid status code in spec',
                receivedValue: $path . ' [' . $method . '] [' . $statusCode . ']',
                reason: \sprintf('Path "%s" with method "%s" and status code %d not found in OpenAPI spec', $path, $method, $statusCode),
                hint: 'Available paths: ' . \implode(', ', $availablePaths)
            ),
        ]);
    }

    /**
     * Extract request body schema from operation.
     *
     * @param array<string, mixed> $operation Operation object from spec
     *
     * @return array<string, mixed>
     */
    private static function extractRequestBodySchema(array $operation): array
    {
        if (!isset($operation['requestBody']) || !\is_array($operation['requestBody'])) {
            return [];
        }

        $requestBody = $operation['requestBody'];
        if (!isset($requestBody['content']) || !\is_array($requestBody['content'])) {
            return [];
        }

        $content = $requestBody['content'];
        if (!isset($content['application/json']) || !\is_array($content['application/json'])) {
            return [];
        }

        $jsonContent = $content['application/json'];
        if (!isset($jsonContent['schema']) || !\is_array($jsonContent['schema'])) {
            return [];
        }

        /** @var array<string, mixed> $schema */
        $schema = $jsonContent['schema'];
        return $schema;
    }

    /**
     * Extract response schema from operation for specific status code.
     *
     * @param array<string, mixed> $operation Operation object from spec
     * @param string $statusCode Status code as string (e.g., "200", "404")
     *
     * @return array<string, mixed>
     */
    private static function extractResponseSchema(array $operation, string $statusCode): array
    {
        if (!isset($operation['responses']) || !\is_array($operation['responses'])) {
            return [];
        }

        $responses = $operation['responses'];

        // Try exact status code match first
        if (isset($responses[$statusCode]) && \is_array($responses[$statusCode])) {
            $response = $responses[$statusCode];
            $schema = self::extractSchemaFromResponse($response);
            if ([] !== $schema) {
                return $schema;
            }
        }

        // Try 'default' response as fallback
        if (isset($responses['default']) && \is_array($responses['default'])) {
            $response = $responses['default'];
            $schema = self::extractSchemaFromResponse($response);
            if ([] !== $schema) {
                return $schema;
            }
        }

        return [];
    }

    /**
     * Extract schema from response object.
     *
     * @param array<string, mixed> $response Response object from spec
     *
     * @return array<string, mixed>
     */
    private static function extractSchemaFromResponse(array $response): array
    {
        if (!isset($response['content']) || !\is_array($response['content'])) {
            return [];
        }

        $content = $response['content'];
        if (!isset($content['application/json']) || !\is_array($content['application/json'])) {
            return [];
        }

        $jsonContent = $content['application/json'];
        if (!isset($jsonContent['schema']) || !\is_array($jsonContent['schema'])) {
            return [];
        }

        /** @var array<string, mixed> $schema */
        $schema = $jsonContent['schema'];
        return $schema;
    }

    /**
     * Check if a path matches a path template.
     *
     * Converts OpenAPI path templates (e.g., /users/{id}) to regex
     * and matches against actual paths (e.g., /users/123).
     *
     * @param string $template Path template from spec (e.g., "/users/{id}")
     * @param string $path Actual path to match (e.g., "/users/123")
     *
     * @return bool True if path matches template
     */
    private static function pathMatches(string $template, string $path): bool
    {
        // If no parameters, must be exact match
        if (!\str_contains($template, '{')) {
            return $template === $path;
        }

        // Convert template to regex pattern
        // /users/{id} becomes /users/([^/]+)
        // /users/{id}/posts/{postId} becomes /users/([^/]+)/posts/([^/]+)
        $pattern = \preg_replace('/\{[^}]+\}/', '([^/]+)', $template);
        if (null === $pattern) {
            return false;
        }

        // Escape forward slashes for regex
        $pattern = \str_replace('/', '\\/', $pattern);

        // Add anchors
        $pattern = '/^' . $pattern . '$/';

        return 1 === \preg_match($pattern, $path);
    }

    /**
     * Throw appropriate exception if errors exist.
     *
     * Determines which exception to throw based on error types.
     * Uses most specific exception type when possible.
     */
    private static function throwIfErrors(ErrorCollector $errors): void
    {
        if (!$errors->hasErrors()) {
            return;
        }

        $errorList = $errors->getErrors();

        // Count error types
        $typeErrors = 0;
        $requiredErrors = 0;
        $additionalPropErrors = 0;
        $formatErrors = 0;
        $boundaryErrors = 0;
        $patternErrors = 0;
        $enumErrors = 0;
        $compositionErrors = 0;
        $discriminatorErrors = 0;

        foreach ($errorList as $error) {
            if ('type' === $error->constraint) {
                ++$typeErrors;
            }
            if ('required' === $error->constraint) {
                ++$requiredErrors;
            }
            if ('additionalProperties' === $error->constraint) {
                ++$additionalPropErrors;
            }
            if ('format' === $error->constraint) {
                ++$formatErrors;
            }
            if (\in_array($error->constraint, ['minimum', 'maximum', 'exclusiveMinimum', 'exclusiveMaximum', 'minLength', 'maxLength', 'minItems', 'maxItems', 'uniqueItems', 'multipleOf'], true)) {
                ++$boundaryErrors;
            }
            if ('pattern' === $error->constraint) {
                ++$patternErrors;
            }
            if ('enum' === $error->constraint) {
                ++$enumErrors;
            }
            if (\in_array($error->constraint, ['oneOf', 'anyOf', 'allOf'], true)) {
                ++$compositionErrors;
            }
            if ('discriminator' === $error->constraint) {
                ++$discriminatorErrors;
            }
        }

        // If only one type of error, throw specific exception
        if (1 === \count($errorList)) {
            $error = $errorList[0];
            self::throwSpecificException($error);
        }

        // If multiple errors of same type, throw specific exception
        if ($typeErrors > 0 && 0 === $requiredErrors && 0 === $additionalPropErrors && 0 === $formatErrors && 0 === $boundaryErrors && 0 === $patternErrors && 0 === $enumErrors && 0 === $compositionErrors && 0 === $discriminatorErrors) {
            throw new TypeMismatchException($errorList);
        }

        if ($requiredErrors > 0 && 0 === $typeErrors && 0 === $additionalPropErrors && 0 === $formatErrors && 0 === $boundaryErrors && 0 === $patternErrors && 0 === $enumErrors && 0 === $compositionErrors && 0 === $discriminatorErrors) {
            throw new RequiredFieldMissingException($errorList);
        }

        if ($additionalPropErrors > 0 && 0 === $typeErrors && 0 === $requiredErrors && 0 === $formatErrors && 0 === $boundaryErrors && 0 === $patternErrors && 0 === $enumErrors && 0 === $compositionErrors && 0 === $discriminatorErrors) {
            throw new AdditionalPropertyException($errorList);
        }

        if ($formatErrors > 0 && 0 === $typeErrors && 0 === $requiredErrors && 0 === $additionalPropErrors && 0 === $boundaryErrors && 0 === $patternErrors && 0 === $enumErrors && 0 === $compositionErrors && 0 === $discriminatorErrors) {
            throw new FormatViolationException($errorList);
        }

        if ($boundaryErrors > 0 && 0 === $typeErrors && 0 === $requiredErrors && 0 === $additionalPropErrors && 0 === $formatErrors && 0 === $patternErrors && 0 === $enumErrors && 0 === $compositionErrors && 0 === $discriminatorErrors) {
            throw new BoundaryViolationException($errorList);
        }

        if ($patternErrors > 0 && 0 === $typeErrors && 0 === $requiredErrors && 0 === $additionalPropErrors && 0 === $formatErrors && 0 === $boundaryErrors && 0 === $enumErrors && 0 === $compositionErrors && 0 === $discriminatorErrors) {
            throw new PatternViolationException($errorList);
        }

        if ($enumErrors > 0 && 0 === $typeErrors && 0 === $requiredErrors && 0 === $additionalPropErrors && 0 === $formatErrors && 0 === $boundaryErrors && 0 === $patternErrors && 0 === $compositionErrors && 0 === $discriminatorErrors) {
            throw new EnumViolationException($errorList);
        }

        if ($compositionErrors > 0 && 0 === $typeErrors && 0 === $requiredErrors && 0 === $additionalPropErrors && 0 === $formatErrors && 0 === $boundaryErrors && 0 === $patternErrors && 0 === $enumErrors && 0 === $discriminatorErrors) {
            throw new CompositionViolationException($errorList);
        }

        if ($discriminatorErrors > 0 && 0 === $typeErrors && 0 === $requiredErrors && 0 === $additionalPropErrors && 0 === $formatErrors && 0 === $boundaryErrors && 0 === $patternErrors && 0 === $enumErrors && 0 === $compositionErrors) {
            throw new DiscriminatorViolationException($errorList);
        }

        // Multiple error types - throw generic SchemaViolationException
        throw new SchemaViolationException($errorList);
    }

    /**
     * Throw specific exception for a single error.
     */
    private static function throwSpecificException(\LongTermSupport\StrictOpenApiValidator\Exception\ValidationError $error): void
    {
        if ('type' === $error->constraint) {
            throw new TypeMismatchException([$error]);
        }

        if ('required' === $error->constraint) {
            throw new RequiredFieldMissingException([$error]);
        }

        if ('additionalProperties' === $error->constraint) {
            throw new AdditionalPropertyException([$error]);
        }

        if ('format' === $error->constraint) {
            throw new FormatViolationException([$error]);
        }

        if (\in_array($error->constraint, ['minimum', 'maximum', 'exclusiveMinimum', 'exclusiveMaximum', 'minLength', 'maxLength', 'minItems', 'maxItems', 'uniqueItems', 'multipleOf'], true)) {
            throw new BoundaryViolationException([$error]);
        }

        if ('pattern' === $error->constraint) {
            throw new PatternViolationException([$error]);
        }

        if ('enum' === $error->constraint) {
            throw new EnumViolationException([$error]);
        }

        if (\in_array($error->constraint, ['oneOf', 'anyOf', 'allOf'], true)) {
            throw new CompositionViolationException([$error]);
        }

        if ('discriminator' === $error->constraint) {
            throw new DiscriminatorViolationException([$error]);
        }

        // Fallback to generic SchemaViolationException
        throw new SchemaViolationException([$error]);
    }
}
