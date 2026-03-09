<?php

namespace App\Service\Context;

use App\Dto\Context\ResolvedSkillSheet;
use App\Dto\Context\SkillSheet;

final readonly class FrameworkSkillsProvider
{
    public function __construct(
        private FrameworkFolderScanner $scanner,
        private SkillSheetLoader $loader,
        private FrameworkStructureLoader $structureLoader,
        private SkillSheetResolver $resolver,
    ) {
    }

    /**
     * @return ResolvedSkillSheet[]
     */
    public function listSkills(string $promotion, string $year): array
    {
        $refs = $this->scanner->listJsonFiles($promotion, $year, 'skills');
        $structure = $this->structureLoader->load($promotion, $year);

        $sheets = [];

        foreach ($refs as $ref) {
            try {
                $sheets[] = $this->loader->load($ref->fileCode, $ref->path);
            } catch (\Throwable) {
                continue;
            }
        }

        usort(
            $sheets,
            static fn(SkillSheet $a, SkillSheet $b): int => $a->fileCode <=> $b->fileCode
        );

        $resolved = [];

        foreach ($sheets as $sheet) {
            $resolved[] = $this->resolver->resolve($sheet, $structure, $sheets);
        }

        return $resolved;
    }
}
