<?php

namespace App\Service\Context;

use App\Dto\Context\FrameworkStructure;
use App\Dto\Context\ProjectSheet;
use App\Dto\Context\ResolvedProjectSheet;
use App\Dto\Context\ResolvedSkillSheet;

final class ProjectSheetResolver
{
    /**
     * @param array<string,ResolvedSkillSheet> $skillsIndex
     */
    public function resolve(ProjectSheet $sheet, FrameworkStructure $structure, array $skillsIndex = []): ResolvedProjectSheet
    {
        $skillsWithCriteria = $this->resolveSkillsWithCriteria($sheet, $skillsIndex);
        $evaluations = $sheet->evaluations !== [] ? $sheet->evaluations : $this->resolveEvaluations($sheet, $structure);

        return new ResolvedProjectSheet(
            fileCode: $sheet->fileCode,
            path: $sheet->path,
            meta: $sheet->meta,
            description: (string)($sheet->raw['description'] ?? ''),
            objectives: $sheet->objectives,
            prerequisites: $sheet->prerequisites,
            durationDays: $sheet->durationDays(),
            durationHours: $sheet->durationHours(),
            skills: $sheet->skills,
            skillsWithCriteria: $skillsWithCriteria,
            modules: $sheet->modules,
            evaluations: $evaluations,
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
     *     fileCode:string,
     *     description:string,
     *     criteria:array<int,string>
     * }>
     */
    private function resolveSkillsWithCriteria(ProjectSheet $sheet, array $skillsIndex): array
    {
        $resolved = [];

        foreach ($sheet->skills as $skill) {
            $code = (string)($skill['code'] ?? '');
            $fileCode = (string)($skill['fileCode'] ?? '');
            $description = (string)($skill['description'] ?? '');

            if ($code === '' || $description === '') {
                continue;
            }

            $key = $this->buildSkillKey($sheet->blocCode(), $code);
            $resolvedSkill = $skillsIndex[$key] ?? null;

            $criteria = [];
            if ($resolvedSkill instanceof ResolvedSkillSheet) {
                $criteria = $resolvedSkill->criteria;

                if ($fileCode === '') {
                    $fileCode = $resolvedSkill->fileCode;
                }
            }

            if ($fileCode === '') {
                $fileCode = strtolower($code);
            }

            $resolved[] = [
                'code' => $code,
                'fileCode' => $fileCode,
                'description' => $description,
                'criteria' => $criteria,
            ];
        }

        return $resolved;
    }

    /**
     * @return array<int,array{code:string,title:string,blockCode:string}>
     */
    private function resolveEvaluations(ProjectSheet $sheet, FrameworkStructure $structure): array
    {
        $projectCode = $sheet->projectCode();

        if ($projectCode === '') {
            return [];
        }

        $evaluationCodes = $this->resolveEvaluationCodesFromProjectDefinition($projectCode, $structure);

        if ($evaluationCodes === []) {
            $evaluationCodes = $this->resolveEvaluationCodesFromLegacyModulesLink($projectCode, $structure);
        }

        if ($evaluationCodes === []) {
            return [];
        }

        $evaluationsIndex = $this->indexEvaluationsByCode($structure);
        $resolved = [];

        foreach ($evaluationCodes as $evaluationCode) {
            $evaluation = $evaluationsIndex[$this->normalizeCode($evaluationCode)] ?? null;

            if (!is_array($evaluation)) {
                continue;
            }

            $code = $this->extractNullableString($evaluation['code'] ?? null);
            if ($code === null) {
                continue;
            }

            $title = $this->extractNullableString($evaluation['title'] ?? null) ?? '';
            $blockCode = $this->extractNullableString($evaluation['blockCode'] ?? $evaluation['blocCode'] ?? null) ?? '';

            $resolved[] = [
                'code' => strtolower($code),
                'title' => $title,
                'blockCode' => $blockCode,
            ];
        }

        return $resolved;
    }

    /**
     * @return array<int,string>
     */
    private function resolveEvaluationCodesFromProjectDefinition(string $projectCode, FrameworkStructure $structure): array
    {
        foreach ($structure->projects as $project) {
            if (!is_array($project)) {
                continue;
            }

            $code = $this->extractNullableString($project['code'] ?? null);
            if ($code === null) {
                continue;
            }

            if ($this->normalizeCode($code) !== $this->normalizeCode($projectCode)) {
                continue;
            }

            return $this->extractStringList($project['evaluations'] ?? null);
        }

        return [];
    }

    /**
     * @return array<int,string>
     */
    private function resolveEvaluationCodesFromLegacyModulesLink(string $projectCode, FrameworkStructure $structure): array
    {
        $resolved = [];

        foreach ($structure->evaluations as $evaluation) {
            if (!is_array($evaluation)) {
                continue;
            }

            $modules = $this->extractStringList($evaluation['modules'] ?? null);

            if (!in_array($projectCode, $modules, true)) {
                continue;
            }

            $code = $this->extractNullableString($evaluation['code'] ?? null);
            if ($code === null) {
                continue;
            }

            $resolved[] = $code;
        }

        return array_values(array_unique($resolved));
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private function indexEvaluationsByCode(FrameworkStructure $structure): array
    {
        $index = [];

        foreach ($structure->evaluations as $evaluation) {
            if (!is_array($evaluation)) {
                continue;
            }

            $code = $this->extractNullableString($evaluation['code'] ?? null);
            if ($code === null) {
                continue;
            }

            $index[$this->normalizeCode($code)] = $evaluation;
        }

        return $index;
    }

    private function buildSkillKey(string $blocCode, string $skillCode): string
    {
        return strtolower(trim($blocCode) . '|' . trim($skillCode));
    }

    private function normalizeCode(string $value): string
    {
        return strtoupper(trim($value));
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
