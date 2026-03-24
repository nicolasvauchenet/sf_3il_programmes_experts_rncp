<?php

namespace App\Dto\Context;

final readonly class ResolvedSkillSheet
{
    /**
     * @param array<string,mixed> $meta
     * @param array<int,string> $criteria
     * @param array<string,mixed> $raw
     * @param array<int,array{code:string,title:string,fullCode:string}> $modules
     * @param array<int,array{code:string,blockCode:string,title:string}> $evaluations
     * @param array<int,array{fileCode:string,code:string,title:string}> $relatedSkills
     */
    public function __construct(
        public string $fileCode,
        public string $path,
        public array $meta,
        public string $description,
        public array $criteria,
        public array $raw,
        public ?string $rncpCode,
        public array $modules,
        public array $evaluations,
        public array $relatedSkills,
    ) {
    }

    public static function fromSheet(
        SkillSheet $sheet,
        ?string $rncpCode,
        array $modules,
        array $evaluations,
        array $relatedSkills,
    ): self {
        return new self(
            fileCode: $sheet->fileCode,
            path: $sheet->path,
            meta: $sheet->meta,
            description: $sheet->description,
            criteria: $sheet->criteria,
            raw: $sheet->raw,
            rncpCode: $rncpCode,
            modules: $modules,
            evaluations: $evaluations,
            relatedSkills: $relatedSkills,
        );
    }

    public function skillCode(): string
    {
        $fullCode = (string)($this->meta['code'] ?? '');

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
}
