<?php

namespace App\Dto\Context;

final readonly class ResolvedSkillSheet
{
    /**
     * @param array<string,mixed> $meta
     * @param array<int,string> $criteria
     * @param array<int,array{code:string,title:string,fullCode:string}> $modules
     * @param array<int,array{code:string,title:string,blockCode:string}> $projects
     * @param array<int,array{code:string,title:string,blockCode:string}> $evaluations
     * @param array<int,array{fileCode:string,code:string,title:string}> $relatedSkills
     * @param array<string,mixed> $raw
     */
    public function __construct(
        public string $fileCode,
        public string $path,
        public array  $meta,
        public string $description,
        public array  $criteria,
        public array  $modules,
        public array  $projects,
        public array  $evaluations,
        public array  $relatedSkills,
        public array  $raw,
    )
    {
    }

    public static function fromSheet(
        SkillSheet $sheet,
        ?string    $rncpCode = null,
        array      $modules = [],
        array      $projects = [],
        array      $evaluations = [],
        array      $relatedSkills = [],
    ): self
    {
        $meta = $sheet->meta;

        if ($rncpCode !== null && $rncpCode !== '') {
            $meta['rncpCode'] = $rncpCode;
        }

        return new self(
            fileCode: $sheet->fileCode,
            path: $sheet->path,
            meta: $meta,
            description: $sheet->description,
            criteria: $sheet->criteria,
            modules: $modules,
            projects: $projects,
            evaluations: $evaluations,
            relatedSkills: $relatedSkills,
            raw: $sheet->raw,
        );
    }

    public function skillCode(): string
    {
        $fullCode = $this->fullSkillCode();

        if ($fullCode === '') {
            return '';
        }

        $parts = explode('-', $fullCode);

        return (string)end($parts);
    }

    public function fullSkillCode(): string
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

    public function rncpCode(): string
    {
        return (string)($this->meta['rncpCode'] ?? '');
    }
}
