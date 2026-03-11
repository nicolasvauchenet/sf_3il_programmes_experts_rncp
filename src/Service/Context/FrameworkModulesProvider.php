<?php

namespace App\Service\Context;

use App\Dto\Context\ModuleSheet;
use App\Dto\Context\ResolvedSkillSheet;

final readonly class FrameworkModulesProvider
{
    public function __construct(
        private FrameworkFolderScanner  $scanner,
        private ModuleSheetLoader       $loader,
        private FrameworkSkillsProvider $skillsProvider,
    )
    {
    }

    /**
     * @return ModuleSheet[]
     */
    public function listModules(string $promotion, string $year): array
    {
        $refs = $this->scanner->listJsonFiles($promotion, $year, 'modules');
        $resolvedSkills = $this->skillsProvider->listSkills($promotion, $year);
        $skillsIndex = $this->indexSkills($resolvedSkills);

        $modules = [];

        foreach ($refs as $ref) {
            try {
                $module = $this->loader->load($ref->fileCode, $ref->path);
                $modules[] = $this->enrichModuleWithCriteria($module, $skillsIndex);
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

    /**
     * @param array<string,ResolvedSkillSheet> $skillsIndex
     */
    private function enrichModuleWithCriteria(ModuleSheet $module, array $skillsIndex): ModuleSheet
    {
        $skillsWithCriteria = [];

        foreach ($module->skills as $skill) {
            $code = (string)($skill['code'] ?? '');
            $description = (string)($skill['description'] ?? '');

            if ($code === '' || $description === '') {
                continue;
            }

            $key = $this->buildSkillKey($module->blocCode(), $code);
            $resolvedSkill = $skillsIndex[$key] ?? null;

            $criteria = [];
            if ($resolvedSkill instanceof ResolvedSkillSheet) {
                $criteria = $resolvedSkill->criteria;
            }

            $skillsWithCriteria[] = [
                'code' => $code,
                'description' => $description,
                'criteria' => $criteria,
            ];
        }

        return new ModuleSheet(
            fileCode: $module->fileCode,
            path: $module->path,
            meta: $module->meta,
            skills: $module->skills,
            skillsWithCriteria: $skillsWithCriteria,
            objectives: $module->objectives,
            prerequisites: $module->prerequisites,
            outline: $module->outline,
            exercises: $module->exercises,
            bibliography: $module->bibliography,
            teachingMethods: $module->teachingMethods,
            raw: $module->raw,
        );
    }

    private function buildSkillKey(string $blocCode, string $skillCode): string
    {
        return strtolower(trim($blocCode) . '|' . trim($skillCode));
    }
}
