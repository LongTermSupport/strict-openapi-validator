<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator;

use LongTermSupport\StrictOpenApiValidator\Exception\ValidationError;

/**
 * Validates string formats against OpenAPI format constraints.
 *
 * Supports: email, uri, uuid, date-time, date, time, hostname, ipv4, ipv6, etc.
 */
final readonly class FormatValidator
{
    /**
     * Validate data format against schema format constraint.
     *
     * @param mixed $data Data to validate
     * @param array<string, mixed> $schema Schema to validate against
     * @param string $path Current path in the data (for error reporting)
     * @param ErrorCollector $errors Error collector
     */
    public static function validate(mixed $data, array $schema, string $path, ErrorCollector $errors): void
    {
        // Format validation only applies to strings
        if (!isset($schema['format']) || !\is_string($data)) {
            return;
        }

        $format = $schema['format'];

        $isValid = match ($format) {
            'email' => self::isValidEmail($data),
            'uri' => self::isValidUri($data),
            'uri-reference' => self::isValidUriReference($data),
            'uuid' => self::isValidUuid($data),
            'date-time' => self::isValidDateTime($data),
            'date' => self::isValidDate($data),
            'time' => self::isValidTime($data),
            'hostname' => self::isValidHostname($data),
            'ipv4' => self::isValidIpv4($data),
            'ipv6' => self::isValidIpv6($data),
            default => true, // Unknown formats are not validated (per OpenAPI spec)
        };

        if (!$isValid) {
            $formatHint = self::getFormatHint($format);
            $errors->addError(new ValidationError(
                path: $path,
                specReference: '#/schema/format',
                constraint: 'format',
                expectedValue: $format,
                receivedValue: $data,
                reason: \sprintf('Invalid %s format', $format),
                hint: $formatHint
            ));
        }
    }

    private static function isValidEmail(string $value): bool
    {
        return false !== \filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    private static function isValidUri(string $value): bool
    {
        return false !== \filter_var($value, FILTER_VALIDATE_URL);
    }

    private static function isValidUriReference(string $value): bool
    {
        // URI reference can be relative or absolute
        // Simple validation: check if it's a valid URI OR looks like a path
        if (self::isValidUri($value)) {
            return true;
        }

        // Check if it looks like a valid path (starts with /, ./, or ../)
        if (\str_starts_with($value, '/') || \str_starts_with($value, './') || \str_starts_with($value, '../')) {
            return true;
        }

        // Reject obviously invalid references
        return !str_contains($value, '://');
    }

    private static function isValidUuid(string $value): bool
    {
        return 1 === \preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value);
    }

    private static function isValidDateTime(string $value): bool
    {
        // RFC 3339 date-time format: 2024-11-17T10:00:00Z or 2024-11-17T10:00:00+00:00
        $pattern = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(Z|[+-]\d{2}:\d{2})$/';
        if (1 !== \preg_match($pattern, $value)) {
            return false;
        }

        // Verify it's actually parseable
        return false !== \strtotime($value);
    }

    private static function isValidDate(string $value): bool
    {
        // YYYY-MM-DD format
        if (1 !== \preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }

        // Verify it's a valid date
        return false !== \strtotime($value);
    }

    private static function isValidTime(string $value): bool
    {
        // HH:MM:SS or HH:MM:SS.sss format
        return 1 === \preg_match('/^\d{2}:\d{2}:\d{2}(\.\d+)?$/', $value);
    }

    private static function isValidHostname(string $value): bool
    {
        // Basic hostname validation
        return 1 === \preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)*$/i', $value);
    }

    private static function isValidIpv4(string $value): bool
    {
        return false !== \filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }

    private static function isValidIpv6(string $value): bool
    {
        return false !== \filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }

    private static function getFormatHint(string $format): ?string
    {
        return match ($format) {
            'email' => 'Expected valid email address like "user@example.com"',
            'uri' => 'Expected valid URI like "https://example.com"',
            'uri-reference' => 'Expected valid URI or relative path like "/path/to/resource"',
            'uuid' => 'Expected UUID format like "550e8400-e29b-41d4-a716-446655440000"',
            'date-time' => 'Expected RFC 3339 date-time like "2024-11-17T10:00:00Z"',
            'date' => 'Expected date in YYYY-MM-DD format like "2024-11-17"',
            'time' => 'Expected time in HH:MM:SS format like "10:00:00"',
            'hostname' => 'Expected valid hostname like "example.com"',
            'ipv4' => 'Expected valid IPv4 address like "192.168.1.1"',
            'ipv6' => 'Expected valid IPv6 address like "2001:db8::1"',
            default => null,
        };
    }
}
