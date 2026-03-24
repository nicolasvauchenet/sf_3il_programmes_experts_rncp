<?php

namespace App\Service\Context\Provider;

use App\Dto\Context\EvaluationSheet;
use App\Service\Context\EvaluationSheetResolver;
use App\Service\Context\FrameworkFolderScanner;
use App\Service\Context\Loader\EvaluationSheetLoader;
use App\Service\Context\Loader\FrameworkStructureLoader;

final readonly class FrameworkEvaluationsProvider
{
    public function __construct(
        private FrameworkFolderScanner   $scanner,
        private EvaluationSheetLoader    $loader,
        private FrameworkStructureLoader $structureLoader,
        private EvaluationSheetResolver  $resolver,
    )
    {
    }

    /**
     * @return EvaluationSheet[]
     */
    public function listEvaluations(string $promotion, string $year): array
    {
        $refs = $this->scanner->listJsonFiles($promotion, $year, 'evaluations');
        $structure = $this->structureLoader->load($promotion, $year);

        $evaluations = [];

        foreach ($refs as $ref) {
            try {
                $sheet = $this->loader->load($ref->fileCode, $ref->path);
                $evaluations[] = $this->resolver->resolve($sheet, $structure);
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
