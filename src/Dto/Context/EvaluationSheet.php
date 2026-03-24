<?php

namespace App\Dto\Context;

final readonly class EvaluationSheet
{
    /**
     * @param array<string,mixed> $meta
     * @param array<int,array{code:string,description:string}> $skills
     * @param array<int,array{
     *     code:string,
     *     title:string,
     *     description:string,
     *     indicators:array<int,string>
     * }> $criteria
     * @param array<int,array{
     *     code:string,
     *     description:string,
     *     criteria:array<int,array{
     *         code:string,
     *         title:string,
     *         description:string,
     *         indicators:array<int,string>
     *     }>
     * }> $skillsWithCriteria
     * @param array<int,array{
     *     code:string,
     *     title:string,
     *     blockCode:string
     * }> $modules
     * @param array<string,mixed> $modalities
     * @param array<string,mixed> $exam
     * @param array<string,mixed> $raw
     */
    public function __construct(
        public string $fileCode,
        public string $path,
        public array  $meta,
        public int    $evaluationNumber,
        public string $description,
        public array  $skills,
        public array  $criteria,
        public array  $skillsWithCriteria,
        public array  $modules,
        public array  $modalities,
        public array  $exam,
        public array  $raw,
    )
    {
    }

    public function evaluationCode(): string
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

    public function rncpCode(): string
    {
        return (string)($this->meta['rncpCode'] ?? '');
    }

    public function blocCode(): string
    {
        return (string)($this->meta['blocCode'] ?? '');
    }

    public function blocName(): string
    {
        return (string)($this->meta['blocName'] ?? '');
    }

    public function format(): string
    {
        return (string)($this->modalities['format'] ?? '');
    }

    public function delivery(): string
    {
        return (string)($this->modalities['delivery'] ?? '');
    }

    public function totalDuration(): string
    {
        return (string)($this->modalities['totalDuration'] ?? '');
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function examParts(): array
    {
        $parts = $this->exam['parts'] ?? [];

        return is_array($parts) ? $parts : [];
    }

    public function examThreshold(): int
    {
        return (int)($this->exam['validation']['evaluationThreshold'] ?? 0);
    }

    public function compensationMin(): int
    {
        return (int)($this->exam['validation']['compensationMin'] ?? 0);
    }

    public function remedialBelow(): int
    {
        return (int)($this->exam['validation']['remedialBelow'] ?? 0);
    }

    public function blockThreshold(): int
    {
        return (int)($this->exam['validation']['blockThreshold'] ?? 0);
    }
}
