<?php

declare(strict_types=1);

namespace App\Service\Framework;

final readonly class FrameworkProvider
{
    public function __construct(
        private StructureReader $structureReader,
        private ModuleReader $moduleReader,
        private SkillReader $skillReader,
        private EvaluationReader $evaluationReader,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getStructure(FrameworkContext $context): array
    {
        return $this->structureReader->read($context);
    }

    /**
     * @return list<string>
     */
    public function getModuleCodes(FrameworkContext $context): array
    {
        $structure = $this->getStructure($context);

        return $this->extractCodes($structure['modules'] ?? null);
    }

    /**
     * @return list<string>
     */
    public function getSkillCodes(FrameworkContext $context): array
    {
        $structure = $this->getStructure($context);

        return $this->extractCodes($structure['skills'] ?? null);
    }

    /**
     * @return list<string>
     */
    public function getEvaluationCodes(FrameworkContext $context): array
    {
        $evaluations = $this->getEvaluations($context);

        $out = [];
        foreach ($evaluations as $e) {
            $code = $e['code'] ?? null;
            if (is_string($code) && $code !== '') {
                $out[] = strtolower($code);
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getModules(FrameworkContext $context): array
    {
        return $this->moduleReader->readMany($context, $this->getModuleCodes($context));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getSkills(FrameworkContext $context): array
    {
        return $this->skillReader->readMany($context, $this->getSkillCodes($context));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getEvaluations(FrameworkContext $context): array
    {
        $structure = $this->getStructure($context);

        $node = $structure['evaluations'] ?? null;

        if (!is_array($node) || !array_is_list($node)) {
            return [];
        }

        $out = [];

        foreach ($node as $item) {
            if (!is_array($item) || array_is_list($item)) {
                continue;
            }

            $code = $item['code'] ?? null;
            if (!is_string($code) || $code === '') {
                continue;
            }

            $item['code'] = strtolower($code);
            $out[] = $item;
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getSkillsForModule(FrameworkContext $context, string $moduleCode): array
    {
        $map = $this->getModuleSkillsMap($context);
        $moduleCode = strtolower(trim($moduleCode));

        $skillCodes = $map[$moduleCode] ?? [];

        return $this->skillReader->readMany($context, $skillCodes);
    }

    /**
     * @return array<string, list<string>>
     */
    public function getModuleSkillsMap(FrameworkContext $context): array
    {
        $structure = $this->getStructure($context);

        $direct = $structure['modulesSkills'] ?? $structure['moduleSkills'] ?? null;
        if (is_array($direct) && !array_is_list($direct)) {
            return $this->normalizeModuleSkillsMap($direct);
        }

        $modules = $structure['modules'] ?? null;
        if (is_array($modules) && array_is_list($modules)) {
            $out = [];

            foreach ($modules as $m) {
                if (!is_array($m) || array_is_list($m)) {
                    continue;
                }

                $code = $m['code'] ?? null;
                if (!is_string($code) || $code === '') {
                    continue;
                }

                $skills = $m['skills'] ?? null;
                $codes = $this->extractCodes($skills);

                if ($codes !== []) {
                    $out[strtolower($code)] = $codes;
                }
            }

            return $out;
        }

        return [];
    }

    /**
     * @param mixed $node
     * @return list<string>
     */
    private function extractCodes(mixed $node): array
    {
        if (!is_array($node)) {
            return [];
        }

        if (array_is_list($node)) {
            $out = [];

            foreach ($node as $item) {
                if (is_string($item) && $item !== '') {
                    $out[] = strtolower($item);
                    continue;
                }

                if (is_array($item) && !array_is_list($item)) {
                    $code = $item['code'] ?? null;
                    if (is_string($code) && $code !== '') {
                        $out[] = strtolower($code);
                    }
                }
            }

            return array_values(array_unique($out));
        }

        return [];
    }

    /**
     * @param array<string, mixed> $map
     * @return array<string, list<string>>
     */
    private function normalizeModuleSkillsMap(array $map): array
    {
        $out = [];

        foreach ($map as $moduleCode => $skills) {
            if (!is_string($moduleCode) || $moduleCode === '') {
                continue;
            }

            $skillCodes = $this->extractCodes($skills);
            if ($skillCodes === []) {
                continue;
            }

            $out[strtolower($moduleCode)] = $skillCodes;
        }

        return $out;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findEvaluation(FrameworkContext $context, string $code): ?array
    {
        $code = strtolower(trim($code));
        if ($code === '') {
            return null;
        }

        foreach ($this->getEvaluations($context) as $e) {
            $c = $e['code'] ?? null;
            if (is_string($c) && strtolower($c) === $code) {
                return $e;
            }
        }

        return null;
    }
}
