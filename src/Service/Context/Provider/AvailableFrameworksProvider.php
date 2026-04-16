<?php

namespace App\Service\Context\Provider;

use App\Dto\Context\ContextReference;
use App\Entity\Promotion;
use App\Repository\PromotionRepository;

final readonly class AvailableFrameworksProvider
{
    public function __construct(
        private PromotionRepository $promotionRepository,
    )
    {
    }

    /**
     * @return ContextReference[]
     */
    public function listAvailable(): array
    {
        $promotions = $this->promotionRepository->findAll();
        $contexts = [];

        foreach ($promotions as $promotion) {
            if (!$promotion instanceof Promotion) {
                continue;
            }

            $program = $promotion->getProgram();
            $framework = $promotion->getFramework();
            $start = $promotion->getStartAt();
            $end = $promotion->getEndAt();

            if (
                $program === null
                || $framework === null
                || !$start instanceof \DateTimeImmutable
                || !$end instanceof \DateTimeImmutable
            ) {
                continue;
            }

            $academicYear = sprintf('%s-%s', $start->format('Y'), $end->format('Y'));

            $contexts[] = new ContextReference(
                promotionCode: $program->value,
                academicYear: $academicYear,
                datasetCode: $program->value . '_' . $academicYear,
                structurePath: '',
            );
        }

        usort(
            $contexts,
            static fn(ContextReference $a, ContextReference $b): int => [
                    $a->promotionCode,
                    $a->academicYear,
                ] <=> [
                    $b->promotionCode,
                    $b->academicYear,
                ]
        );

        return $contexts;
    }
}
