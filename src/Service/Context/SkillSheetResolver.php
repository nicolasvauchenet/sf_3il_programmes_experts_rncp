<?php

namespace App\Service\Context;

use App\Dto\Context\FrameworkStructure;
use App\Dto\Context\ResolvedSkillSheet;
use App\Dto\Context\SkillSheet;

final class SkillSheetResolver
{
    public function resolve(SkillSheet $sheet, FrameworkStructure $structure, array $allSheets = []): ResolvedSkillSheet
    {
        $skillCode = $this->extractShortSkillCode((string)($sheet->meta['code'] ?? ''));
        $blockCode = (string)($sheet->meta['blocCode'] ?? '');

        $structureSkill = $this->findStructureSkill($structure, $skillCode, $blockCode);

        $moduleFullCodes = $this->extractStringList($structureSkill['modules'] ?? null);
        $evaluationCodes = $this->extractStringList($structureSkill['evaluations'] ?? null);

        return ResolvedSkillSheet::fromSheet(
            sheet: $sheet,
            rncpCode: $this->extractNullableString($structure->meta['rncpCode'] ?? null),
            modules: $this->resolveModules($structure, $moduleFullCodes),
            evaluations: $this->resolveEvaluations($structure, $evaluationCodes),
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
            $candidateBlockCode = $this->extractNullableString($skill['blockCode'] ?? null);

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
        $resolved = [];

        foreach ($moduleFullCodes as $fullCode) {
            foreach ($structure->modules as $module) {
                if (!is_array($module)) {
                    continue;
                }

                $candidateFullCode = $this->extractNullableString($module['fullCode'] ?? null);
                if ($candidateFullCode !== $fullCode) {
                    continue;
                }

                $resolved[] = [
                    'code' => $this->extractNullableString($module['code'] ?? null) ?? '',
                    'title' => $this->extractNullableString($module['title'] ?? null) ?? '',
                    'fullCode' => $candidateFullCode,
                ];

                break;
            }
        }

        return $resolved;
    }

    /**
     * @param array<int,string> $evaluationCodes
     * @return array<int,array{code:string,blockCode:string}>
     */
    private function resolveEvaluations(FrameworkStructure $structure, array $evaluationCodes): array
    {
        $resolved = [];

        foreach ($evaluationCodes as $wantedCode) {
            foreach ($structure->evaluations as $evaluation) {
                if (!is_array($evaluation)) {
                    continue;
                }

                $code = $this->extractNullableString($evaluation['code'] ?? null);
                if ($code !== $wantedCode) {
                    continue;
                }

                $resolved[] = [
                    'code' => $code,
                    'blockCode' => $this->extractNullableString($evaluation['blockCode'] ?? null) ?? '',
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
        array $allSheets,
        string $blockCode,
        string $currentSkillCode,
    ): array {
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

            $candidateBlockCode = $this->extractNullableString($skill['blockCode'] ?? null);
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
