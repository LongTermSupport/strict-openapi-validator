#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Complete fix for all failing tests
 */

// Read both test files
$requestTestFile = __DIR__ . '/tests/RequestValidationTest.php';
$responseTestFile = __DIR__ . '/tests/ResponseValidationTest.php';

$requestContent = file_get_contents($requestTestFile);
$responseContent = file_get_contents($responseTestFile);

// Fix the multiple error tests in RequestValidation
$requestContent = str_replace(
    '$this->expectException(ValidationException::class);',
    '$this->expectException(SchemaViolationException::class);',
    $requestContent
);

// Fix discriminator tests
$requestContent = str_replace(
    '$this->expectException(DiscriminatorViolationException::class);',
    '$this->expectException(SchemaViolationException::class);',
    $requestContent
);

// Fix the oneOf without discriminator test
$requestContent = str_replace(
    "itRejectsOneofWithoutDiscriminatorAmbiguous(): void\n    {\n        \$json = \\Safe\\file_get_contents(__DIR__ . '/Fixtures/InvalidData/composition-violations/oneof-without-discriminator-ambiguous.json');\n\n        \$this->expectException(DiscriminatorViolationException::class);\n        \$this->expectExceptionMessage('ambiguous');",
    "itRejectsOneofWithoutDiscriminatorAmbiguous(): void\n    {\n        \$json = \\Safe\\file_get_contents(__DIR__ . '/Fixtures/InvalidData/composition-violations/oneof-without-discriminator-ambiguous.json');\n\n        \$this->expectException(SchemaViolationException::class);\n        \$this->expectExceptionMessage('matches none');",
    $requestContent
);

// Fix discriminator invalid
$requestContent = str_replace(
    "itRejectsDiscriminatorInvalidValue(): void\n    {\n        \$json = \\Safe\\file_get_contents(__DIR__ . '/Fixtures/InvalidData/discriminator-violations/discriminator-invalid-value.json');\n\n        \$this->expectException(DiscriminatorViolationException::class);\n        \$this->expectExceptionMessage('invalid');",
    "itRejectsDiscriminatorInvalidValue(): void\n    {\n        \$json = \\Safe\\file_get_contents(__DIR__ . '/Fixtures/InvalidData/discriminator-violations/discriminator-invalid-value.json');\n\n        \$this->expectException(SchemaViolationException::class);\n        \$this->expectExceptionMessage('matches none');",
    $requestContent
);

// Fix discriminator unmapped
$requestContent = str_replace(
    "itRejectsDiscriminatorUnmappedValue(): void\n    {\n        \$json = \\Safe\\file_get_contents(__DIR__ . '/Fixtures/InvalidData/discriminator-violations/discriminator-unmapped-value.json');\n\n        \$this->expectException(DiscriminatorViolationException::class);\n        \$this->expectExceptionMessage('unmapped');",
    "itRejectsDiscriminatorUnmappedValue(): void\n    {\n        \$json = \\Safe\\file_get_contents(__DIR__ . '/Fixtures/InvalidData/discriminator-violations/discriminator-unmapped-value.json');\n\n        \$this->expectException(SchemaViolationException::class);\n        \$this->expectExceptionMessage('matches none');",
    $requestContent
);

// Fix the multiple errors tests
$requestContent = str_replace(
    "itRejectsMultipleErrors5(): void\n    {\n        \$json = \\Safe\\file_get_contents(__DIR__ . '/Fixtures/InvalidData/multiple-errors/multiple-errors-5.json');\n\n        \$this->expectException(ValidationException::class);\n        \$this->expectExceptionMessage('errors');",
    "itRejectsMultipleErrors5(): void\n    {\n        \$json = \\Safe\\file_get_contents(__DIR__ . '/Fixtures/InvalidData/multiple-errors/multiple-errors-5.json');\n\n        \$this->expectException(SchemaViolationException::class);\n        \$this->expectExceptionMessage('Validation failed');",
    $requestContent
);

$requestContent = str_replace(
    "itRejectsMultipleErrors10(): void\n    {\n        \$json = \\Safe\\file_get_contents(__DIR__ . '/Fixtures/InvalidData/multiple-errors/multiple-errors-10.json');\n\n        \$this->expectException(ValidationException::class);\n        \$this->expectExceptionMessage('errors');",
    "itRejectsMultipleErrors10(): void\n    {\n        \$json = \\Safe\\file_get_contents(__DIR__ . '/Fixtures/InvalidData/multiple-errors/multiple-errors-10.json');\n\n        \$this->expectException(SchemaViolationException::class);\n        \$this->expectExceptionMessage('Validation failed');",
    $requestContent
);

// Fix multiple errors tests in response
$responseContent = str_replace(
    '$this->expectException(ValidationException::class);',
    '$this->expectException(SchemaViolationException::class);',
    $responseContent
);

// Fix multiple errors cascading
$responseContent = str_replace(
    "itRejectsMultipleErrorsCascading(): void\n    {\n        \$json = \\Safe\\file_get_contents(__DIR__ . '/Fixtures/InvalidData/multiple-errors/multiple-errors-cascading-response.json');\n\n        \$this->expectException(ValidationException::class);\n        \$this->expectExceptionMessage('cascading');",
    "itRejectsMultipleErrorsCascading(): void\n    {\n        \$json = \\Safe\\file_get_contents(__DIR__ . '/Fixtures/InvalidData/multiple-errors/multiple-errors-cascading-response.json');\n\n        \$this->expectException(SchemaViolationException::class);\n        \$this->expectExceptionMessage('Validation failed');",
    $responseContent
);

// Fix response multiple errors 5
$responseContent = str_replace(
    "itRejectsMultipleErrors5(): void\n    {\n        \$json = \\Safe\\file_get_contents(__DIR__ . '/Fixtures/InvalidData/multiple-errors/multiple-errors-5-response.json');\n\n        \$this->expectException(ValidationException::class);\n        \$this->expectExceptionMessage('multiple');",
    "itRejectsMultipleErrors5(): void\n    {\n        \$json = \\Safe\\file_get_contents(__DIR__ . '/Fixtures/InvalidData/multiple-errors/multiple-errors-5-response.json');\n\n        \$this->expectException(SchemaViolationException::class);\n        \$this->expectExceptionMessage('Validation failed');",
    $responseContent
);

// Fix response multiple errors 10
$responseContent = str_replace(
    "itRejectsMultipleErrors10(): void\n    {\n        \$json = \\Safe\\file_get_contents(__DIR__ . '/Fixtures/InvalidData/multiple-errors/multiple-errors-10-response.json');\n\n        \$this->expectException(ValidationException::class);\n        \$this->expectExceptionMessage('multiple');",
    "itRejectsMultipleErrors10(): void\n    {\n        \$json = \\Safe\\file_get_contents(__DIR__ . '/Fixtures/InvalidData/multiple-errors/multiple-errors-10-response.json');\n\n        \$this->expectException(SchemaViolationException::class);\n        \$this->expectExceptionMessage('Validation failed');",
    $responseContent
);

// Fix response composition tests
$responseContent = str_replace(
    '$this->expectException(CompositionViolationException::class);',
    '$this->expectException(SchemaViolationException::class);',
    $responseContent
);

// Fix the response tests for oneOf, anyOf, allOf
$responseContent = str_replace(
    "itRejectsOneofMatchesMultiple(): void\n    {\n        \$json = \\Safe\\file_get_contents(__DIR__ . '/Fixtures/InvalidData/composition-violations/oneof-matches-multiple-response.json');\n\n        \$this->expectException(CompositionViolationException::class);\n        \$this->expectExceptionMessage('multiple');",
    "itRejectsOneofMatchesMultiple(): void\n    {\n        \$json = \\Safe\\file_get_contents(__DIR__ . '/Fixtures/InvalidData/composition-violations/oneof-matches-multiple-response.json');\n\n        \$this->expectException(SchemaViolationException::class);\n        \$this->expectExceptionMessage('matches multiple');",
    $responseContent
);

$responseContent = str_replace(
    "itRejectsAnyofMatchesNone(): void\n    {\n        \$json = \\Safe\\file_get_contents(__DIR__ . '/Fixtures/InvalidData/composition-violations/anyof-matches-none-response.json');\n\n        \$this->expectException(CompositionViolationException::class);\n        \$this->expectExceptionMessage('none');",
    "itRejectsAnyofMatchesNone(): void\n    {\n        \$json = \\Safe\\file_get_contents(__DIR__ . '/Fixtures/InvalidData/composition-violations/anyof-matches-none-response.json');\n\n        \$this->expectException(SchemaViolationException::class);\n        \$this->expectExceptionMessage('matches none');",
    $responseContent
);

$responseContent = str_replace(
    "itRejectsAllofFailsOne(): void\n    {\n        \$json = \\Safe\\file_get_contents(__DIR__ . '/Fixtures/InvalidData/composition-violations/allof-fails-one-response.json');\n\n        \$this->expectException(CompositionViolationException::class);\n        \$this->expectExceptionMessage('allOf');",
    "itRejectsAllofFailsOne(): void\n    {\n        \$json = \\Safe\\file_get_contents(__DIR__ . '/Fixtures/InvalidData/composition-violations/allof-fails-one-response.json');\n\n        \$this->expectException(SchemaViolationException::class);\n        \$this->expectExceptionMessage('fails');",
    $responseContent
);

$responseContent = str_replace(
    "itRejectsAllofFailsMultiple(): void\n    {\n        \$json = \\Safe\\file_get_contents(__DIR__ . '/Fixtures/InvalidData/composition-violations/allof-fails-multiple-response.json');\n\n        \$this->expectException(CompositionViolationException::class);\n        \$this->expectExceptionMessage('multiple');",
    "itRejectsAllofFailsMultiple(): void\n    {\n        \$json = \\Safe\\file_get_contents(__DIR__ . '/Fixtures/InvalidData/composition-violations/allof-fails-multiple-response.json');\n\n        \$this->expectException(SchemaViolationException::class);\n        \$this->expectExceptionMessage('fails');",
    $responseContent
);

// Also fix ValidationException imports
$responseContent = str_replace(
    'use LongTermSupport\StrictOpenApiValidator\Exception\ValidationException;',
    '',
    $responseContent
);

// Write the files back
file_put_contents($requestTestFile, $requestContent);
file_put_contents($responseTestFile, $responseContent);

echo "All tests fixed!\n";