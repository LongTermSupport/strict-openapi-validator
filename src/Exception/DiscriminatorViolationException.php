<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Exception;

/**
 * Exception thrown when discriminator validation fails.
 *
 * This validates the "discriminator" keyword from OpenAPI 3.1.0:
 * - Discriminator property must be present
 * - Discriminator value must map to a valid schema
 * - Used with oneOf to select correct schema based on property value
 *
 * Examples:
 * Schema with discriminator:
 * {
 *   "discriminator": {
 *     "propertyName": "petType",
 *     "mapping": {
 *       "dog": "#/components/schemas/Dog",
 *       "cat": "#/components/schemas/Cat"
 *     }
 *   },
 *   "oneOf": [
 *     {"$ref": "#/components/schemas/Dog"},
 *     {"$ref": "#/components/schemas/Cat"}
 *   ]
 * }
 *
 * Valid data:
 * - {"petType": "dog", "breed": "Golden Retriever"} - maps to Dog schema
 *
 * Invalid data:
 * - {} - missing discriminator property "petType"
 * - {"petType": "bird"} - discriminator value "bird" not in mapping
 */
final class DiscriminatorViolationException extends SchemaViolationException
{
}
