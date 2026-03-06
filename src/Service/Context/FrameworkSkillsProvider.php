<?php

namespace App\Service\Context;

use App\Dto\Context\SkillSheet;

final readonly class FrameworkSkillsProvider
{
    public function __construct(
        private FrameworkFolderScanner $scanner,
        private SkillSheetLoader       $loader,
    )
    {
    }

    /**
     * @return SkillSheet[]
     */
    public function listSkills(string $promotion, string $year): array
    {
        $refs = $this->scanner->listJsonFiles($promotion, $year, 'skills');

        $skills = [];

        foreach ($refs as $ref) {
            try {
                $skills[] = $this->loader->load($ref->fileCode, $ref->path);
            } catch (\Throwable) {
                continue;
            }
        }

        usort($skills, static fn(SkillSheet $a, SkillSheet $b): int => $a->fileCode <=> $b->fileCode);

        return $skills;
    }
}
