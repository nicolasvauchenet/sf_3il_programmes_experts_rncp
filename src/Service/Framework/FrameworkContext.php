<?php

declare(strict_types=1);

namespace App\Service\Framework;

final readonly class FrameworkContext
{
    public function __construct(
        public string $promotion,
        public string $year,
    ) {
    }

    public function datasetKey(): string
    {
        return sprintf('%s_%s', $this->promotion, $this->year);
    }
}
