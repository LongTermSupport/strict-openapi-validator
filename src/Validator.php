<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates HTTP requests and responses against OpenAPI specifications.
 *
 * Phase 2 NOOP Implementation: All methods throw LogicException.
 * This ensures tests fail predictably with clear "Not yet implemented" messages
 * rather than silently passing without validation.
 * Full validation logic will be implemented in later phases.
 */
final readonly class Validator
{
    /**
     * Validate a JSON request string against the OpenAPI spec.
     *
     * Phase 2 NOOP: Throws LogicException until implemented.
     *
     * @param string $json JSON request body to validate
     * @param Spec $spec OpenAPI specification to validate against
     *
     * @return void
     * @throws \LogicException Always - not yet implemented
     */
    public static function validateRequest(string $json, Spec $spec): void
    {
        throw new \LogicException('Not yet implemented');
    }

    /**
     * Validate a JSON response string against the OpenAPI spec.
     *
     * Phase 2 NOOP: Throws LogicException until implemented.
     *
     * @param string $json JSON response body to validate
     * @param Spec $spec OpenAPI specification to validate against
     *
     * @return void
     * @throws \LogicException Always - not yet implemented
     */
    public static function validateResponse(string $json, Spec $spec): void
    {
        throw new \LogicException('Not yet implemented');
    }

    /**
     * Validate a Symfony Request object against the OpenAPI spec.
     *
     * Phase 2 NOOP: Throws LogicException until implemented.
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
     * Phase 2 NOOP: Throws LogicException until implemented.
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
}
