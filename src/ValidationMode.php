<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator;

/**
 * Controls validation behavior based on your role relative to the API.
 *
 * Client mode (consuming a third-party API):
 *   - Requests: validated strictly, errors thrown (catch our mistakes before sending)
 *   - Responses: validated with warnings only (log, don't throw - API may return unexpected data)
 *   - Spec structure: NOT validated (we don't control the spec quality)
 *
 * Server mode (we own the API):
 *   - Responses: validated strictly, errors thrown
 *   - Spec structure: validated strictly
 *   - Requests: validated with safe public-facing error messages
 *
 * Both mode:
 *   - Everything validated strictly (spec, requests, responses)
 *   - Full error detail on all violations
 */
enum ValidationMode
{
    case Client;
    case Server;
    case Both;

    public function shouldValidateSpec(): bool
    {
        return match ($this) {
            self::Client => false,
            self::Server, self::Both => true,
        };
    }

    public function shouldThrowOnRequestErrors(): bool
    {
        return match ($this) {
            self::Client, self::Both => true,
            self::Server => false,
        };
    }

    public function shouldThrowOnResponseErrors(): bool
    {
        return match ($this) {
            self::Server, self::Both => true,
            self::Client => false,
        };
    }
}
