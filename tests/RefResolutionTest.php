<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Tests;

use LongTermSupport\StrictOpenApiValidator\Exception\RequiredFieldMissingException;
use LongTermSupport\StrictOpenApiValidator\Exception\TypeMismatchException;
use LongTermSupport\StrictOpenApiValidator\Spec;
use LongTermSupport\StrictOpenApiValidator\ValidationMode;
use LongTermSupport\StrictOpenApiValidator\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for $ref resolution in OpenAPI specs.
 *
 * Verifies that $ref references are resolved correctly so that
 * requestBodies, responses, schemas, and other components can be
 * validated even when they use $ref pointers.
 */
#[CoversClass(Spec::class)]
#[CoversClass(Validator::class)]
final class RefResolutionTest extends TestCase
{
    #[Test]
    public function itResolvesRefInRequestBody(): void
    {
        $spec = Spec::createFromArray($this->specWithRequestBodyRef(), ValidationMode::Client);

        $validJson = '{"lastName":"Doe","emailId":"john@example.com"}';

        // Should NOT throw - the $ref to requestBodies should be resolved
        Validator::validateRequest($validJson, $spec, '/api/v1/agents', 'post');

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function itValidatesDataThroughResolvedRequestBodyRef(): void
    {
        $spec = Spec::createFromArray($this->specWithRequestBodyRef(), ValidationMode::Client);

        // Missing required field 'lastName'
        $invalidJson = '{"emailId":"john@example.com"}';

        $this->expectException(RequiredFieldMissingException::class);
        $this->expectExceptionMessage('lastName');

        Validator::validateRequest($invalidJson, $spec, '/api/v1/agents', 'post');
    }

    #[Test]
    public function itResolvesRefInPatchRequestBody(): void
    {
        $spec = Spec::createFromArray($this->specWithPatchRequestBodyRef(), ValidationMode::Client);

        $validJson = '{"lastName":"Updated"}';

        // Should NOT throw - the $ref to requestBodies for PATCH should be resolved
        Validator::validateRequest($validJson, $spec, '/api/v1/agents/12345', 'patch');

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function itRejectsInvalidDataThroughResolvedPatchRef(): void
    {
        $spec = Spec::createFromArray($this->specWithPatchRequestBodyRef(), ValidationMode::Client);

        // lastName must be string, not integer
        $invalidJson = '{"lastName":12345}';

        $this->expectException(TypeMismatchException::class);

        Validator::validateRequest($invalidJson, $spec, '/api/v1/agents/12345', 'patch');
    }

    #[Test]
    public function itResolvesRefInResponse(): void
    {
        $spec = Spec::createFromArray($this->specWithResponseRef(), ValidationMode::Client);

        $validJson = '{"id":123,"name":"John"}';

        Validator::validateResponse($validJson, $spec, '/api/v1/users/123', 'get', 200);

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function itResolvesNestedRefsInRequestBody(): void
    {
        // requestBody $ref -> resolved content has schema with $ref -> resolved schema
        $spec = Spec::createFromArray($this->specWithNestedRefs(), ValidationMode::Client);

        $validJson = '{"name":"John","address":{"street":"123 Main St","city":"Springfield"}}';

        Validator::validateRequest($validJson, $spec, '/api/v1/users', 'post');

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function itHandlesUnresolvableRefGracefully(): void
    {
        // External file refs (./Other.json#/...) can't be resolved - should not crash
        // but the path/method will have no extractable requestBody schema,
        // so the validator throws InvalidRequestPathException
        $specArray = [
            'openapi' => '3.1.0',
            'info' => ['title' => 'Test', 'version' => '1.0.0'],
            'paths' => [
                '/test' => [
                    'post' => [
                        'requestBody' => [
                            '$ref' => './External.json#/components/requestBodies/something',
                        ],
                        'responses' => [
                            '200' => ['description' => 'OK'],
                        ],
                    ],
                ],
            ],
        ];

        $spec = Spec::createFromArray($specArray, ValidationMode::Client);

        $this->expectException(\LongTermSupport\StrictOpenApiValidator\Exception\InvalidRequestPathException::class);

        Validator::validateRequest('{"anything":"goes"}', $spec, '/test', 'post');
    }

    #[Test]
    public function itResolvesRefInFirstRequestBodySchemaFallback(): void
    {
        $spec = Spec::createFromArray($this->specWithRequestBodyRef(), ValidationMode::Client);

        $validJson = '{"lastName":"Doe","emailId":"john@example.com"}';

        // Using backward-compatible mode (no path/method) should also resolve $ref
        Validator::validateRequest($validJson, $spec);

        $this->addToAssertionCount(1);
    }

    /**
     * Spec with POST /api/v1/agents where requestBody uses $ref.
     * Mimics the Zoho Desk Agent spec pattern.
     *
     * @return array<string, mixed>
     */
    private function specWithRequestBodyRef(): array
    {
        return [
            'openapi' => '3.1.0',
            'info' => ['title' => 'Agent API', 'version' => '1.0.0'],
            'paths' => [
                '/api/v1/agents' => [
                    'post' => [
                        'operationId' => 'createAgent',
                        'requestBody' => [
                            '$ref' => '#/components/requestBodies/addAgentInputStream',
                        ],
                        'responses' => [
                            '200' => ['description' => 'Success'],
                        ],
                    ],
                ],
            ],
            'components' => [
                'requestBodies' => [
                    'addAgentInputStream' => [
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'lastName' => ['type' => 'string'],
                                        'emailId' => ['type' => 'string'],
                                        'roleId' => ['type' => 'string'],
                                    ],
                                    'required' => ['lastName', 'emailId'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Spec with PATCH /api/v1/agents/{agentId} where requestBody uses $ref.
     *
     * @return array<string, mixed>
     */
    private function specWithPatchRequestBodyRef(): array
    {
        return [
            'openapi' => '3.1.0',
            'info' => ['title' => 'Agent API', 'version' => '1.0.0'],
            'paths' => [
                '/api/v1/agents/{agentId}' => [
                    'patch' => [
                        'operationId' => 'updateAgent',
                        'parameters' => [
                            ['name' => 'agentId', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']],
                        ],
                        'requestBody' => [
                            '$ref' => '#/components/requestBodies/updateAgentInputStream',
                        ],
                        'responses' => [
                            '200' => ['description' => 'Success'],
                        ],
                    ],
                ],
            ],
            'components' => [
                'requestBodies' => [
                    'updateAgentInputStream' => [
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'lastName' => ['type' => 'string'],
                                        'emailId' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Spec with GET /api/v1/users/{id} where response uses $ref.
     *
     * @return array<string, mixed>
     */
    private function specWithResponseRef(): array
    {
        return [
            'openapi' => '3.1.0',
            'info' => ['title' => 'User API', 'version' => '1.0.0'],
            'paths' => [
                '/api/v1/users/{id}' => [
                    'get' => [
                        'operationId' => 'getUser',
                        'parameters' => [
                            ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                        ],
                        'responses' => [
                            '200' => [
                                '$ref' => '#/components/responses/userResponse',
                            ],
                        ],
                    ],
                ],
            ],
            'components' => [
                'responses' => [
                    'userResponse' => [
                        'description' => 'A user object',
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
        ];
    }

    /**
     * Spec with nested $ref: requestBody ref -> schema ref.
     *
     * @return array<string, mixed>
     */
    private function specWithNestedRefs(): array
    {
        return [
            'openapi' => '3.1.0',
            'info' => ['title' => 'User API', 'version' => '1.0.0'],
            'paths' => [
                '/api/v1/users' => [
                    'post' => [
                        'operationId' => 'createUser',
                        'requestBody' => [
                            '$ref' => '#/components/requestBodies/createUserBody',
                        ],
                        'responses' => [
                            '201' => ['description' => 'Created'],
                        ],
                    ],
                ],
            ],
            'components' => [
                'requestBodies' => [
                    'createUserBody' => [
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => ['type' => 'string'],
                                        'address' => [
                                            '$ref' => '#/components/schemas/Address',
                                        ],
                                    ],
                                    'required' => ['name'],
                                ],
                            ],
                        ],
                    ],
                ],
                'schemas' => [
                    'Address' => [
                        'type' => 'object',
                        'properties' => [
                            'street' => ['type' => 'string'],
                            'city' => ['type' => 'string'],
                        ],
                        'required' => ['street', 'city'],
                    ],
                ],
            ],
        ];
    }
}
