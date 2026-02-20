<?php

namespace App\Service\Chart;

use App\Service\Reporting\EvaluationsVsSkillsDataBuilder;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

final class EvaluationsVsSkillsChartBuilder
{
    private const COLOR_PRIMARY = '#00556A';
    private const COLOR_ACCENT = '#F36C30';
    private const COLOR_SECONDARY = '#00A78E';

    private const MIN_HEIGHT_PX = 200;
    private const MAX_HEIGHT_PX = 720;
    private const BAR_THICKNESS_PX = 22;
    private const BAR_GAP_PX = 5;

    public function __construct(
        private readonly ChartBuilderInterface $chartBuilder,
        private readonly EvaluationsVsSkillsDataBuilder $dataBuilder,
    ) {
    }

    public function build(array $json): ChartViewModel
    {
        $series = $this->dataBuilder->buildChartSeries($json);

        $baseColors = [
            self::COLOR_PRIMARY,
            self::COLOR_SECONDARY,
        ];

        $backgroundColors = [];
        foreach ($series['values'] as $index => $value) {
            $backgroundColors[] = $baseColors[$index % count($baseColors)];
        }

        $chart = $this->chartBuilder->createChart(Chart::TYPE_BAR);

        $chart->setData([
            'labels' => $series['labels'],
            'datasets' => [
                [
                    'label' => 'Compétences mobilisées',
                    'data' => $series['values'],
                    'backgroundColor' => $backgroundColors,
                    'hoverBackgroundColor' => self::COLOR_ACCENT,
                    'barThickness' => self::BAR_THICKNESS_PX,
                    'skillsByEvaluation' => $series['skillsByEvaluation'],
                ],
            ],
        ]);

        $chart->setOptions([
            'indexAxis' => 'y',
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => [
                    'enabled' => true,
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                        'color' => self::COLOR_PRIMARY,
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Nombre de compétences',
                        'color' => self::COLOR_PRIMARY,
                    ],
                    'grid' => [
                        'color' => 'rgba(0, 85, 106, 0.10)',
                    ],
                ],
                'y' => [
                    'ticks' => [
                        'autoSkip' => false,
                        'color' => self::COLOR_PRIMARY,
                    ],
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
        ]);

        $barsCount = count($series['labels']);
        $computed = ($barsCount * (self::BAR_THICKNESS_PX + self::BAR_GAP_PX)) + 120;
        $heightPx = max(self::MIN_HEIGHT_PX, min(self::MAX_HEIGHT_PX, $computed));

        return new ChartViewModel($chart, $heightPx);
    }
}
