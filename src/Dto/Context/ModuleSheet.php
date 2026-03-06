<?php

namespace App\Dto\Context;

final readonly class ModuleSheet
{
    /**
     * @param array<string,mixed> $meta
     * @param array<int,array{code:string,description:string}> $skills
     * @param array<string,mixed> $objectives
     * @param array<string,mixed> $prerequisites
     * @param array<string,mixed> $outline
     * @param array<int,array<string,mixed>> $exercises
     * @param array<int,array<string,mixed>> $bibliography
     * @param array<int,string> $teachingMethods
     * @param array<string,mixed> $raw
     */
    public function __construct(
        public string $fileCode,
        public string $path,
        public array  $meta,
        public array  $skills,
        public array  $objectives,
        public array  $prerequisites,
        public array  $outline,
        public array  $exercises,
        public array  $bibliography,
        public array  $teachingMethods,
        public array  $raw,
    )
    {
    }

    public function moduleCode(): string
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

    public function durationDays(): int
    {
        return (int)($this->meta['durationDays'] ?? 0);
    }

    public function durationHours(): int
    {
        return (int)($this->meta['durationHours'] ?? 0);
    }
}
