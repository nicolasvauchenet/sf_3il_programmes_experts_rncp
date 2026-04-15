<?php

namespace App\Dto\Import;

use App\Entity\Framework;
use App\Entity\Promotion;

final readonly class ImportContext
{
    public function __construct(
        public Framework $framework,
        public Promotion $promotion,
    )
    {
    }
}
