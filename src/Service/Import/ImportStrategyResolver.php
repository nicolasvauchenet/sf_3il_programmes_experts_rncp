<?php

namespace App\Service\Import;

use App\Enum\ImportStrategy;
use App\Entity\Framework;
use App\Entity\Promotion;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ImportStrategyResolver
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    )
    {
    }

    /**
     * @param array<string, mixed> $structure
     * @param bool $hasSkills
     * @param bool $hasEvaluations
     * @return ImportStrategy
     */
    public function resolve(array $structure, bool $hasSkills, bool $hasEvaluations): ImportStrategy
    {
        $meta = $structure['meta'] ?? [];

        $rncpCode = (string)($meta['rncpCode'] ?? '');
        $programCode = (string)($meta['programCode'] ?? '');
        $academicYear = (string)($meta['academicYear'] ?? '');

        $frameworkCode = 'RNCP' . $rncpCode;
        $promotionLabel = strtoupper($programCode) . ' ' . $academicYear;

        $framework = $this->entityManager->getRepository(Framework::class)->findOneBy([
            'code' => $frameworkCode,
        ]);

        $promotion = $this->entityManager->getRepository(Promotion::class)->findOneBy([
            'label' => $promotionLabel,
        ]);

        $isFullDataset = $hasSkills && $hasEvaluations;

        // --- CAS 1 : framework absent ---
        if (!$framework) {
            if (!$isFullDataset) {
                throw new \RuntimeException('Import partiel impossible : framework inexistant.');
            }

            return ImportStrategy::CREATE_FULL;
        }

        // --- CAS 2 : framework présent ---
        if (!$promotion) {
            return ImportStrategy::CREATE_PROMOTION;
        }

        // --- CAS 3 : framework + promo présents ---
        if ($isFullDataset) {
            return ImportStrategy::UPDATE_FULL;
        }

        return ImportStrategy::UPDATE_PROMOTION;
    }
}
