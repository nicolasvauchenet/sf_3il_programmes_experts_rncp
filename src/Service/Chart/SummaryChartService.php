<?php

namespace App\Service\Chart;

use App\Dto\Context\FrameworkStructure;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

final readonly class SummaryChartService
{
    private const COLOR_ORANGE = '#e84d0d';
    private const COLOR_BLUE = '#005067';

    public function __construct(
        private ChartBuilderInterface $chartBuilder,
    ) {
    }

    public function createReferentialVolumeChart(FrameworkStructure $structure): Chart
    {
        return $this->createDoughnutChart(
            title: 'Volumétrie du référentiel',
            labels: ['Blocs', 'Matières', 'Compétences', 'Évaluations'],
            data: [
                count($structure->blocks),
                count($structure->modules),
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

        foreach ($blocks as $block) {
            $blockCode = $this->getBlockCode($block);
            $labels[] = $this->getBlockLabel($block);

            $skillsCount = 0;

            if (is_array($block['skills'] ?? null)) {
                $skillsCount = count($block['skills']);
            } else {
                foreach ($skills as $skill) {
                    $skillBlockCode = $this->normalizeCode(
                        $skill['blockCode']
                        ?? $skill['block']
                        ?? null
                    );

                    if ($skillBlockCode === $blockCode) {
                        ++$skillsCount;
                    }
                }
            }

            $data[] = $skillsCount;
        }

        return $this->createBarChart(
            title: 'Compétences par bloc',
            labels: $labels,
            data: $data,
        );
    }

    public function createEvaluationsPerBlockChart(FrameworkStructure $structure): Chart
    {
        $blocks = $structure->blocks;
        $evaluations = $structure->evaluations;

        $labels = [];
        $data = [];

        foreach ($blocks as $block) {
            $blockCode = $this->getBlockCode($block);
            $labels[] = $this->getBlockLabel($block);

            $count = 0;

            foreach ($evaluations as $evaluation) {
                $evaluationBlockCode = $this->normalizeCode(
                    $evaluation['blockCode']
                    ?? $evaluation['block']
                    ?? null
                );

                if ($evaluationBlockCode === $blockCode) {
                    ++$count;
                }
            }

            $data[] = $count;
        }

        return $this->createBarChart(
            title: 'Évaluations par bloc',
            labels: $labels,
            data: $data,
        );
    }

    public function createModulesPerBlockChart(FrameworkStructure $structure): Chart
    {
        $blocks = $structure->blocks;
        $modules = $structure->modules;

        $labels = [];
        $data = [];

        foreach ($blocks as $block) {
            $blockCode = $this->getBlockCode($block);
            $labels[] = $this->getBlockLabel($block);

            $modulesCount = 0;

            if (is_array($block['modules'] ?? null)) {
                $modulesCount = count($block['modules']);
            } else {
                foreach ($modules as $module) {
                    $moduleBlockCode = $this->normalizeCode(
                        $module['blockCode']
                        ?? $module['block']
                        ?? null
                    );

                    if ($moduleBlockCode === $blockCode) {
                        ++$modulesCount;
                    }
                }
            }

            $data[] = $modulesCount;
        }

        return $this->createBarChart(
            title: 'Modules par bloc',
            labels: $labels,
            data: $data,
        );
    }

    public function createSkillsPerEvaluationChart(FrameworkStructure $structure): Chart
    {
        $evaluations = $structure->evaluations;

        $labels = [];
        $data = [];

        foreach ($evaluations as $evaluation) {
            $labels[] = $this->getEvaluationLabel($evaluation);

            $skillsCount = 0;

            if (is_array($evaluation['skills'] ?? null)) {
                $skillsCount = count($evaluation['skills']);
            }

            $data[] = $skillsCount;
        }

        return $this->createBarChart(
            title: 'Compétences par évaluation',
            labels: $labels,
            data: $data,
        );
    }

    public function createModulesPerEvaluationChart(FrameworkStructure $structure): Chart
    {
        $evaluations = $structure->evaluations;

        $labels = [];
        $data = [];

        foreach ($evaluations as $evaluation) {
            $labels[] = $this->getEvaluationLabel($evaluation);

            $modulesCount = 0;

            if (is_array($evaluation['modules'] ?? null)) {
                $modulesCount = count($evaluation['modules']);
            }

            $data[] = $modulesCount;
        }

        return $this->createBarChart(
            title: 'Modules par évaluation',
            labels: $labels,
            data: $data,
        );
    }

    private function createDoughnutChart(string $title, array $labels, array $data): Chart
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_DOUGHNUT);

        $colors = [
            '#005067', // bleu
            '#0f766e', // vert canard / teal profond
            '#e84d0d', // orange
            '#b45309', // ambre foncé / ocre chaud
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

    private function createBarChart(string $title, array $labels, array $data): Chart
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_BAR);

        $colors = $this->buildAlternatingColors(count($data));

        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => $title,
                    'data' => $data,
                    'backgroundColor' => $colors['background'],
                    'borderColor' => $colors['border'],
                    'borderWidth' => 1,
                    'borderRadius' => 2,
                    'borderSkipped' => false,
                ],
            ],
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
        $code = $this->normalizeCode($evaluation['code'] ?? '');
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
