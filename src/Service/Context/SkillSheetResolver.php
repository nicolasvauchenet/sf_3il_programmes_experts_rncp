<?php

namespace App\Service\Context;

use App\Dto\Context\FrameworkStructure;
use App\Dto\Context\ResolvedSkillSheet;
use App\Dto\Context\SkillSheet;

final class SkillSheetResolver
{
    /**
     * @param SkillSheet[] $allSheets
     */
    public function resolve(SkillSheet $sheet, FrameworkStructure $structure, array $allSheets = []): ResolvedSkillSheet
    {
        $skillCode = $this->extractShortSkillCode((string)($sheet->meta['code'] ?? ''));
        $blockCode = (string)($sheet->meta['blocCode'] ?? '');

        $structureSkill = $this->findStructureSkill($structure, $skillCode, $blockCode);

        $moduleFullCodes = $this->extractStringList($structureSkill['modules'] ?? null);
        $evaluationCodes = $this->extractStringList($structureSkill['evaluations'] ?? null);

        $modules = $sheet->modules !== [] ? $sheet->modules : $this->resolveModules($structure, $moduleFullCodes);
        $projects = $sheet->projects;
        $evaluations = $sheet->evaluations !== [] ? $sheet->evaluations : $this->resolveEvaluations($structure, $evaluationCodes);

        return ResolvedSkillSheet::fromSheet(
            sheet: $sheet,
            rncpCode: $this->extractNullableString($structure->meta['rncpCode'] ?? null),
            modules: $modules,
            projects: $projects,
            evaluations: $evaluations,
            relatedSkills: $this->resolveRelatedSkills($structure, $allSheets, $blockCode, $skillCode),
        );
    }

    private function extractShortSkillCode(string $fullCode): string
    {
        $fullCode = trim($fullCode);

        if ($fullCode === '') {
            return '';
        }

        $parts = explode('-', $fullCode);

        return trim((string)end($parts));
    }

    /**
     * @return array<string,mixed>
     */
    private function findStructureSkill(FrameworkStructure $structure, string $skillCode, string $blockCode): array
    {
        foreach ($structure->skills as $skill) {
            if (!is_array($skill)) {
                continue;
            }

            $candidateCode = $this->extractNullableString($skill['code'] ?? null);
            $candidateBlockCode = $this->extractNullableString($skill['blockCode'] ?? $skill['blocCode'] ?? null);

            if ($candidateCode !== $skillCode) {
                continue;
            }

            if ($blockCode !== '' && $candidateBlockCode !== null && $candidateBlockCode !== $blockCode) {
                continue;
            }

            return $skill;
        }

        return [];
    }

    /**
     * @param array<int,string> $moduleFullCodes
     * @return array<int,array{code:string,title:string,fullCode:string}>
     */
    private function resolveModules(FrameworkStructure $structure, array $moduleFullCodes): array
    {
        $index = [];

        foreach ($structure->modules as $module) {
            if (!is_array($module)) {
                continue;
            }

            $fullCode = $this->extractNullableString($module['fullCode'] ?? null);
            if ($fullCode === null) {
                continue;
            }

            $index[$fullCode] = [
                'code' => $this->extractNullableString($module['code'] ?? null) ?? '',
                'title' => $this->extractNullableString($module['title'] ?? null) ?? '',
                'fullCode' => strtolower($fullCode),
            ];
        }

        $resolved = [];

        foreach ($moduleFullCodes as $fullCode) {
            $module = $index[$fullCode] ?? null;

            if (is_array($module)) {
                $resolved[] = $module;
            }
        }

        return $resolved;
    }

    /**
     * @param array<int,string> $evaluationCodes
     * @return array<int,array{code:string,blockCode:string,title:string}>
     */
    private function resolveEvaluations(FrameworkStructure $structure, array $evaluationCodes): array
    {
        $resolved = [];

        foreach ($evaluationCodes as $evaluationCode) {
            foreach ($structure->evaluations as $evaluation) {
                if (!is_array($evaluation)) {
                    continue;
                }

                $code = $this->extractNullableString($evaluation['code'] ?? null);
                if ($code !== $evaluationCode) {
                    continue;
                }

                $resolved[] = [
                    'code' => strtolower($code),
                    'title' => $this->extractNullableString($evaluation['title'] ?? null) ?? '',
                    'blockCode' => $this->extractNullableString($evaluation['blockCode'] ?? $evaluation['blocCode'] ?? null) ?? '',
                ];

                break;
            }
        }

        return $resolved;
    }

    /**
     * @param SkillSheet[] $allSheets
     * @return array<int,array{fileCode:string,code:string,title:string}>
     */
    private function resolveRelatedSkills(
        FrameworkStructure $structure,
        array              $allSheets,
        string             $blockCode,
        string             $currentSkillCode,
    ): array
    {
        $fileCodeByShortCode = [];

        foreach ($allSheets as $sheet) {
            $shortCode = $this->extractShortSkillCode((string)($sheet->meta['code'] ?? ''));
            if ($shortCode === '') {
                continue;
            }

            $fileCodeByShortCode[$shortCode] = $sheet->fileCode;
        }

        $resolved = [];

        foreach ($structure->skills as $skill) {
            if (!is_array($skill)) {
                continue;
            }

            $candidateBlockCode = $this->extractNullableString($skill['blockCode'] ?? $skill['blocCode'] ?? null);
            $code = $this->extractNullableString($skill['code'] ?? null);
            $title = $this->extractNullableString($skill['title'] ?? null);

            if ($candidateBlockCode !== $blockCode || $code === null || $title === null || $code === $currentSkillCode) {
                continue;
            }

            $resolved[] = [
                'fileCode' => $fileCodeByShortCode[$code] ?? strtolower($code),
                'code' => $code,
                'title' => $title,
            ];
        }

        return $resolved;
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
