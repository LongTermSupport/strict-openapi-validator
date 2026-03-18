# Strict OpenAPI Validator

Ultra-strict OpenAPI request/response validator for PHP 8.4+ with LLM-optimized error output.

## Purpose

Designed to catch API contract violations. Enforces absolute adherence to OpenAPI specifications - rejecting anything remotely invalid. Collects **all validation errors** before throwing to provide complete failure context.

## Core Philosophy

- **Collect all errors**: Gathers every validation issue before throwing, never stops at first failure
- **Detect, don't fix**: Precisely identify violations, never attempt automatic correction
- **Complete validation**: Nothing incomplete, nothing extra, nothing unexpected
- **Strict types**: No implicit coercion - `"123"` ≠ `123`
- **Helpful hints**: Common issues (snake/camel case confusion, type mismatches) get additional guidance

## Requirements

- PHP ^8.4

## Installation

```bash
composer require lts/strict-openapi-validator
```

## Validation Modes

Three modes control what gets validated and how strictly:

### Client Mode

For **consuming third-party APIs** where you don't control the spec quality.

- **Requests**: Validated strictly with errors thrown (catch your mistakes before sending)
- **Responses**: Validated with warnings only (log, don't throw - the API may return unexpected data)
- **Spec structure**: NOT validated (third-party specs are often sloppy)

```php
use LongTermSupport\StrictOpenApiValidator\Spec;
use LongTermSupport\StrictOpenApiValidator\ValidationMode;

$spec = Spec::createFromFile('/path/to/third-party-api.json', ValidationMode::Client);
```

### Server Mode

For **APIs you own** where you want to enforce spec quality.

- **Responses**: Validated strictly with errors thrown
- **Spec structure**: Validated strictly
- **Requests**: Validated with a view to generating helpful, public-safe error messages

```php
$spec = Spec::createFromFile('/path/to/your-api.json', ValidationMode::Server);
```

### Both Mode (default)

Full strict validation on everything - spec, requests, and responses all throw on error.

```php
// Default - validates everything strictly
$spec = Spec::createFromFile('/path/to/openapi.json');

// Explicit
$spec = Spec::createFromFile('/path/to/openapi.json', ValidationMode::Both);
```

## API

### Spec Loading

```php
use LongTermSupport\StrictOpenApiValidator\Spec;
use LongTermSupport\StrictOpenApiValidator\ValidationMode;

// Full strict validation (default - Both mode)
$spec = Spec::createFromFile('/path/to/openapi.json');

// Client mode - skip spec structure validation
$spec = Spec::createFromFile('/path/to/openapi.json', ValidationMode::Client);

// From array
$spec = Spec::createFromArray($specArray, ValidationMode::Client);
```

### JSON String Validation

```php
use LongTermSupport\StrictOpenApiValidator\Validator;

// Validate request body against spec for specific path/method
Validator::validateRequest(string $json, Spec $spec, string $path, string $method): void;

// Validate response body
Validator::validateResponse(string $json, Spec $spec, string $path, string $method, int $statusCode): void;
```

### Symfony Request/Response Validation

```php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Middleware-style validation
Validator::validate(Request $request, Spec $spec): void;
Validator::validateSymfonyResponse(Response $response, Spec $spec, string $path, string $method): void;
```

## Error Output

The validator collects **all validation errors** and throws a single exception containing the complete list. Errors are verbose, precise, and LLM-optimized:

```
Validation failed with 3 errors:

[1] unexpected string at request.body.user.age, breaking openapi.yml line 142 expectations
    expected: integer
    received: "25"
    hint: this looks like a type confusion issue - received string "25" but spec requires integer 25

[2] unexpected property at request.body.user_name, breaking openapi.yml line 156 expectations
    expected: userName
    reason: additionalProperties not allowed
    hint: this looks like a snake_case/camelCase confusion - did you mean "userName"?

[3] invalid format at response.body.email, breaking openapi.yml line 89 expectations
    expected: email format (RFC 5322)
    received: "not-an-email"
```

Each error includes:
- **Exact location**: JSONPath to problematic field
- **Spec reference**: Line number in OpenAPI spec
- **Expected vs Received**: Clear comparison
- **Reason**: Why validation failed
- **Hint** (when applicable): Suggestions for common issues

## Validation Strictness

The validator enforces:

1. **Complete structure**: All required fields must be present
2. **No extra fields**: Additional properties rejected unless explicitly allowed
3. **Exact types**: No type coercion (`"123"` is not `123`)
4. **Valid formats**: Email, URI, date-time, UUID etc. must be strictly valid
5. **Schema adherence**: Structure must match spec exactly
6. **No missing data**: Nothing incomplete

### Common Issue Detection

The validator provides helpful hints for common problems:

- **snake_case/camelCase confusion**: Detects similar field names with different casing
- **Type confusion**: String numbers vs integers/floats
- **Format issues**: Invalid but close-to-valid formats
- **Missing required fields**: Lists what's missing and where spec defines it

**Note**: Hints are suggestions only - the validator never attempts to fix issues.

## Development

This project uses [lts/php-qa-ci](https://github.com/LongTermSupport/php-qa-ci) for quality assurance.

## License

MIT

## Author

[LongTermSupport](https://github.com/LongTermSupport)
