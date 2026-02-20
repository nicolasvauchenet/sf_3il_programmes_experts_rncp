<?php

declare(strict_types=1);

namespace App\Service\Framework;

use App\Exception\DatasetNotFoundException;
use App\Exception\InvalidDatasetIdentifierException;

final readonly class DatasetPathResolver
{
    public function __construct(
        private string $datasetRootPath,
    ) {
    }

    public function validateAndCreateContext(string $promotion, string $year): FrameworkContext
    {
        $promotion = strtolower(trim($promotion));
        $year = trim($year);

        // strict rules:
        // - promotion: lowercase letters/numbers/underscore only, 2..50
        // - year: YYYY or YYYY-YYYY (e.g. 2025 or 2025-2026)
        if (!preg_match('/^[a-z0-9_]{2,50}$/', $promotion)) {
            throw InvalidDatasetIdentifierException::forPromotion($promotion);
        }

        if (!preg_match('/^\d{4}(-\d{4})?$/', $year)) {
            throw InvalidDatasetIdentifierException::forYear($year);
        }

        if (str_contains($promotion, '..') || str_contains($year, '..') || str_contains(
                $promotion,
                '/'
            ) || str_contains($year, '/')
            || str_contains($promotion, '\\') || str_contains($year, '\\')) {
            throw InvalidDatasetIdentifierException::pathTraversalDetected($promotion.'|'.$year);
        }

        return new FrameworkContext($promotion, $year);
    }

    public function datasetRootPath(): string
    {
        $root = rtrim($this->datasetRootPath, '/');

        if (!is_dir($root)) {
            throw DatasetNotFoundException::rootNotFound($root);
        }

        return $root;
    }

    public function datasetDir(FrameworkContext $context): string
    {
        $dir = $this->datasetRootPath().'/'.$context->datasetKey();

        if (!is_dir($dir)) {
            throw DatasetNotFoundException::datasetDirNotFound($dir);
        }

        return $dir;
    }

    public function structurePath(FrameworkContext $context): string
    {
        $path = $this->datasetDir($context).'/structure.json';

        if (!is_file($path)) {
            throw DatasetNotFoundException::structureNotFound($path);
        }

        return $path;
    }

    public function contentPath(FrameworkContext $context, string $type, string $code): string
    {
        $type = strtolower(trim($type));
        $code = strtolower(trim($code));

        // all lowercase folders/files
        if (!preg_match('/^[a-z0-9_]{2,50}$/', $type)) {
            throw InvalidDatasetIdentifierException::pathTraversalDetected($type);
        }

        // code format: allow a-z0-9_ and dash (skills like bc02-fm03 etc.)
        if (!preg_match('/^[a-z0-9_-]{2,80}$/', $code)) {
            throw InvalidDatasetIdentifierException::pathTraversalDetected($code);
        }

        if (str_contains($type, '..') || str_contains($code, '..') || str_contains($type, '/') || str_contains(
                $code,
                '/'
            )
            || str_contains($type, '\\') || str_contains($code, '\\')) {
            throw InvalidDatasetIdentifierException::pathTraversalDetected($type.'|'.$code);
        }

        return $this->datasetDir($context).sprintf('/content/%s/%s.json', $type, $code);
    }
}
