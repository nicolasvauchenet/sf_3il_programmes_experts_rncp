<?php

declare(strict_types=1);

namespace App\Warning;

final readonly class Warning
{
    public function __construct(
        public WarningCode $code,
        public string $message,
        public array $context = [],
        public WarningLevel $level = WarningLevel::Warning,
    ) {
    }

    public static function contentFileMissing(string $path, array $context = []): self
    {
        return new self(
            WarningCode::ContentFileMissing,
            sprintf('Content file missing: %s', $path),
            ['path' => $path] + $context,
        );
    }

    public static function contentJsonInvalid(string $path, string $errorMessage, array $context = []): self
    {
        return new self(
            WarningCode::ContentJsonInvalid,
            sprintf('Invalid JSON in content file (%s): %s', $path, $errorMessage),
            ['path' => $path, 'error' => $errorMessage] + $context,
        );
    }
}
