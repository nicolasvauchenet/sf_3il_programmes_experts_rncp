<?php

namespace App\Service\Context;

use App\Dto\Context\FrameworkStructure;
use App\Dto\Context\ModuleSheet;
use App\Dto\Context\ResolvedSkillSheet;

final class ModuleSheetResolver
{
    /**
     * @param array<string,ResolvedSkillSheet> $skillsIndex
     */
    public function resolve(ModuleSheet $sheet, FrameworkStructure $structure, array $skillsIndex = []): ModuleSheet
    {
        $skillsWithCriteria = $this->resolveSkillsWithCriteria($sheet, $skillsIndex);
        $evaluations = $this->resolveEvaluations($sheet, $structure);

        return new ModuleSheet(
            fileCode: $sheet->fileCode,
            path: $sheet->path,
            meta: $sheet->meta,
            skills: $sheet->skills,
            skillsWithCriteria: $skillsWithCriteria,
            evaluations: $evaluations,
            objectives: $sheet->objectives,
            prerequisites: $sheet->prerequisites,
            outline: $sheet->outline,
            exercises: $sheet->exercises,
            bibliography: $sheet->bibliography,
            teachingMethods: $sheet->teachingMethods,
            raw: $sheet->raw,
        );
    }

    /**
     * @param array<string,ResolvedSkillSheet> $skillsIndex
     * @return array<int,array{
     *     code:string,
     *     description:string,
     *     criteria:array<int,string>
     * }>
     */
    private function resolveSkillsWithCriteria(ModuleSheet $sheet, array $skillsIndex): array
    {
        $resolved = [];

        foreach ($sheet->skills as $skill) {
            $code = (string)($skill['code'] ?? '');
            $description = (string)($skill['description'] ?? '');

            if ($code === '' || $description === '') {
                continue;
            }

            $key = $this->buildSkillKey($sheet->blocCode(), $code);
            $resolvedSkill = $skillsIndex[$key] ?? null;

            $criteria = [];
            if ($resolvedSkill instanceof ResolvedSkillSheet) {
                $criteria = $resolvedSkill->criteria;
            }

            $resolved[] = [
                'code' => $code,
                'description' => $description,
                'criteria' => $criteria,
            ];
        }

        return $resolved;
    }

    /**
     * @return array<int,array{code:string,blockCode:string}>
     */
    private function resolveEvaluations(ModuleSheet $sheet, FrameworkStructure $structure): array
    {
        $resolved = [];
        $moduleCode = $sheet->moduleCode();

        if ($moduleCode === '') {
            return [];
        }

        foreach ($structure->evaluations as $evaluation) {
            if (!is_array($evaluation)) {
                continue;
            }

            $modules = $this->extractStringList($evaluation['modules'] ?? null);
            if (!in_array($moduleCode, $modules, true)) {
                continue;
            }

            $code = $this->extractNullableString($evaluation['code'] ?? null);
            if ($code === null) {
                continue;
            }

            $title = $this->extractNullableString($evaluation['title'] ?? null) ?? '';

            $resolved[] = [
                'code' => $code,
                'title' => $title,
                'blockCode' => $this->extractNullableString($evaluation['blockCode'] ?? null) ?? '',
            ];
        }

        return $resolved;
    }

    private function buildSkillKey(string $blocCode, string $skillCode): string
    {
        return strtolower(trim($blocCode) . '|' . trim($skillCode));
    }

    /**
     * @param mixed $value
     * @return array<int,string>
     */
    private function extractStringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $out = [];

        foreach ($value as $item) {
            if (!is_string($item)) {
                continue;
            }

            $item = trim($item);
            if ($item !== '') {
                $out[] = $item;
            }
        }

        return array_values(array_unique($out));
    }

    private function extractNullableString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
