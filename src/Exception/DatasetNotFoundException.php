<?php

declare(strict_types=1);

namespace App\Exception;

final class DatasetNotFoundException extends FrameworkException
{
    public static function rootNotFound(string $rootPath): self
    {
        return new self(sprintf('Dataset root not found: %s', $rootPath));
    }

    public static function datasetDirNotFound(string $datasetDir): self
    {
        return new self(sprintf('Dataset directory not found: %s', $datasetDir));
    }

    public static function structureNotFound(string $structurePath): self
    {
        return new self(sprintf('structure.json not found: %s', $structurePath));
    }
}
