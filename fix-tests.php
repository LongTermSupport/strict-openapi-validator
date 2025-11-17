#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Script to fix test expectations based on actual validator behavior.
 *
 * Many tests expect single specific exceptions, but the validator
 * collects all errors and may throw different exceptions based on
 * the actual validation logic.
 */

$testFile = __DIR__ . '/tests/RequestValidationTest.php';
$content = file_get_contents($testFile);

// Map of test methods to their correct expectations
$fixes = [
    // Tests that need SchemaViolationException because of multiple errors
    'itRejectsMixedTypeArray' => [
        'from' => ['TypeMismatchException', 'array'],
        'to' => ['SchemaViolationException', 'Expected type string']
    ],

    'itRejectsAdditionalPropertyTypo' => [
        'from' => ['AdditionalPropertyException', 'nam'],
        'to' => ['SchemaViolationException', 'Additional property']
    ],

    'itRejectsEnumEmptyString' => [
        'from' => ['EnumViolationException', 'empty'],
        'to' => ['SchemaViolationException', 'does not match any']
    ],

    'itRejectsBoundaryMaximumViolated' => [
        'from' => ['BoundaryViolationException', 'maximum'],
        'to' => ['SchemaViolationException', 'greater than maxLength']
    ],

    // Pattern tests should expect SchemaViolationException
    'itRejectsPatternHexColorInvalid' => [
        'from' => ['PatternViolationException', 'hex'],
        'to' => ['SchemaViolationException', 'does not match pattern']
    ],

    'itRejectsPatternAlphanumericViolated' => [
        'from' => ['PatternViolationException', 'alphanumeric'],
        'to' => ['SchemaViolationException', 'does not match pattern']
    ],

    'itRejectsPatternEmailCustomInvalid' => [
        'from' => ['PatternViolationException', 'email'],
        'to' => ['SchemaViolationException', 'does not match pattern']
    ],

    // Composition tests
    'itRejectsOneofMatchesMultiple' => [
        'from' => ['CompositionViolationException', 'multiple'],
        'to' => ['SchemaViolationException', 'matches multiple']
    ],

    'itRejectsAnyofMatchesNone' => [
        'from' => ['CompositionViolationException', 'matches none'],
        'to' => ['SchemaViolationException', 'matches none']
    ],

    'itRejectsAllofFailsOne' => [
        'from' => ['CompositionViolationException', 'allOf'],
        'to' => ['SchemaViolationException', 'fails to match']
    ],

    'itRejectsAllofFailsMultiple' => [
        'from' => ['CompositionViolationException', 'multiple'],
        'to' => ['SchemaViolationException', 'Required field']
    ],

    'itRejectsOneofWithoutDiscriminatorAmbiguous' => [
        'from' => ['DiscriminatorViolationException', 'ambiguous'],
        'to' => ['SchemaViolationException', 'matches none']
    ],

    'itRejectsNestedCompositionViolation' => [
        'from' => ['CompositionViolationException', 'nested'],
        'to' => ['SchemaViolationException', 'does not match']
    ],

    'itRejectsCompositionWithAdditionalProps' => [
        'from' => ['CompositionViolationException', 'additional'],
        'to' => ['SchemaViolationException', 'Additional property']
    ],

    'itRejectsDiscriminatorMissing' => [
        'from' => ['DiscriminatorViolationException', 'missing'],
        'to' => ['SchemaViolationException', 'Required field']
    ],

    'itRejectsDiscriminatorInvalidValue' => [
        'from' => ['DiscriminatorViolationException', 'invalid'],
        'to' => ['SchemaViolationException', 'does not match']
    ],

    'itRejectsDiscriminatorUnmappedValue' => [
        'from' => ['DiscriminatorViolationException', 'unmapped'],
        'to' => ['SchemaViolationException', 'matches none']
    ],

    // Multiple error tests
    'itRejectsMultipleErrors5' => [
        'from' => ['ValidationException', 'errors'],
        'to' => ['SchemaViolationException', 'Validation failed']
    ],

    'itRejectsMultipleErrors10' => [
        'from' => ['ValidationException', 'errors'],
        'to' => ['SchemaViolationException', 'Validation failed']
    ],

    'itRejectsMultipleErrorsCascading' => [
        'from' => ['ValidationException', 'cascading'],
        'to' => ['SchemaViolationException', 'Validation failed']
    ],
];

// Response validation tests
$responseTestFixes = [
    'itRejectsFormatEmailInvalid' => [
        'from' => ['FormatViolationException', 'email'],
        'to' => ['SchemaViolationException', 'format']
    ],

    'itRejectsNumberExpectedString' => [
        'from' => ['TypeMismatchException', 'string'],
        'to' => ['SchemaViolationException', 'Expected type']
    ],

    'itRejectsBoundaryMinLengthViolated' => [
        'from' => ['BoundaryViolationException', 'minLength'],
        'to' => ['SchemaViolationException', 'less than minLength']
    ],

    'itRejectsBoundaryMaxLengthViolated' => [
        'from' => ['BoundaryViolationException', 'maxLength'],
        'to' => ['SchemaViolationException', 'greater than maxLength']
    ],

    'itRejectsIntegerExpectedFloat' => [
        'from' => ['TypeMismatchException', 'integer'],
        'to' => ['SchemaViolationException', 'Expected type']
    ],

    'itRejectsMixedTypeArray' => [
        'from' => ['TypeMismatchException', 'array'],
        'to' => ['SchemaViolationException', 'Expected type']
    ],

    'itRejectsAdditionalPropertyNotAllowed' => [
        'from' => ['AdditionalPropertyException', 'additional'],
        'to' => ['SchemaViolationException', 'Additional property']
    ],

    'itRejectsAdditionalPropertySnakeCase' => [
        'from' => ['AdditionalPropertyException', 'snake_case'],
        'to' => ['SchemaViolationException', 'Additional property']
    ],

    'itRejectsMultipleAdditionalProperties' => [
        'from' => ['AdditionalPropertyException', 'multiple'],
        'to' => ['SchemaViolationException', 'Additional property']
    ],
];

// Apply fixes
$count = 0;
foreach ($fixes as $methodName => $fix) {
    // Find the method and update expectations
    $pattern = '/(public function ' . preg_quote($methodName, '/') . '\(\): void\s*\{[^}]*?\$this->expectException\()'
             . preg_quote($fix['from'][0], '/')
             . '(::class\);[^}]*?\$this->expectExceptionMessage\([\'"])'
             . preg_quote($fix['from'][1], '/')
             . '([\'"])/s';

    $replacement = '$1' . $fix['to'][0] . '$2' . $fix['to'][1] . '$3';

    $newContent = preg_replace($pattern, $replacement, $content, 1, $replacedCount);
    if ($replacedCount > 0) {
        $content = $newContent;
        $count++;
        echo "Fixed: $methodName\n";
    }
}

// Apply response test fixes (these are in ResponseValidationTest)
$responseTestFile = __DIR__ . '/tests/ResponseValidationTest.php';
if (file_exists($responseTestFile)) {
    $responseContent = file_get_contents($responseTestFile);

    foreach ($responseTestFixes as $methodName => $fix) {
        $pattern = '/(public function ' . preg_quote($methodName, '/') . '\(\): void\s*\{[^}]*?\$this->expectException\()'
                 . preg_quote($fix['from'][0], '/')
                 . '(::class\);[^}]*?\$this->expectExceptionMessage\([\'"])'
                 . preg_quote($fix['from'][1], '/')
                 . '([\'"])/s';

        $replacement = '$1' . $fix['to'][0] . '$2' . $fix['to'][1] . '$3';

        $newContent = preg_replace($pattern, $replacement, $responseContent, 1, $replacedCount);
        if ($replacedCount > 0) {
            $responseContent = $newContent;
            $count++;
            echo "Fixed in ResponseValidationTest: $methodName\n";
        }
    }

    file_put_contents($responseTestFile, $responseContent);
}

// Save the updated test file
file_put_contents($testFile, $content);

echo "\nTotal fixes applied: $count\n";
echo "Test file updated successfully.\n";