<?php

namespace App\Service\Chart;

use App\Service\Reporting\ModulesBySemesterDataBuilder;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

final class ModulesBySemesterChartBuilder
{
    private const COLOR_PRIMARY = '#00556A';
    private const COLOR_ACCENT = '#F36C30';
    private const COLOR_SECONDARY = '#00A78E';

    private const MIN_HEIGHT_PX = 260;
    private const MAX_HEIGHT_PX = 420;
    private const BAR_THICKNESS_PX = 28;

    public function __construct(
        private readonly ChartBuilderInterface $chartBuilder,
        private readonly ModulesBySemesterDataBuilder $dataBuilder,
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
                    'label' => 'Modules',
                    'data' => $series['values'],
                    'backgroundColor' => $backgroundColors,
                    'hoverBackgroundColor' => self::COLOR_ACCENT,
                    'barThickness' => self::BAR_THICKNESS_PX,
                ],
            ],
        ]);

        $chart->setOptions([
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
                    'ticks' => [
                        'color' => self::COLOR_PRIMARY,
                    ],
                    'grid' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                        'color' => self::COLOR_PRIMARY,
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Nombre de modules',
                        'color' => self::COLOR_PRIMARY,
                    ],
                    'grid' => [
                        'color' => 'rgba(0, 85, 106, 0.10)',
                    ],
                ],
            ],
        ]);

        $labelsCount = count($series['labels']);
        $computed = 220 + ($labelsCount * 12);
        $heightPx = max(self::MIN_HEIGHT_PX, min(self::MAX_HEIGHT_PX, $computed));

        return new ChartViewModel($chart, $heightPx);
    }
}
