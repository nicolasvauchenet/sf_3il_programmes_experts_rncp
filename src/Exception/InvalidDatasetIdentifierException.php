<?php

declare(strict_types=1);

namespace App\Exception;

final class InvalidDatasetIdentifierException extends FrameworkException
{
    public static function forPromotion(string $promotion): self
    {
        return new self(sprintf('Invalid promotion "%s".', $promotion));
    }

    public static function forYear(string $year): self
    {
        return new self(sprintf('Invalid year "%s".', $year));
    }

    public static function pathTraversalDetected(string $value): self
    {
        return new self(sprintf('Path traversal detected for "%s".', $value));
    }
}
