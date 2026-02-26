<?php

namespace App\Dto\Context;

final readonly class ContextReference
{
    public function __construct(
        public string $promotionCode,
        public string $academicYear,
        public string $datasetCode,
        public string $structurePath,
    )
    {
    }
}
