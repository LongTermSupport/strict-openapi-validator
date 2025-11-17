<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator;

use LongTermSupport\StrictOpenApiValidator\Exception\AdditionalPropertyException;
use LongTermSupport\StrictOpenApiValidator\Exception\BoundaryViolationException;
use LongTermSupport\StrictOpenApiValidator\Exception\FormatViolationException;
use LongTermSupport\StrictOpenApiValidator\Exception\PatternViolationException;
use LongTermSupport\StrictOpenApiValidator\Exception\RequiredFieldMissingException;
use LongTermSupport\StrictOpenApiValidator\Exception\SchemaViolationException;
use LongTermSupport\StrictOpenApiValidator\Exception\TypeMismatchException;
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
     * Phase 2: Validates against first requestBody schema found in spec.
     * Phase 5 will add proper path/method matching.
     *
     * @param string $json JSON request body to validate
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
     */
    public static function validateRequest(string $json, Spec $spec): void
    {
        // Parse JSON (returns mixed, validated before use)
        $data = \Safe\json_decode($json, true);

        // Find first request body schema in spec
        $schema = self::findFirstRequestBodySchema($spec);

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
     * Phase 2: Validates against first response schema found in spec.
     * Phase 5 will add proper path/method/status matching.
     *
     * @param string $json JSON response body to validate
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
     */
    public static function validateResponse(string $json, Spec $spec): void
    {
        // Parse JSON (returns mixed, validated before use)
        $data = \Safe\json_decode($json, true);

        // Find first response schema in spec
        $schema = self::findFirstResponseSchema($spec);

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
     * Phase 5: Not yet implemented - proper request matching will be added.
     *
     * @param Request $request Symfony Request object to validate
     * @param Spec $spec OpenAPI specification to validate against
     *
     * @return void
     * @throws \LogicException Always - not yet implemented
     */
    public static function validate(Request $request, Spec $spec): void
    {
        throw new \LogicException('Not yet implemented');
    }

    /**
     * Validate a Symfony Response object against the OpenAPI spec.
     *
     * Phase 5: Not yet implemented - proper response matching will be added.
     *
     * @param Response $response Symfony Response object to validate
     * @param Spec $spec OpenAPI specification to validate against
     *
     * @return void
     * @throws \LogicException Always - not yet implemented
     */
    public static function validateSymfonyResponse(Response $response, Spec $spec): void
    {
        throw new \LogicException('Not yet implemented');
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
        }

        // If only one type of error, throw specific exception
        if (1 === \count($errorList)) {
            $error = $errorList[0];
            self::throwSpecificException($error);
        }

        // If multiple errors of same type, throw specific exception
        if ($typeErrors > 0 && 0 === $requiredErrors && 0 === $additionalPropErrors && 0 === $formatErrors && 0 === $boundaryErrors && 0 === $patternErrors) {
            throw new TypeMismatchException($errorList);
        }

        if ($requiredErrors > 0 && 0 === $typeErrors && 0 === $additionalPropErrors && 0 === $formatErrors && 0 === $boundaryErrors && 0 === $patternErrors) {
            throw new RequiredFieldMissingException($errorList);
        }

        if ($additionalPropErrors > 0 && 0 === $typeErrors && 0 === $requiredErrors && 0 === $formatErrors && 0 === $boundaryErrors && 0 === $patternErrors) {
            throw new AdditionalPropertyException($errorList);
        }

        if ($formatErrors > 0 && 0 === $typeErrors && 0 === $requiredErrors && 0 === $additionalPropErrors && 0 === $boundaryErrors && 0 === $patternErrors) {
            throw new FormatViolationException($errorList);
        }

        if ($boundaryErrors > 0 && 0 === $typeErrors && 0 === $requiredErrors && 0 === $additionalPropErrors && 0 === $formatErrors && 0 === $patternErrors) {
            throw new BoundaryViolationException($errorList);
        }

        if ($patternErrors > 0 && 0 === $typeErrors && 0 === $requiredErrors && 0 === $additionalPropErrors && 0 === $formatErrors && 0 === $boundaryErrors) {
            throw new PatternViolationException($errorList);
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

        // Fallback to generic SchemaViolationException
        throw new SchemaViolationException([$error]);
    }
}
