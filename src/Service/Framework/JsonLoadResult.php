<?php

declare(strict_types=1);

namespace App\Service\Framework;

final readonly class JsonLoadResult
{
    public function __construct(
        public string $path,
        public bool $exists,
        public ?int $mtime,
        public bool $ok,
        public mixed $data,
        public ?string $error,
    ) {
    }

    public static function missing(string $path): self
    {
        return new self(
            path: $path,
            exists: false,
            mtime: null,
            ok: false,
            data: null,
            error: null,
        );
    }

    public static function invalid(string $path, ?int $mtime, string $error): self
    {
        return new self(
            path: $path,
            exists: true,
            mtime: $mtime,
            ok: false,
            data: null,
            error: $error,
        );
    }

    public static function success(string $path, ?int $mtime, mixed $data): self
    {
        return new self(
            path: $path,
            exists: true,
            mtime: $mtime,
            ok: true,
            data: $data,
            error: null,
        );
    }
}
