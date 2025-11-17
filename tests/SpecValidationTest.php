<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator\Tests;

use Iterator;
use LogicException;
use LongTermSupport\StrictOpenApiValidator\Exception\InvalidSpecException;
use LongTermSupport\StrictOpenApiValidator\Exception\InvalidSpecVersionException;
use LongTermSupport\StrictOpenApiValidator\Exception\MissingRequiredSpecFieldException;
use LongTermSupport\StrictOpenApiValidator\Spec;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for OpenAPI spec validation.
 *
 * These tests validate that Spec::createFromFile() and Spec::createFromArray()
 * properly validate OpenAPI 3.1.0 specifications.
 *
 * CURRENT STATUS: All tests should FAIL because Spec currently does no validation (NOOP).
 * Once validation is implemented, these tests will verify correct behavior.
 *
 * @see https://spec.openapis.org/oas/v3.1.0.html
 */
#[CoversClass(Spec::class)]
final class SpecValidationTest extends TestCase
{
    /**
     * Base path to spec fixtures.
     */
    private const string FIXTURES_PATH = __DIR__ . '/Fixtures/Specs/';

    // ==================== VALID SPECS (should accept) ====================

    /**
     * Valid minimal spec should be accepted.
     *
     * This is the absolute minimum valid OpenAPI 3.1.0 spec.
     */
    #[Test]
    public function itAcceptsMinimalValidSpec(): void
    {
        $spec = Spec::createFromFile(self::FIXTURES_PATH . 'minimal-valid.json');

        /** @var array{openapi: string, info: array{title: string}} $specArray */
        $specArray = $spec->getSpec();
        self::assertSame('3.1.0', $specArray['openapi']);
        self::assertSame('Minimal API', $specArray['info']['title']);
    }

    /**
     * Valid simple CRUD spec should be accepted.
     *
     * Tests that a complete, realistic API spec is accepted.
     */
    #[Test]
    public function itAcceptsSimpleCrudSpec(): void
    {
        $spec = Spec::createFromFile(self::FIXTURES_PATH . 'simple-crud.json');

        /** @var array<string, mixed> $specArray */
        $specArray = $spec->getSpec();
        self::assertSame('3.1.0', $specArray['openapi']);
        self::assertArrayHasKey('paths', $specArray);
        self::assertIsArray($specArray['paths']);
        self::assertArrayHasKey('/users', $specArray['paths']);
    }

    /**
     * Valid strict schemas spec should be accepted.
     *
     * Tests that specs with additionalProperties: false are accepted.
     */
    #[Test]
    public function itAcceptsStrictSchemasSpec(): void
    {
        $spec = Spec::createFromFile(self::FIXTURES_PATH . 'strict-schemas.json');

        /** @var array<string, mixed> $specArray */
        $specArray = $spec->getSpec();
        self::assertSame('3.1.0', $specArray['openapi']);
        self::assertArrayHasKey('components', $specArray);
        self::assertIsArray($specArray['components']);
        self::assertArrayHasKey('schemas', $specArray['components']);
    }

    /**
     * Valid composition examples spec should be accepted.
     *
     * Tests that specs with oneOf/anyOf/allOf/discriminator are accepted.
     */
    #[Test]
    public function itAcceptsCompositionExamplesSpec(): void
    {
        $spec = Spec::createFromFile(self::FIXTURES_PATH . 'composition-examples.json');

        /** @var array<string, mixed> $specArray */
        $specArray = $spec->getSpec();
        self::assertSame('3.1.0', $specArray['openapi']);
        self::assertArrayHasKey('components', $specArray);
        self::assertIsArray($specArray['components']);
        self::assertArrayHasKey('schemas', $specArray['components']);
    }

    /**
     * Valid edge cases spec should be accepted.
     *
     * Tests that specs with nullable fields, boundaries, and patterns are accepted.
     */
    #[Test]
    public function itAcceptsEdgeCasesSpec(): void
    {
        $spec = Spec::createFromFile(self::FIXTURES_PATH . 'edge-cases.json');

        self::assertSame('3.1.0', $spec->getSpec()['openapi']);
        self::assertArrayHasKey('components', $spec->getSpec());
    }

    // ==================== MISSING REQUIRED FIELDS ====================

    /**
     * Spec missing openapi field should be rejected.
     *
     * OpenAPI version is required by spec.
     */
    #[Test]
    public function itRejectsMissingOpenApiVersion(): void
    {
        $spec = [
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
            'paths' => [],
        ];

        $this->expectException(MissingRequiredSpecFieldException::class);
        $this->expectExceptionMessage('openapi');

        Spec::createFromArray($spec);
    }

    /**
     * Spec missing info object should be rejected.
     *
     * Info object is required by spec.
     */
    #[Test]
    public function itRejectsMissingInfoObject(): void
    {
        $spec = [
            'openapi' => '3.1.0',
            'paths' => [],
        ];

        $this->expectException(MissingRequiredSpecFieldException::class);
        $this->expectExceptionMessage('info');

        Spec::createFromArray($spec);
    }

    /**
     * Spec missing info.title should be rejected.
     *
     * Title is required within info object.
     */
    #[Test]
    public function itRejectsMissingInfoTitle(): void
    {
        $spec = [
            'openapi' => '3.1.0',
            'info' => [
                'version' => '1.0.0',
            ],
            'paths' => [],
        ];

        $this->expectException(MissingRequiredSpecFieldException::class);
        $this->expectExceptionMessage('title');

        Spec::createFromArray($spec);
    }

    /**
     * Spec missing info.version should be rejected.
     *
     * Version is required within info object.
     */
    #[Test]
    public function itRejectsMissingInfoVersion(): void
    {
        $spec = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Test API',
            ],
            'paths' => [],
        ];

        $this->expectException(MissingRequiredSpecFieldException::class);
        $this->expectExceptionMessage('version');

        Spec::createFromArray($spec);
    }

    /**
     * Spec with no paths, components, or webhooks should be rejected.
     *
     * At least one of these must be present.
     */
    #[Test]
    public function itRejectsEmptySpec(): void
    {
        $spec = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
        ];

        $this->expectException(InvalidSpecException::class);
        $this->expectExceptionMessage('paths');

        Spec::createFromArray($spec);
    }

    // ==================== INVALID OPENAPI VERSION ====================

    /**
     * OpenAPI 2.0 spec should be rejected.
     *
     * We only support OpenAPI 3.1.x.
     */
    #[Test]
    public function itRejectsOpenApi20(): void
    {
        $spec = [
            'swagger' => '2.0',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
            'paths' => [],
        ];

        $this->expectException(InvalidSpecVersionException::class);
        $this->expectExceptionMessage('2.0');

        Spec::createFromArray($spec);
    }

    /**
     * OpenAPI 3.0 spec should be rejected.
     *
     * We only support OpenAPI 3.1.x (not 3.0.x).
     */
    #[Test]
    public function itRejectsOpenApi30(): void
    {
        $spec = [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
            'paths' => [],
        ];

        $this->expectException(InvalidSpecVersionException::class);
        $this->expectExceptionMessage('3.0');

        Spec::createFromArray($spec);
    }

    /**
     * OpenAPI 3.2 spec should be rejected (future version).
     *
     * We only support OpenAPI 3.1.x.
     */
    #[Test]
    public function itRejectsOpenApi32(): void
    {
        $spec = [
            'openapi' => '3.2.0',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
            'paths' => [],
        ];

        $this->expectException(InvalidSpecVersionException::class);
        $this->expectExceptionMessage('3.2');

        Spec::createFromArray($spec);
    }

    /**
     * Invalid version format should be rejected.
     *
     * Version must be in semantic versioning format.
     */
    #[Test]
    public function itRejectsInvalidVersionFormat(): void
    {
        $spec = [
            'openapi' => 'three point one',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
            'paths' => [],
        ];

        $this->expectException(InvalidSpecVersionException::class);
        $this->expectExceptionMessage('version');

        Spec::createFromArray($spec);
    }

    // ==================== INVALID PATH STRUCTURE ====================

    /**
     * Path not starting with / should be rejected.
     *
     * All paths must start with forward slash.
     */
    #[Test]
    public function itRejectsInvalidPathFormat(): void
    {
        $spec = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
            'paths' => [
                'users' => [  // Missing leading /
                    'get' => [
                        'responses' => [
                            '200' => [
                                'description' => 'OK',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->expectException(InvalidSpecException::class);
        $this->expectExceptionMessage('path');

        Spec::createFromArray($spec);
    }

    /**
     * Duplicate operationId should be rejected.
     *
     * OperationIds must be unique across all operations.
     */
    #[Test]
    public function itRejectsDuplicateOperationIds(): void
    {
        $spec = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
            'paths' => [
                '/users' => [
                    'get' => [
                        'operationId' => 'getUser',
                        'responses' => [
                            '200' => [
                                'description' => 'OK',
                            ],
                        ],
                    ],
                ],
                '/products' => [
                    'get' => [
                        'operationId' => 'getUser',  // Duplicate!
                        'responses' => [
                            '200' => [
                                'description' => 'OK',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->expectException(InvalidSpecException::class);
        $this->expectExceptionMessage('operationId');

        Spec::createFromArray($spec);
    }

    /**
     * Path parameter mismatch should be rejected.
     *
     * Path template must have corresponding parameter definition.
     */
    #[Test]
    public function itRejectsPathParameterMismatch(): void
    {
        $spec = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
            'paths' => [
                '/users/{id}' => [  // Has {id} parameter
                    'get' => [
                        'parameters' => [
                            // Missing parameter definition for {id}
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'OK',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->expectException(InvalidSpecException::class);
        $this->expectExceptionMessage('parameter');

        Spec::createFromArray($spec);
    }

    /**
     * Invalid HTTP method should be rejected.
     *
     * Only valid HTTP methods are allowed (get, post, put, delete, patch, options, head, trace).
     */
    #[Test]
    public function itRejectsInvalidHttpMethod(): void
    {
        $spec = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
            'paths' => [
                '/users' => [
                    'fetch' => [  // Invalid method
                        'responses' => [
                            '200' => [
                                'description' => 'OK',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->expectException(InvalidSpecException::class);
        $this->expectExceptionMessage('method');

        Spec::createFromArray($spec);
    }

    // ==================== RESPONSE VALIDATION ====================

    /**
     * Response without description should be rejected.
     *
     * Response description is required by spec.
     */
    #[Test]
    public function itRejectsResponseWithoutDescription(): void
    {
        $spec = [
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
                                // Missing description
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->expectException(MissingRequiredSpecFieldException::class);
        $this->expectExceptionMessage('description');

        Spec::createFromArray($spec);
    }

    /**
     * Operation without responses should be rejected.
     *
     * At least one response is required.
     */
    #[Test]
    public function itRejectsOperationWithoutResponses(): void
    {
        $spec = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
            'paths' => [
                '/users' => [
                    'get' => [
                        // Missing responses
                    ],
                ],
            ],
        ];

        $this->expectException(MissingRequiredSpecFieldException::class);
        $this->expectExceptionMessage('responses');

        Spec::createFromArray($spec);
    }

    /**
     * Empty responses object should be rejected.
     *
     * At least one response must be defined.
     */
    #[Test]
    public function itRejectsEmptyResponsesObject(): void
    {
        $spec = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
            'paths' => [
                '/users' => [
                    'get' => [
                        'responses' => [],  // Empty
                    ],
                ],
            ],
        ];

        $this->expectException(InvalidSpecException::class);
        $this->expectExceptionMessage('responses');

        Spec::createFromArray($spec);
    }

    // ==================== SCHEMA VALIDATION ====================

    /**
     * Invalid schema type should be rejected.
     *
     * Type must be one of: string, number, integer, boolean, array, object, null.
     */
    #[Test]
    public function itRejectsInvalidSchemaType(): void
    {
        $spec = [
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
                                        'type' => 'varchar',  // Invalid type
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'OK',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->expectException(InvalidSpecException::class);
        $this->expectExceptionMessage('type');

        Spec::createFromArray($spec);
    }

    /**
     * Invalid format should be rejected.
     *
     * Format must be a recognized JSON Schema format.
     */
    #[Test]
    public function itRejectsInvalidFormat(): void
    {
        $spec = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
            'components' => [
                'schemas' => [
                    'User' => [
                        'type' => 'object',
                        'properties' => [
                            'email' => [
                                'type' => 'string',
                                'format' => 'electronic-mail',  // Invalid format
                            ],
                        ],
                    ],
                ],
            ],
            'paths' => [],
        ];

        $this->expectException(InvalidSpecException::class);
        $this->expectExceptionMessage('format');

        Spec::createFromArray($spec);
    }

    /**
     * Conflicting minimum/maximum constraints should be rejected.
     *
     * Minimum must be <= maximum.
     */
    #[Test]
    public function itRejectsConflictingMinMaxConstraints(): void
    {
        $spec = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
            'components' => [
                'schemas' => [
                    'Age' => [
                        'type' => 'integer',
                        'minimum' => 100,
                        'maximum' => 0,  // Conflicting!
                    ],
                ],
            ],
            'paths' => [],
        ];

        $this->expectException(InvalidSpecException::class);
        $this->expectExceptionMessage('minimum');

        Spec::createFromArray($spec);
    }

    /**
     * Conflicting minLength/maxLength constraints should be rejected.
     *
     * MinLength must be <= maxLength.
     */
    #[Test]
    public function itRejectsConflictingMinMaxLengthConstraints(): void
    {
        $spec = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
            'components' => [
                'schemas' => [
                    'Name' => [
                        'type' => 'string',
                        'minLength' => 100,
                        'maxLength' => 10,  // Conflicting!
                    ],
                ],
            ],
            'paths' => [],
        ];

        $this->expectException(InvalidSpecException::class);
        $this->expectExceptionMessage('minLength');

        Spec::createFromArray($spec);
    }

    /**
     * Invalid regex pattern should be rejected.
     *
     * Pattern must be a valid ECMA-262 regular expression.
     */
    #[Test]
    public function itRejectsInvalidRegexPattern(): void
    {
        $spec = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
            'components' => [
                'schemas' => [
                    'Code' => [
                        'type' => 'string',
                        'pattern' => '[',  // Invalid regex
                    ],
                ],
            ],
            'paths' => [],
        ];

        $this->expectException(InvalidSpecException::class);
        $this->expectExceptionMessage('pattern');

        Spec::createFromArray($spec);
    }

    // ==================== DATA PROVIDER TESTS ====================

    /**
     * Test multiple valid specs using data provider.
     *
     * @param string $specPath Path to spec file
     */
    #[Test]
    #[DataProvider('provideValidSpecFiles')]
    public function itAcceptsValidSpecsFromDataProvider(string $specPath): void
    {
        $spec = Spec::createFromFile($specPath);

        /** @var array<string, mixed> $specArray */
        $specArray = $spec->getSpec();
        self::assertSame('3.1.0', $specArray['openapi']);
        self::assertArrayHasKey('info', $specArray);
    }

    /**
     * Provide valid spec files for testing.
     *
     * @return Iterator<string, array{specPath: string}>
     */
    public static function provideValidSpecFiles(): Iterator
    {
        yield 'minimal-valid' => [
            'specPath' => self::FIXTURES_PATH . 'minimal-valid.json',
        ];

        yield 'simple-crud' => [
            'specPath' => self::FIXTURES_PATH . 'simple-crud.json',
        ];

        yield 'strict-schemas' => [
            'specPath' => self::FIXTURES_PATH . 'strict-schemas.json',
        ];

        yield 'composition-examples' => [
            'specPath' => self::FIXTURES_PATH . 'composition-examples.json',
        ];

        yield 'edge-cases' => [
            'specPath' => self::FIXTURES_PATH . 'edge-cases.json',
        ];
    }
}
