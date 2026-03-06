<?php

namespace App\Dto\Context;

final readonly class SkillSheet
{
    /**
     * @param array<string,mixed> $meta
     * @param array<int,string> $criteria
     * @param array<string,mixed> $raw
     */
    public function __construct(
        public string $fileCode,
        public string $path,
        public array  $meta,
        public string $description,
        public array  $criteria,
        public array  $raw,
    )
    {
    }

    public function skillCode(): string
    {
        return (string)($this->meta['code'] ?? '');
    }

    public function title(): string
    {
        return (string)($this->meta['title'] ?? '');
    }

    public function academicYear(): string
    {
        return (string)($this->meta['academicYear'] ?? '');
    }

    public function blocCode(): string
    {
        return (string)($this->meta['blocCode'] ?? '');
    }

    public function blocName(): string
    {
        return (string)($this->meta['blocName'] ?? '');
    }
}
