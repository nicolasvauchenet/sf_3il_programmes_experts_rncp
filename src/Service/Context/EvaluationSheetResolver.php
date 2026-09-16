<?php

namespace App\Service\Context;

use App\Dto\Context\EvaluationSheet;
use App\Dto\Context\FrameworkStructure;
use App\Dto\Context\ResolvedEvaluationSheet;

final class EvaluationSheetResolver
{
    public function resolve(EvaluationSheet $sheet, FrameworkStructure $structure): ResolvedEvaluationSheet
    {
        $modules = $sheet->modules !== [] ? $sheet->modules : $this->resolveModules($sheet, $structure);

        return new ResolvedEvaluationSheet(
            fileCode: $sheet->fileCode,
            path: $sheet->path,
            meta: $sheet->meta,
            evaluationNumber: $sheet->evaluationNumber,
            description: $sheet->description,
            skills: $sheet->skills,
            criteria: $sheet->criteria,
            skillsWithCriteria: $sheet->skillsWithCriteria,
            modules: $this->uniqueReferences($modules),
            projects: $this->uniqueReferences($sheet->projects),
            modalities: $sheet->modalities,
            exam: $sheet->exam,
            raw: $sheet->raw,
        );
    }

    /**
     * @return array<int,array{code:string,title:string,blockCode:string}>
     */
    private function resolveModules(EvaluationSheet $sheet, FrameworkStructure $structure): array
    {
        $resolved = [];
        $evaluationCode = $this->extractShortEvaluationCode($sheet);

        if ($evaluationCode === '') {
            return [];
        }

        $moduleIndex = $this->buildModulesIndex($structure);

        foreach ($structure->evaluations as $evaluation) {
            if (!is_array($evaluation)) {
                continue;
            }

            $code = $this->extractNullableString($evaluation['code'] ?? null);
            if ($code !== $evaluationCode) {
                continue;
            }

            $modules = $this->extractStringList($evaluation['modules'] ?? null);

            foreach ($modules as $fullCode) {
                $module = $moduleIndex[$fullCode] ?? null;

                if ($module === null) {
                    continue;
                }

                $resolved[] = $module;
            }

            break;
        }

        return $resolved;
    }

    /**
     * @return array<string,array{code:string,title:string,blockCode:string}>
     */
    private function buildModulesIndex(FrameworkStructure $structure): array
    {
        $index = [];

        foreach ($structure->modules as $module) {
            if (!is_array($module)) {
                continue;
            }

            $fullCode = $this->extractNullableString($module['fullCode'] ?? null);
            $code = $this->extractNullableString($module['code'] ?? null);
            $blockCode = $this->extractNullableString($module['blockCode'] ?? $module['blocCode'] ?? null);

            if ($fullCode === null || $code === null || $blockCode === null) {
                continue;
            }

            $index[$fullCode] = [
                'code' => strtolower($fullCode),
                'title' => $this->extractNullableString($module['title'] ?? null) ?? '',
                'blockCode' => $blockCode,
            ];
        }

        return $index;
    }

    private function extractShortEvaluationCode(EvaluationSheet $sheet): string
    {
        $metaCode = trim($sheet->evaluationCode());

        if ($metaCode === '') {
            return strtoupper(trim($sheet->fileCode));
        }

        if (preg_match('/(EC\d{2})$/i', $metaCode, $matches) === 1) {
            return strtoupper($matches[1]);
        }

        return strtoupper(trim($sheet->fileCode));
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

    /**
     * @param array<int,array{code:string,title:string,blockCode:string}> $references
     * @return array<int,array{code:string,title:string,blockCode:string}>
     */
    private function uniqueReferences(array $references): array
    {
        $unique = [];

        foreach ($references as $reference) {
            $key = strtolower(trim($reference['code']));

            if ($key === '' || isset($unique[$key])) {
                continue;
            }

            $unique[$key] = $reference;
        }

        return array_values($unique);
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
