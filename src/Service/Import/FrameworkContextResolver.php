<?php

namespace App\Service\Import;

use App\Dto\Import\ImportContext;
use App\Entity\Framework;
use App\Entity\Promotion;
use Doctrine\ORM\EntityManagerInterface;

final readonly class FrameworkContextResolver
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    )
    {
    }

    /**
     * @param array<string, mixed> $structure
     */
    public function resolve(array $structure): ImportContext
    {
        /** @var array<string, mixed> $meta */
        $meta = $structure['meta'] ?? [];

        $academicYear = trim((string)($meta['academicYear'] ?? ''));
        $rncpCode = trim((string)($meta['rncpCode'] ?? ''));
        $programCode = trim((string)($meta['programCode'] ?? ''));

        $frameworkCode = $rncpCode !== '' ? 'RNCP' . $rncpCode : trim((string)($meta['datasetCode'] ?? ''));
        $promotionLabel = trim(sprintf('%s %s', mb_strtoupper($programCode), $academicYear));

        /** @var Framework|null $framework */
        $framework = $this->entityManager->getRepository(Framework::class)->findOneBy([
            'code' => $frameworkCode,
        ]);

        if (!$framework instanceof Framework) {
            throw new \RuntimeException(sprintf(
                'Le framework "%s" n’existe pas en base. Lance d’abord un import complet.',
                $frameworkCode
            ));
        }

        /** @var Promotion|null $promotion */
        $promotion = $this->entityManager->getRepository(Promotion::class)->findOneBy([
            'label' => $promotionLabel,
        ]);

        if (!$promotion instanceof Promotion) {
            throw new \RuntimeException(sprintf(
                'La promotion "%s" n’existe pas en base. Lance d’abord un import complet.',
                $promotionLabel
            ));
        }

        return new ImportContext($framework, $promotion);
    }
}
