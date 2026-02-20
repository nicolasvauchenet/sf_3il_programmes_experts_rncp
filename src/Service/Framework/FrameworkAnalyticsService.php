<?php

declare(strict_types=1);

namespace App\Service\Framework;

final readonly class FrameworkAnalyticsService
{
    public function __construct(
        private FrameworkProvider $provider,
    ) {
    }

    /**
     * @return array{
     *   modulesTotal:int,
     *   skillsTotal:int,
     *   modulesWithSkills:int,
     *   modulesWithoutSkills:int,
     *   linkedSkillsUnique:int,
     *   unlinkedSkills:int
     * }
     */
    public function getSummary(FrameworkContext $context): array
    {
        $moduleCodes = $this->provider->getModuleCodes($context);
        $skillCodes = $this->provider->getSkillCodes($context);
        $map = $this->provider->getModuleSkillsMap($context);

        $modulesWithSkills = 0;
        $linkedSkillSet = [];

        foreach ($moduleCodes as $mCode) {
            $skills = $map[$mCode] ?? [];
            if ($skills !== []) {
                $modulesWithSkills++;
                foreach ($skills as $sCode) {
                    $linkedSkillSet[$sCode] = true;
                }
            }
        }

        $linkedSkillsUnique = count($linkedSkillSet);

        $unlinked = 0;
        foreach ($skillCodes as $sCode) {
            if (!isset($linkedSkillSet[$sCode])) {
                $unlinked++;
            }
        }

        return [
            'modulesTotal' => count($moduleCodes),
            'skillsTotal' => count($skillCodes),
            'modulesWithSkills' => $modulesWithSkills,
            'modulesWithoutSkills' => max(0, count($moduleCodes) - $modulesWithSkills),
            'linkedSkillsUnique' => $linkedSkillsUnique,
            'unlinkedSkills' => $unlinked,
        ];
    }

    /**
     * @return array{
     *   labels:list<string>,
     *   values:list<int>,
     *   moduleCodes:list<string>
     * }
     */
    public function getSkillsPerModuleDataset(FrameworkContext $context): array
    {
        $modules = $this->provider->getModules($context);
        $map = $this->provider->getModuleSkillsMap($context);

        $rows = [];

        foreach ($modules as $module) {
            $code = $this->stringOrNull($module['code'] ?? $module['id'] ?? null);
            if ($code === null) {
                continue;
            }

            $code = strtolower($code);
            $label = $this->stringOrNull($module['name'] ?? $module['title'] ?? null) ?? $code;

            $count = count($map[$code] ?? []);

            $rows[] = [
                'code' => $code,
                'label' => $label,
                'count' => $count,
            ];
        }

        usort($rows, static function (array $a, array $b): int {
            if ($a['count'] === $b['count']) {
                return $a['code'] <=> $b['code'];
            }

            return $b['count'] <=> $a['count'];
        });

        $labels = [];
        $values = [];
        $codes = [];

        foreach ($rows as $r) {
            $labels[] = (string)$r['label'];
            $values[] = (int)$r['count'];
            $codes[] = (string)$r['code'];
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'moduleCodes' => $codes,
        ];
    }

    /**
     * @return array{
     *   unlinkedSkillCodes:list<string>,
     *   unlinkedSkillsTotal:int
     * }
     */
    public function getUnlinkedSkillsDataset(FrameworkContext $context): array
    {
        $skillCodes = $this->provider->getSkillCodes($context);
        $map = $this->provider->getModuleSkillsMap($context);

        $linked = [];
        foreach ($map as $moduleCode => $skills) {
            foreach ($skills as $sCode) {
                $linked[$sCode] = true;
            }
        }

        $unlinked = [];
        foreach ($skillCodes as $sCode) {
            if (!isset($linked[$sCode])) {
                $unlinked[] = $sCode;
            }
        }

        sort($unlinked);

        return [
            'unlinkedSkillCodes' => $unlinked,
            'unlinkedSkillsTotal' => count($unlinked),
        ];
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $v = trim($value);

        return $v === '' ? null : $v;
    }
}
