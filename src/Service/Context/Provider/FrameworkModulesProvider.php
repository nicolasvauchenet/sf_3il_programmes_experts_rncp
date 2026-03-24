<?php

namespace App\Service\Context\Provider;

use App\Dto\Context\ModuleSheet;
use App\Dto\Context\ResolvedSkillSheet;
use App\Service\Context\FrameworkFolderScanner;
use App\Service\Context\Loader\FrameworkStructureLoader;
use App\Service\Context\Loader\ModuleSheetLoader;
use App\Service\Context\ModuleSheetResolver;

final readonly class FrameworkModulesProvider
{
    public function __construct(
        private FrameworkFolderScanner   $scanner,
        private ModuleSheetLoader        $loader,
        private FrameworkSkillsProvider  $skillsProvider,
        private FrameworkStructureLoader $structureLoader,
        private ModuleSheetResolver      $resolver,
    )
    {
    }

    /**
     * @return ModuleSheet[]
     */
    public function listModules(string $promotion, string $year): array
    {
        $refs = $this->scanner->listJsonFiles($promotion, $year, 'modules');
        $structure = $this->structureLoader->load($promotion, $year);
        $resolvedSkills = $this->skillsProvider->listSkills($promotion, $year);
        $skillsIndex = $this->indexSkills($resolvedSkills);

        $modules = [];

        foreach ($refs as $ref) {
            try {
                $module = $this->loader->load($ref->fileCode, $ref->path);
                $modules[] = $this->resolver->resolve($module, $structure, $skillsIndex);
            } catch (\Throwable) {
                continue;
            }
        }

        usort($modules, static fn(ModuleSheet $a, ModuleSheet $b): int => $a->fileCode <=> $b->fileCode);

        return $modules;
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
