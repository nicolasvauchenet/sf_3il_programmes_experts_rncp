<?php

namespace App\Service\Chart;

use Symfony\UX\Chartjs\Model\Chart;

final readonly class ChartViewModel
{
    public function __construct(
        public Chart $chart,
        public int $heightPx,
    ) {
    }
}
