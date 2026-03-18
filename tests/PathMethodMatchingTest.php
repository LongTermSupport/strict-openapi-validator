<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Tests;

use LongTermSupport\StrictOpenApiValidator\Exception\InvalidRequestPathException;
use LongTermSupport\StrictOpenApiValidator\Exception\InvalidResponseStatusException;
use LongTermSupport\StrictOpenApiValidator\Spec;
use LongTermSupport\StrictOpenApiValidator\ValidationMode;
use LongTermSupport\StrictOpenApiValidator\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Phase 5: Path/Method/Status matching functionality.
 *
 * Verifies that requests and responses can be validated against
 * specific operations in the OpenAPI spec rather than just the
 * first schema found.
 */
#[CoversClass(Validator::class)]
final class PathMethodMatchingTest extends TestCase
{
    #[Test]
    public function itValidatesRequestWithExactPathMatch(): void
    {
        $specArray = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
            'paths' => [
                '/users' => [
                    'post' => [
                        'requestBody' => [
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'name' => ['type' => 'string'],
                                            'email' => ['type' => 'string'],
                                        ],
                                        'required' => ['name', 'email'],
                                        'additionalProperties' => false,
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '201' => [
                                'description' => 'Created',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $spec = Spec::createFromArray($specArray);
        $validJson = '{"name":"John","email":"john@example.com"}';

        // Should not throw - valid request for POST /users
        Validator::validateRequest($validJson, $spec, '/users', 'post');

        self::assertTrue(true); // If we get here, validation passed
    }

    #[Test]
    public function itValidatesResponseWithExactPathAndStatusMatch(): void
    {
        $specArray = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
            'paths' => [
                '/users/{id}' => [
                    'get' => [
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'integer'],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Success',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'id' => ['type' => 'integer'],
                                                'name' => ['type' => 'string'],
                                            ],
                                            'required' => ['id', 'name'],
                                            'additionalProperties' => false,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $spec = Spec::createFromArray($specArray);
        $validJson = '{"id":123,"name":"John"}';

        // Should not throw - valid response for GET /users/{id} with 200 status
        Validator::validateResponse($validJson, $spec, '/users/123', 'get', 200);

        self::assertTrue(true); // If we get here, validation passed
    }

    #[Test]
    public function itThrowsExceptionWhenPathNotFound(): void
    {
        $specArray = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
            'paths' => [
                '/users' => [
                    'post' => [
                        'requestBody' => [
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'name' => ['type' => 'string'],
                                        ],
                                        'required' => ['name'],
                                        'additionalProperties' => false,
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '201' => [
                                'description' => 'Created',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $spec = Spec::createFromArray($specArray);
        $json = '{"name":"John"}';

        $this->expectException(InvalidRequestPathException::class);
        $this->expectExceptionMessage('Path "/products" with method "post" not found in OpenAPI spec');

        Validator::validateRequest($json, $spec, '/products', 'post');
    }

    #[Test]
    public function itThrowsExceptionWhenStatusCodeNotFound(): void
    {
        $specArray = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
            'paths' => [
                '/users' => [
                    'get' => [
                        'responses' => [
                            '200' => [
                                'description' => 'Success',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'name' => ['type' => 'string'],
                                            ],
                                            'required' => ['name'],
                                            'additionalProperties' => false,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $spec = Spec::createFromArray($specArray);
        $json = '{"error":"Not found"}';

        $this->expectException(InvalidResponseStatusException::class);
        $this->expectExceptionMessage('Path "/users" with method "get" and status code 404 not found in OpenAPI spec');

        Validator::validateResponse($json, $spec, '/users', 'get', 404);
    }

    #[Test]
    public function itMatchesPathParameters(): void
    {
        $specArray = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
            'paths' => [
                '/users/{id}' => [
                    'get' => [
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'integer'],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Success',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'id' => ['type' => 'integer'],
                                            ],
                                            'required' => ['id'],
                                            'additionalProperties' => false,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $spec = Spec::createFromArray($specArray);
        $validJson = '{"id":456}';

        // Should match /users/{id} with /users/456
        Validator::validateResponse($validJson, $spec, '/users/456', 'get', 200);

        self::assertTrue(true); // If we get here, validation passed
    }

    #[Test]
    public function itUsesDefaultResponseWhenStatusNotFound(): void
    {
        $specArray = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
            'paths' => [
                '/users' => [
                    'get' => [
                        'responses' => [
                            'default' => [
                                'description' => 'Default response',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'message' => ['type' => 'string'],
                                            ],
                                            'required' => ['message'],
                                            'additionalProperties' => false,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $spec = Spec::createFromArray($specArray);
        $validJson = '{"message":"Something happened"}';

        // Should use 'default' response when specific status not found
        Validator::validateResponse($validJson, $spec, '/users', 'get', 500);

        self::assertTrue(true); // If we get here, validation passed
    }

    #[Test]
    public function itResolvesRefOnRequestBody(): void
    {
        $specArray = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
            'paths' => [
                '/agents/{agentId}' => [
                    'patch' => [
                        'parameters' => [
                            [
                                'name' => 'agentId',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'string'],
                            ],
                        ],
                        'requestBody' => [
                            '$ref' => '#/components/requestBodies/updateAgent',
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Updated',
                            ],
                        ],
                    ],
                ],
            ],
            'components' => [
                'requestBodies' => [
                    'updateAgent' => [
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'firstName' => ['type' => 'string'],
                                        'lastName' => ['type' => 'string'],
                                    ],
                                    'required' => ['firstName'],
                                    'additionalProperties' => false,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $spec = Spec::createFromArray($specArray);
        $validJson = '{"firstName":"John","lastName":"Doe"}';

        // Should resolve $ref and validate against the referenced requestBody
        Validator::validateRequest($validJson, $spec, '/agents/{agentId}', 'patch');

        self::assertTrue(true);
    }

    #[Test]
    public function itResolvesRefOnRequestBodyWithPathParameterMatching(): void
    {
        $specArray = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
            'paths' => [
                '/agents/{agentId}' => [
                    'patch' => [
                        'parameters' => [
                            [
                                'name' => 'agentId',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'string'],
                            ],
                        ],
                        'requestBody' => [
                            '$ref' => '#/components/requestBodies/updateAgent',
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Updated',
                            ],
                        ],
                    ],
                ],
            ],
            'components' => [
                'requestBodies' => [
                    'updateAgent' => [
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'firstName' => ['type' => 'string'],
                                    ],
                                    'required' => ['firstName'],
                                    'additionalProperties' => false,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $spec = Spec::createFromArray($specArray);
        $validJson = '{"firstName":"John"}';

        // Should resolve $ref even when matching via path parameters
        Validator::validateRequest($validJson, $spec, '/agents/999', 'patch');

        self::assertTrue(true);
    }

    #[Test]
    public function itResolvesRefOnResponse(): void
    {
        $specArray = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
            'paths' => [
                '/agents' => [
                    'get' => [
                        'responses' => [
                            '200' => [
                                '$ref' => '#/components/responses/agentList',
                            ],
                        ],
                    ],
                ],
            ],
            'components' => [
                'responses' => [
                    'agentList' => [
                        'description' => 'Agent list',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'data' => [
                                            'type' => 'array',
                                            'items' => ['type' => 'object'],
                                        ],
                                    ],
                                    'required' => ['data'],
                                    'additionalProperties' => false,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $spec = Spec::createFromArray($specArray, ValidationMode::Client);
        $validJson = '{"data":[]}';

        Validator::validateResponse($validJson, $spec, '/agents', 'get', 200);

        self::assertTrue(true);
    }

    #[Test]
    public function itMaintainsBackwardCompatibilityWithEmptyParameters(): void
    {
        $specArray = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
            'paths' => [
                '/users' => [
                    'post' => [
                        'requestBody' => [
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'name' => ['type' => 'string'],
                                        ],
                                        'required' => ['name'],
                                        'additionalProperties' => false,
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '201' => [
                                'description' => 'Created',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $spec = Spec::createFromArray($specArray);
        $validJson = '{"name":"John"}';

        // Should use first schema found when path/method not provided
        Validator::validateRequest($validJson, $spec);

        self::assertTrue(true); // If we get here, validation passed
    }
}
