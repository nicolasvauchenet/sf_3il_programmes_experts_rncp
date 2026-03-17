<?php

namespace App\Service\Context\Provider;

use App\Dto\Context\EvaluationSheet;
use App\Service\Context\FrameworkFolderScanner;
use App\Service\Context\Loader\EvaluationSheetLoader;

final readonly class FrameworkEvaluationsProvider
{
    public function __construct(
        private FrameworkFolderScanner $scanner,
        private EvaluationSheetLoader $loader,
    ) {
    }

    /**
     * @return EvaluationSheet[]
     */
    public function listEvaluations(string $promotion, string $year): array
    {
        $refs = $this->scanner->listJsonFiles($promotion, $year, 'evaluations');

        $evaluations = [];

        foreach ($refs as $ref) {
            try {
                $evaluations[] = $this->loader->load($ref->fileCode, $ref->path);
            } catch (\Throwable) {
                continue;
            }
        }

        usort(
            $evaluations,
            static fn(EvaluationSheet $a, EvaluationSheet $b): int => $a->fileCode <=> $b->fileCode
        );

        return $evaluations;
    }
}
