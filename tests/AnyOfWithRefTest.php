<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Tests;

use LongTermSupport\StrictOpenApiValidator\Spec;
use LongTermSupport\StrictOpenApiValidator\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Test anyOf with $ref validation.
 *
 * Reproduces issue where anyOf containing [$ref, {type: null}] fails validation.
 */
#[CoversClass(Validator::class)]
final class AnyOfWithRefTest extends TestCase
{
    #[Test]
    public function itValidatesAnyOfWithRefAndNullForObjectValue(): void
    {
        $this->expectNotToPerformAssertions();

        // Minimal OpenAPI spec with anyOf containing $ref and null
        $spec = [
            'openapi' => '3.1.0',
            'info' => ['title' => 'Test', 'version' => '1.0.0'],
            'paths' => [
                '/test' => [
                    'get' => [
                        'responses' => [
                            '200' => [
                                'description' => 'Success',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'required' => ['id'],
                                            'properties' => [
                                                'id' => ['type' => 'integer'],
                                                'data' => [
                                                    'anyOf' => [
                                                        ['$ref' => '#/components/schemas/DataDto'],
                                                        ['type' => 'null'],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'components' => [
                'schemas' => [
                    'DataDto' => [
                        'type' => 'object',
                        'required' => ['name'],
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'value' => ['type' => 'integer'],
                        ],
                        'additionalProperties' => false,
                    ],
                ],
            ],
        ];

        $specObj = Spec::createFromArray($spec);

        // Test 1: data is an object (should match first anyOf schema)
        $responseWithObject = \Safe\json_encode([
            'id' => 123,
            'data' => [
                'name' => 'test',
                'value' => 42,
            ],
        ]);

        // Test: Validator should NOT throw exception for object value matching $ref schema
        Validator::validateResponse($responseWithObject, $specObj, '/test', 'get', 200);
    }

    #[Test]
    public function itValidatesAnyOfWithRefAndNullForNullValue(): void
    {
        $this->expectNotToPerformAssertions();

        $spec = [
            'openapi' => '3.1.0',
            'info' => ['title' => 'Test', 'version' => '1.0.0'],
            'paths' => [
                '/test' => [
                    'get' => [
                        'responses' => [
                            '200' => [
                                'description' => 'Success',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'required' => ['id'],
                                            'properties' => [
                                                'id' => ['type' => 'integer'],
                                                'data' => [
                                                    'anyOf' => [
                                                        ['$ref' => '#/components/schemas/DataDto'],
                                                        ['type' => 'null'],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'components' => [
                'schemas' => [
                    'DataDto' => [
                        'type' => 'object',
                        'required' => ['name'],
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'value' => ['type' => 'integer'],
                        ],
                        'additionalProperties' => false,
                    ],
                ],
            ],
        ];

        $specObj = Spec::createFromArray($spec);

        // Test 2: data is null (should match second anyOf schema)
        $responseWithNull = \Safe\json_encode([
            'id' => 123,
            'data' => null,
        ]);

        // Test: Validator should NOT throw exception for null value matching null schema
        Validator::validateResponse($responseWithNull, $specObj, '/test', 'get', 200);
    }
}
