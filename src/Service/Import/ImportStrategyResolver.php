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
        [$startAt, $endAt] = $this->parseAcademicYear($academicYear);

        $framework = $this->entityManager->getRepository(Framework::class)->findOneBy([
            'code' => $frameworkCode,
            'startAt' => $startAt,
            'endAt' => $endAt,
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

    /**
     * @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable}
     */
    private function parseAcademicYear(string $academicYear): array
    {
        if (!preg_match('/^(?<start>\d{4})-(?<end>\d{4})$/', $academicYear, $matches)) {
            throw new \InvalidArgumentException(sprintf('Année universitaire invalide : "%s".', $academicYear));
        }

        return [
            new \DateTimeImmutable(sprintf('%d-09-01 00:00:00', (int)$matches['start'])),
            new \DateTimeImmutable(sprintf('%d-08-31 23:59:59', (int)$matches['end'])),
        ];
    }
}
