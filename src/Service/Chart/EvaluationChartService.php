<?php

namespace App\Service\Chart;

use App\Dto\Context\EvaluationSheet;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

final readonly class EvaluationChartService
{
    private const COLOR_ORANGE = '#e84d0d';
    private const COLOR_BLUE = '#005067';

    public function __construct(
        private ChartBuilderInterface $chartBuilder,
    ) {
    }

    public function createEvaluationVolumeChart(EvaluationSheet $evaluation): Chart
    {
        $examPartsCount = count($evaluation->examParts());
        $skillsCount = count($evaluation->skills);
        $criteriaCount = count($evaluation->criteria);

        $chart = $this->chartBuilder->createChart(Chart::TYPE_BAR);

        $chart->setData([
            'labels' => ['Épreuves', 'Compétences', 'Critères'],
            'datasets' => [
                [
                    'label' => 'Volumétrie de l’évaluation',
                    'data' => [$examPartsCount, $skillsCount, $criteriaCount],
                    'backgroundColor' => [
                        $this->hexToRgba(self::COLOR_BLUE, 1),
                        $this->hexToRgba(self::COLOR_ORANGE, 1),
                        $this->hexToRgba(self::COLOR_BLUE, 1),
                    ],
                    'borderColor' => [
                        self::COLOR_BLUE,
                        self::COLOR_ORANGE,
                        self::COLOR_BLUE,
                    ],
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
                        'autoSkip' => false,
                        'maxRotation' => 0,
                        'minRotation' => 0,
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                        'stepSize' => 1,
                    ],
                ],
            ],
        ]);

        return $chart;
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
}
