<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates HTTP requests and responses against OpenAPI specifications.
 *
 * NOOP Implementation: All methods currently do nothing.
 * This allows tests to run but fail predictably since no validation occurs.
 * Full validation logic will be implemented in later phases.
 */
final class Validator
{
    /**
     * Validate a JSON request string against the OpenAPI spec.
     *
     * NOOP: Currently does nothing. Tests will fail as expected.
     *
     * @param string $json JSON request body to validate
     * @param Spec $spec OpenAPI specification to validate against
     *
     * @return void
     */
    public static function validateRequest(string $json, Spec $spec): void
    {
        // NOOP - to be implemented
    }

    /**
     * Validate a JSON response string against the OpenAPI spec.
     *
     * NOOP: Currently does nothing. Tests will fail as expected.
     *
     * @param string $json JSON response body to validate
     * @param Spec $spec OpenAPI specification to validate against
     *
     * @return void
     */
    public static function validateResponse(string $json, Spec $spec): void
    {
        // NOOP - to be implemented
    }

    /**
     * Validate a Symfony Request object against the OpenAPI spec.
     *
     * NOOP: Currently does nothing. Tests will fail as expected.
     *
     * @param Request $request Symfony Request object to validate
     * @param Spec $spec OpenAPI specification to validate against
     *
     * @return void
     */
    public static function validate(Request $request, Spec $spec): void
    {
        // NOOP - to be implemented
    }

    /**
     * Validate a Symfony Response object against the OpenAPI spec.
     *
     * NOOP: Currently does nothing. Tests will fail as expected.
     *
     * @param Response $response Symfony Response object to validate
     * @param Spec $spec OpenAPI specification to validate against
     *
     * @return void
     */
    public static function validateSymfonyResponse(Response $response, Spec $spec): void
    {
        // NOOP - to be implemented
    }
}
