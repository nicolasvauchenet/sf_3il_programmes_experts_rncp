<?php

namespace App\Service\Chart;

use App\Entity\User;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

final readonly class AdminUserChartService
{
    public function __construct(
        private ChartBuilderInterface $chartBuilder,
    )
    {
    }

    /**
     * @param list<User> $users
     */
    public function createUsersDistributionChart(array $users): Chart
    {
        $data = [
            'teachers' => 0,
            'students' => 0,
            'inactiveOrDisabled' => 0,
            'others' => 0,
        ];

        foreach ($users as $user) {
            $roles = $user->getRoles();

            if (in_array('ROLE_TEACHER', $roles, true)) {
                ++$data['teachers'];
            } elseif (in_array('ROLE_STUDENT', $roles, true)) {
                ++$data['students'];
            } else {
                ++$data['others'];
            }

            if (!$user->isActive() || null === $user->getLoggedAt()) {
                ++$data['inactiveOrDisabled'];
            }
        }

        return $this->createDoughnutChart(
            title: 'Répartition des utilisateurs',
            labels: ['Enseignants', 'Apprenants', 'Inactifs / désactivés', 'Autres'],
            data: array_values($data),
        );
    }

    /**
     * @param list<string> $labels
     * @param list<int> $data
     */
    private function createDoughnutChart(string $title, array $labels, array $data): Chart
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_DOUGHNUT);

        $colors = [
            '#005067',
            '#0f766e',
            '#e84d0d',
            '#b45309',
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
}
