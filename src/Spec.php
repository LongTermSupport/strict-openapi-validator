<?php

declare(strict_types=1);

namespace LongTermSupport\StrictOpenApiValidator;

use InvalidArgumentException;
use LogicException;

use function Safe\file_get_contents;
use function Safe\json_decode;

/**
 * Represents an OpenAPI specification loaded from a file or array.
 *
 * NOOP Implementation: Currently just loads the spec without validation.
 * Full validation will be implemented in later phases.
 */
final readonly class Spec
{
    /**
     * @param array<mixed> $spec The OpenAPI specification array
     * @param string $sourceFile The source file path (for error reporting)
     */
    private function __construct(
        private array $spec,
        private string $sourceFile,
    ) {
    }

    /**
     * Create a Spec instance from a file.
     *
     * Supports JSON and YAML files (though YAML is not yet implemented).
     *
     * @param string $path Path to the OpenAPI specification file
     *
     * @return self
     *
     * @throws InvalidArgumentException If file doesn't exist or is invalid
     * @throws LogicException If YAML file provided (not yet implemented)
     */
    public static function createFromFile(string $path): self
    {
        if (!\file_exists($path)) {
            throw new InvalidArgumentException(\sprintf('File not found: %s', $path));
        }

        if (!\is_readable($path)) {
            throw new InvalidArgumentException(\sprintf('File is not readable: %s', $path));
        }

        $content = file_get_contents($path);

        // Determine file type by extension
        $extension = \strtolower(\pathinfo($path, PATHINFO_EXTENSION));

        if (\in_array($extension, ['yml', 'yaml'], true)) {
            throw new LogicException('YAML parsing not yet implemented. Use JSON for now.');
        }

        if ('json' !== $extension) {
            throw new InvalidArgumentException(\sprintf(
                'Unsupported file extension: %s. Expected .json, .yml, or .yaml',
                $extension
            ));
        }

        // Parse JSON
        /** @var array<mixed> $spec */
        $spec = json_decode($content, true);

        return new self($spec, $path);
    }

    /**
     * Create a Spec instance from an array.
     *
     * @param array<mixed> $spec The OpenAPI specification as an array
     *
     * @return self
     */
    public static function createFromArray(array $spec): self
    {
        return new self($spec, '<array>');
    }

    /**
     * Get the OpenAPI specification array.
     *
     * @return array<mixed>
     */
    public function getSpec(): array
    {
        return $this->spec;
    }

    /**
     * Get the source file path (or '<array>' if created from array).
     *
     * @return string
     */
    public function getSourceFile(): string
    {
        return $this->sourceFile;
    }
}
