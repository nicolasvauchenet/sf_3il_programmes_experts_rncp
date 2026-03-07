<?php

namespace App\Service\Chart;

use App\Dto\Context\ModuleSheet;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

final readonly class ModuleChartService
{
    private const COLOR_ORANGE = '#e84d0d';
    private const COLOR_BLUE = '#005067';

    public function __construct(
        private ChartBuilderInterface $chartBuilder,
    ) {
    }

    public function createModuleVolumeChart(ModuleSheet $module): Chart
    {
        $skillsCount = count($module->skills);
        $chaptersCount = count($module->outline['chapters'] ?? []);
        $exercisesCount = count($module->exercises);

        $chart = $this->chartBuilder->createChart(Chart::TYPE_BAR);

        $chart->setData([
            'labels' => ['Compétences', 'Chapitres', 'Exercices'],
            'datasets' => [
                [
                    'label' => 'Volumétrie du module',
                    'data' => [$skillsCount, $chaptersCount, $exercisesCount],
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
