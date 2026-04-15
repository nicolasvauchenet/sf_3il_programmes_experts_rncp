<?php

namespace App\Service\Chart;

use App\Dto\Context\FrameworkStructure;
use App\Dto\Context\ProjectSheet;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

final readonly class SummaryChartService
{
    private const COLOR_ORANGE = '#e84d0d';
    private const COLOR_BLUE = '#005067';

    public function __construct(
        private ChartBuilderInterface $chartBuilder,
    )
    {
    }

    public function createReferentialVolumeChart(FrameworkStructure $structure, int $projectsCount = 0): Chart
    {
        return $this->createDoughnutChart(
            title: 'Volumétrie du référentiel',
            labels: ['Blocs', 'Matières', 'Projets', 'Compétences', 'Évaluations'],
            data: [
                count($structure->blocks),
                count($structure->modules),
                $projectsCount,
                count($structure->skills),
                count($structure->evaluations),
            ],
        );
    }

    public function createSkillsPerBlockChart(FrameworkStructure $structure): Chart
    {
        $blocks = $structure->blocks;
        $skills = $structure->skills;

        $labels = [];
        $data = [];
        $blockCodes = [];
        $fileCodes = [];

        foreach ($blocks as $block) {
            $blockCode = $this->getBlockCode($block);
            $blockSkills = $this->getSkillsForBlock($skills, $blockCode);
            $firstSkill = $blockSkills[0] ?? null;

            $labels[] = $this->getBlockLabel($block);

            $skillsCount = 0;

            if (is_array($block['skills'] ?? null)) {
                $skillsCount = count($block['skills']);
            } else {
                $skillsCount = count($blockSkills);
            }

            $data[] = $skillsCount;
            $blockCodes[] = $blockCode;
            $fileCodes[] = $firstSkill !== null
                ? $this->getSkillFileCode($firstSkill, $blockCode)
                : '';
        }

        return $this->createBarChart(
            title: 'Compétences par bloc',
            labels: $labels,
            data: $data,
            datasetExtra: [
                'blockCodes' => $blockCodes,
                'fileCodes' => $fileCodes,
            ],
        );
    }

    public function createEvaluationsPerBlockChart(FrameworkStructure $structure): Chart
    {
        $blocks = $structure->blocks;
        $evaluations = $structure->evaluations;

        $labels = [];
        $data = [];
        $blockCodes = [];
        $fileCodes = [];

        foreach ($blocks as $block) {
            $blockCode = $this->getBlockCode($block);
            $blockEvaluations = $this->getEvaluationsForBlock($evaluations, $blockCode);
            $firstEvaluation = $blockEvaluations[0] ?? null;

            $labels[] = $this->getBlockLabel($block);
            $data[] = count($blockEvaluations);
            $blockCodes[] = $blockCode;
            $fileCodes[] = $firstEvaluation !== null
                ? $this->getEvaluationFileCode($firstEvaluation, $blockCode)
                : '';
        }

        return $this->createBarChart(
            title: 'Évaluations par bloc',
            labels: $labels,
            data: $data,
            datasetExtra: [
                'blockCodes' => $blockCodes,
                'fileCodes' => $fileCodes,
            ],
        );
    }

    public function createModulesPerBlockChart(FrameworkStructure $structure): Chart
    {
        $blocks = $structure->blocks;
        $modules = $structure->modules;

        $labels = [];
        $data = [];
        $blockCodes = [];
        $fileCodes = [];

        foreach ($blocks as $block) {
            $blockCode = $this->getBlockCode($block);
            $blockModules = $this->getModulesForBlock($modules, $blockCode);
            $firstModule = $blockModules[0] ?? null;

            $labels[] = $this->getBlockLabel($block);
            $data[] = count($blockModules);
            $blockCodes[] = $blockCode;
            $fileCodes[] = $firstModule !== null
                ? $this->getModuleFileCode($firstModule, $blockCode)
                : '';
        }

        return $this->createBarChart(
            title: 'Modules par bloc',
            labels: $labels,
            data: $data,
            datasetExtra: [
                'blockCodes' => $blockCodes,
                'fileCodes' => $fileCodes,
            ],
        );
    }

    /**
     * @param ProjectSheet[] $projects
     */
    public function createProjectsPerBlockChart(array $projects): Chart
    {
        $countsByBlock = [];
        $firstFileCodeByBlock = [];

        foreach ($projects as $project) {
            $blockCode = $this->normalizeCode($project->blocCode());

            if ($blockCode === '') {
                $blockCode = 'SANS_BLOC';
            }

            if (!array_key_exists($blockCode, $countsByBlock)) {
                $countsByBlock[$blockCode] = 0;
                $firstFileCodeByBlock[$blockCode] = strtolower($project->fileCode);
            }

            ++$countsByBlock[$blockCode];
        }

        ksort($countsByBlock);

        $labels = array_keys($countsByBlock);
        $data = array_values($countsByBlock);
        $blockCodes = array_keys($countsByBlock);
        $fileCodes = [];

        foreach ($blockCodes as $blockCode) {
            $fileCodes[] = $firstFileCodeByBlock[$blockCode] ?? '';
        }

        return $this->createBarChart(
            title: 'Projets par bloc',
            labels: $labels,
            data: $data,
            datasetExtra: [
                'blockCodes' => $blockCodes,
                'fileCodes' => $fileCodes,
            ],
        );
    }

    public function createModulesPerEvaluationChart(FrameworkStructure $structure): Chart
    {
        $evaluations = $structure->evaluations;

        $labels = [];
        $data = [];
        $fileCodes = [];

        foreach ($evaluations as $evaluation) {
            $labels[] = $this->getEvaluationLabel($evaluation);

            $modulesCount = 0;

            if (is_array($evaluation['modules'] ?? null)) {
                $modulesCount = count($evaluation['modules']);
            }

            $data[] = $modulesCount;

            $fileCodes[] = $this->getEvaluationFileCode($evaluation);
        }

        return $this->createBarChart(
            title: 'Modules par évaluation',
            labels: $labels,
            data: $data,
            datasetExtra: [
                'fileCodes' => $fileCodes,
            ],
        );
    }

    public function createSkillsPerEvaluationChart(FrameworkStructure $structure): Chart
    {
        $evaluations = $structure->evaluations;

        $labels = [];
        $data = [];
        $fileCodes = [];

        foreach ($evaluations as $evaluation) {
            $labels[] = $this->getEvaluationLabel($evaluation);

            $skillsCount = 0;

            if (is_array($evaluation['skills'] ?? null)) {
                $skillsCount = count($evaluation['skills']);
            }

            $data[] = $skillsCount;

            $fileCodes[] = $this->getEvaluationFileCode($evaluation);
        }

        return $this->createBarChart(
            title: 'Compétences par évaluation',
            labels: $labels,
            data: $data,
            datasetExtra: [
                'fileCodes' => $fileCodes,
            ],
        );
    }

    private function createDoughnutChart(string $title, array $labels, array $data): Chart
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_DOUGHNUT);

        $colors = [
            '#005067',
            '#0f766e',
            '#e84d0d',
            '#b45309',
            '#7c3aed',
        ];

        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => $title,
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                    'borderWidth' => 1,
                    'hoverOffset' => 8,
                ],
            ],
        ]);

        $chart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'cutout' => '60%',
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => [
                        'boxWidth' => 12,
                        'boxHeight' => 12,
                        'padding' => 14,
                    ],
                ],
                'tooltip' => [
                    'enabled' => true,
                ],
            ],
        ]);

        return $chart;
    }

    private function createBarChart(string $title, array $labels, array $data, array $datasetExtra = []): Chart
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_BAR);

        $colors = $this->buildAlternatingColors(count($data));

        $dataset = array_merge([
            'label' => $title,
            'data' => $data,
            'backgroundColor' => $colors['background'],
            'borderColor' => $colors['border'],
            'borderWidth' => 1,
            'borderRadius' => 2,
            'borderSkipped' => false,
        ], $datasetExtra);

        $chart->setData([
            'labels' => $labels,
            'datasets' => [$dataset],
        ]);

        $chart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'enabled' => true,
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'autoSkip' => true,
                        'maxRotation' => 0,
                        'minRotation' => 0,
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ]);

        return $chart;
    }

    private function getModulesForBlock(array $modules, string $blockCode): array
    {
        $blockModules = [];

        foreach ($modules as $module) {
            $moduleBlockCode = $this->normalizeCode(
                $module['blockCode']
                ?? $module['block']
                ?? null
            );

            if ($moduleBlockCode === $blockCode) {
                $blockModules[] = $module;
            }
        }

        return $blockModules;
    }

    private function getSkillsForBlock(array $skills, string $blockCode): array
    {
        $blockSkills = [];

        foreach ($skills as $skill) {
            $skillBlockCode = $this->normalizeCode(
                $skill['blockCode']
                ?? $skill['block']
                ?? null
            );

            if ($skillBlockCode === $blockCode) {
                $blockSkills[] = $skill;
            }
        }

        return $blockSkills;
    }

    private function getEvaluationsForBlock(array $evaluations, string $blockCode): array
    {
        $blockEvaluations = [];

        foreach ($evaluations as $evaluation) {
            $evaluationBlockCode = $this->normalizeCode(
                $evaluation['blockCode']
                ?? $evaluation['blocCode']
                ?? $evaluation['block']
                ?? null
            );

            if ($evaluationBlockCode === $blockCode) {
                $blockEvaluations[] = $evaluation;
            }
        }

        return $blockEvaluations;
    }

    private function getModuleFileCode(array $module, string $fallbackBlockCode = ''): string
    {
        $explicitFileCode = trim((string)($module['fileCode'] ?? ''));

        if ($explicitFileCode !== '') {
            return strtolower($explicitFileCode);
        }

        $blockCode = $this->normalizeCode(
            $module['blockCode']
            ?? $module['block']
            ?? $fallbackBlockCode
        );

        $moduleCode = $this->normalizeCode(
            $module['code']
            ?? ''
        );

        if ($blockCode !== '' && $moduleCode !== '') {
            return strtolower($blockCode . $moduleCode);
        }

        $fullCode = trim((string)($module['fullCode'] ?? ''));

        if ($fullCode !== '' && preg_match('/-(BC\d+)-(FM\d+)$/i', $fullCode, $matches) === 1) {
            return strtolower($matches[1] . $matches[2]);
        }

        return '';
    }

    private function getSkillFileCode(array $skill, string $fallbackBlockCode = ''): string
    {
        $explicitFileCode = trim((string)($skill['fileCode'] ?? ''));

        if ($explicitFileCode !== '') {
            return strtolower($explicitFileCode);
        }

        $blockCode = $this->normalizeCode(
            $skill['blockCode']
            ?? $skill['block']
            ?? $fallbackBlockCode
        );

        $skillCode = $this->normalizeCode(
            $skill['code']
            ?? ''
        );

        if ($blockCode !== '' && $skillCode !== '') {
            return strtolower($blockCode . $skillCode);
        }

        $fullCode = trim((string)($skill['fullCode'] ?? ''));

        if ($fullCode !== '' && preg_match('/-(BC\d+)-([A-Z]+\d+)$/i', $fullCode, $matches) === 1) {
            return strtolower($matches[1] . $matches[2]);
        }

        return '';
    }

    private function getEvaluationFileCode(array $evaluation, string $fallbackBlockCode = ''): string
    {
        $explicitFileCode = trim((string)($evaluation['fileCode'] ?? ''));

        if ($explicitFileCode !== '') {
            return strtolower($explicitFileCode);
        }

        $blockCode = $this->normalizeCode(
            $evaluation['blockCode']
            ?? $evaluation['blocCode']
            ?? $evaluation['block']
            ?? $fallbackBlockCode
        );

        $evaluationCode = $this->normalizeCode(
            $evaluation['code']
            ?? ''
        );

        if ($blockCode !== '' && $evaluationCode !== '') {
            return strtolower($blockCode . $evaluationCode);
        }

        $fullCode = trim((string)($evaluation['fullCode'] ?? ''));

        if ($fullCode !== '' && preg_match('/-(BC\d+)-(EC\d+)$/i', $fullCode, $matches) === 1) {
            return strtolower($matches[1] . $matches[2]);
        }

        return '';
    }

    private function buildAlternatingColors(int $count): array
    {
        $background = [];
        $border = [];

        for ($i = 0; $i < $count; ++$i) {
            $isEven = $i % 2 === 0;

            $background[] = $isEven
                ? $this->hexToRgba(self::COLOR_ORANGE, 1)
                : $this->hexToRgba(self::COLOR_BLUE, 1);

            $border[] = $isEven
                ? self::COLOR_ORANGE
                : self::COLOR_BLUE;
        }

        return [
            'background' => $background,
            'border' => $border,
        ];
    }

    private function hexToRgba(string $hex, float $alpha): string
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) !== 6) {
            return sprintf('rgba(0, 0, 0, %.2f)', $alpha);
        }

        $red = hexdec(substr($hex, 0, 2));
        $green = hexdec(substr($hex, 2, 2));
        $blue = hexdec(substr($hex, 4, 2));

        return sprintf('rgba(%d, %d, %d, %.2f)', $red, $green, $blue, $alpha);
    }

    private function getBlockCode(array $block): string
    {
        return $this->normalizeCode($block['code'] ?? '');
    }

    private function getBlockLabel(array $block): string
    {
        $code = $this->normalizeCode($block['code'] ?? '');
        $name = trim((string)($block['name'] ?? $block['title'] ?? ''));

        if ($code !== '' && $name !== '') {
            return $code;
        }

        if ($code !== '') {
            return $code;
        }

        return $name !== '' ? $name : 'Bloc';
    }

    private function getEvaluationLabel(array $evaluation): string
    {
        $code = $this->normalizeCode($evaluation['shortCode'] ?? '');
        $name = trim((string)($evaluation['name'] ?? $evaluation['title'] ?? ''));

        if ($code !== '') {
            return $code;
        }

        return $name !== '' ? $name : 'Évaluation';
    }

    private function normalizeCode(null|string|int $value): string
    {
        return strtoupper(trim((string)$value));
    }
}
