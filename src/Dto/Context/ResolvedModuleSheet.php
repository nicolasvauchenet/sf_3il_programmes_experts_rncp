<?php

namespace App\Dto\Context;

final readonly class ResolvedModuleSheet
{
    /**
     * @param array<string,mixed> $meta
     * @param array<int,array{code:string,fileCode:string,description:string}> $skills
     * @param array<int,array{code:string,fileCode:string,description:string,criteria:array<int,string>}> $skillsWithCriteria
     * @param array<int,array{code:string,title:string,blockCode:string}> $evaluations
     * @param array<int,array{code:string,title:string,blockCode:string}> $projects
     * @param array<string,mixed> $objectives
     * @param array<string,mixed> $prerequisites
     * @param array<string,mixed> $outline
     * @param array<int,mixed> $exercises
     * @param array<int,mixed> $bibliography
     * @param array<int,string> $teachingMethods
     * @param array<string,mixed> $raw
     */
    public function __construct(
        public string $fileCode,
        public string $path,
        public array  $meta,
        public string $description,
        public array  $objectives,
        public array  $prerequisites,
        public int    $durationDays,
        public int    $durationHours,
        public array  $skills,
        public array  $skillsWithCriteria,
        public array  $evaluations,
        public array  $projects,
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
}
