<?php

namespace App\Dto\Context;

final readonly class ModuleReference
{
    public function __construct(
        public string $datasetCode,
        public string $folderName,
        public string $fileCode,
        public string $path,
    ) {
    }
}
