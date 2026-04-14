<?php

namespace App\Service\Context\Provider;

use App\Dto\Context\ProjectSheet;
use App\Dto\Context\ResolvedSkillSheet;
use App\Service\Context\FrameworkFolderScanner;
use App\Service\Context\Loader\FrameworkStructureLoader;
use App\Service\Context\Loader\ProjectSheetLoader;
use App\Service\Context\ProjectSheetResolver;

final readonly class FrameworkProjectsProvider
{
    public function __construct(
        private FrameworkFolderScanner   $scanner,
        private ProjectSheetLoader       $loader,
        private FrameworkSkillsProvider  $skillsProvider,
        private FrameworkStructureLoader $structureLoader,
        private ProjectSheetResolver     $resolver,
    )
    {
    }

    /**
     * @return ProjectSheet[]
     */
    public function listProjects(string $promotion, string $year): array
    {
        $refs = $this->scanner->listJsonFiles($promotion, $year, 'modules');
        $structure = $this->structureLoader->load($promotion, $year);
        $resolvedSkills = $this->skillsProvider->listSkills($promotion, $year);
        $skillsIndex = $this->indexSkills($resolvedSkills);

        $projects = [];

        foreach ($refs as $ref) {
            try {
                $project = $this->loader->load($ref->fileCode, $ref->path);
                $projects[] = $this->resolver->resolve($project, $structure, $skillsIndex);
            } catch (\Throwable) {
                continue;
            }
        }

        usort(
            $projects,
            static fn(ProjectSheet $a, ProjectSheet $b): int => [$a->blocCode(), $a->fileCode] <=> [$b->blocCode(), $b->fileCode]
        );

        return $projects;
    }

    /**
     * @param ResolvedSkillSheet[] $skills
     * @return array<string,ResolvedSkillSheet>
     */
    private function indexSkills(array $skills): array
    {
        $index = [];

        foreach ($skills as $skill) {
            $key = $this->buildSkillKey($skill->blocCode(), $skill->skillCode());
            $index[$key] = $skill;
        }

        return $index;
    }

    private function buildSkillKey(string $blocCode, string $skillCode): string
    {
        return strtolower(trim($blocCode) . '|' . trim($skillCode));
    }
}
