<?php

namespace App\Service\Import;

use App\Dto\Import\ImportContext;
use App\Dto\Import\ImportReport;
use App\Entity\Framework;
use App\Entity\Promotion;
use Doctrine\ORM\EntityManagerInterface;

final readonly class FrameworkContextImporter
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EnumResolver           $enumResolver,
        private int                    $defaultFrameworkLevel = 6,
    )
    {
    }

    /**
     * @param array<string, mixed> $structure
     */
    public function import(array $structure, ImportReport $report): ImportContext
    {
        /** @var array<string, mixed> $meta */
        $meta = $structure['meta'] ?? [];

        $academicYear = trim((string)($meta['academicYear'] ?? ''));
        [$startAt, $endAt] = $this->parseAcademicYear($academicYear);

        $rncpCode = trim((string)($meta['rncpCode'] ?? ''));
        $programCode = trim((string)($meta['programCode'] ?? ''));
        $programTitle = trim((string)($meta['programTitle'] ?? ''));

        $frameworkCode = $rncpCode !== '' ? 'RNCP' . $rncpCode : trim((string)($meta['datasetCode'] ?? ''));

        /** @var Framework|null $framework */
        $framework = $this->entityManager->getRepository(Framework::class)->findOneBy([
            'code' => $frameworkCode,
        ]);

        $isNewFramework = !$framework instanceof Framework;

        if ($isNewFramework) {
            $framework = new Framework();
            $framework->setCode($frameworkCode);
            $this->entityManager->persist($framework);
            $report->markCreated('frameworks');
        } else {
            $report->markUpdated('frameworks');
        }

        $framework->setTitle($programTitle !== '' ? $programTitle : $frameworkCode);
        $framework->setLevel($this->defaultFrameworkLevel);
        $framework->setStartAt($startAt);
        $framework->setEndAt($endAt);

        $promotionLabel = trim(sprintf('%s %s', mb_strtoupper($programCode), $academicYear));

        /** @var Promotion|null $promotion */
        $promotion = $this->entityManager->getRepository(Promotion::class)->findOneBy([
            'label' => $promotionLabel,
        ]);

        $isNewPromotion = !$promotion instanceof Promotion;

        if ($isNewPromotion) {
            $promotion = new Promotion();
            $this->entityManager->persist($promotion);
            $report->markCreated('promotions');
        } else {
            $report->markUpdated('promotions');
        }

        $promotion->setFramework($framework);
        $promotion->setProgram($this->enumResolver->resolveProgram($programCode));
        $promotion->setLabel($promotionLabel);
        $promotion->setStartAt($startAt);
        $promotion->setEndAt($endAt);

        return new ImportContext($framework, $promotion);
    }

    /**
     * @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable}
     * @throws \Exception
     */
    private function parseAcademicYear(string $academicYear): array
    {
        if (!preg_match('/^(?<start>\d{4})-(?<end>\d{4})$/', $academicYear, $matches)) {
            throw new \InvalidArgumentException(sprintf('Année universitaire invalide : "%s".', $academicYear));
        }

        $startYear = (int)$matches['start'];
        $endYear = (int)$matches['end'];

        return [
            new \DateTimeImmutable(sprintf('%d-09-01 00:00:00', $startYear)),
            new \DateTimeImmutable(sprintf('%d-08-31 23:59:59', $endYear)),
        ];
    }
}
