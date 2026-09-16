<?php

namespace App\Dto\Context;

final readonly class FrameworkStructure
{
    /**
     * @param array<string,mixed> $meta
     * @param array<int,array<string,mixed>> $modules
     * @param array<int,array<string,mixed>> $skills
     * @param array<int,array<string,mixed>> $blocks
     * @param array<int,array<string,mixed>> $evaluations
     * @param array<int,array<string,mixed>> $projects
     * @param array<string,mixed> $raw
     */
    public function __construct(
        public array $meta,
        public array $modules,
        public array $skills,
        public array $blocks,
        public array $evaluations,
        public array $projects,
        public array $raw,
    )
    {
    }

    public function certificationName(): string
    {
        return (string)($this->meta['certificationName'] ?? '');
    }

    public function datasetCode(): string
    {
        return (string)($this->meta['datasetCode'] ?? '');
    }
}
