<?php

namespace App\Service\Admin;

use App\Dto\Import\ImportReport;
use App\Entity\Framework;
use App\Entity\Promotion;
use App\Service\Import\FrameworkImportService;
use App\Service\Import\ImportStrategyResolver;
use App\Service\Import\JsonDatasetLoader;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PromotionJsonDatabaseManager
{
    public function __construct(
        #[Autowire('%app.data_dir%')]
        private string $dataDir,
        private JsonDatasetLoader $loader,
        private ImportStrategyResolver $resolver,
        private FrameworkImportService $importService,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array{exists: bool, label: string|null, frameworkCode: string|null, academicYear: string|null}
     */
    public function getDatabaseState(string $datasetName): array
    {
        try {
            $reference = $this->resolveReference($datasetName);
            $promotion = $this->findPromotion($reference);

            return [
                'exists' => $promotion instanceof Promotion,
                'label' => $promotion?->getLabel(),
                'frameworkCode' => $reference['frameworkCode'],
                'academicYear' => $reference['academicYear'],
            ];
        } catch (\Throwable) {
            return [
                'exists' => false,
                'label' => null,
                'frameworkCode' => null,
                'academicYear' => null,
            ];
        }
    }

    public function exists(string $datasetName): bool
    {
        $reference = $this->resolveReference($datasetName);

        return $this->findPromotion($reference) instanceof Promotion;
    }

    /**
     * @param list<string> $sourceDatasetNames
     * @return list<array{id: int, datasetName: string, label: string, program: string, academicYear: string, frameworkCode: string|null, sourceExists: bool}>
     */
    public function listImportedReferences(array $sourceDatasetNames = []): array
    {
        $sourceLookup = array_fill_keys($sourceDatasetNames, true);
        $promotions = $this->entityManager->getRepository(Promotion::class)->createQueryBuilder('p')
            ->leftJoin('p.framework', 'f')
            ->addSelect('f')
            ->orderBy('p.startAt', 'DESC')
            ->addOrderBy('p.label', 'ASC')
            ->getQuery()
            ->getResult();

        $references = [];

        foreach ($promotions as $promotion) {
            if (!$promotion instanceof Promotion || $promotion->getId() === null || $promotion->getProgram() === null) {
                continue;
            }

            $academicYear = $this->formatAcademicYear($promotion);
            $datasetName = sprintf('%s_%s', $promotion->getProgram()->value, $academicYear);

            $references[] = [
                'id' => $promotion->getId(),
                'datasetName' => $datasetName,
                'label' => (string) $promotion->getLabel(),
                'program' => mb_strtoupper($promotion->getProgram()->value),
                'academicYear' => $academicYear,
                'frameworkCode' => $promotion->getFramework()?->getCode(),
                'sourceExists' => isset($sourceLookup[$datasetName]),
            ];
        }

        return $references;
    }

    public function import(string $datasetName): ImportReport
    {
        $dataset = $this->loadDataset($datasetName);
        $strategy = $this->resolver->resolve(
            $dataset['structure'],
            count($dataset['skills']) > 0,
            count($dataset['evaluations']) > 0,
        );

        return $this->importService->importWithStrategy($dataset, $strategy);
    }

    public function delete(string $datasetName): void
    {
        $reference = $this->resolveReference($datasetName);
        $promotion = $this->findPromotion($reference);

        if (!$promotion instanceof Promotion) {
            throw new \RuntimeException('Aucun referentiel correspondant n existe en base.');
        }

        $this->entityManager->wrapInTransaction(function () use ($promotion): void {
            if ($promotion->getId() === null) {
                return;
            }

            $frameworkId = $promotion->getFramework()?->getId();
            $this->deletePromotionsByIds([$promotion->getId()]);

            if ($frameworkId !== null && $this->countPromotionsForFramework($frameworkId) === 0) {
                $this->deleteFrameworkById($frameworkId);
            }

            $this->entityManager->clear();
        });
    }

    public function deletePromotion(int $promotionId): string
    {
        $promotion = $this->entityManager->getRepository(Promotion::class)->find($promotionId);

        if (!$promotion instanceof Promotion) {
            throw new \RuntimeException('Aucun referentiel correspondant n existe en base.');
        }

        $label = (string) $promotion->getLabel();

        $this->entityManager->wrapInTransaction(function () use ($promotion): void {
            if ($promotion->getId() === null) {
                return;
            }

            $frameworkId = $promotion->getFramework()?->getId();
            $this->deletePromotionsByIds([$promotion->getId()]);

            if ($frameworkId !== null && $this->countPromotionsForFramework($frameworkId) === 0) {
                $this->deleteFrameworkById($frameworkId);
            }

            $this->entityManager->clear();
        });

        return $label;
    }

    /**
     * @return array<string, array{created:int, updated:int, deleted:int}>
     */
    public function normalizeReport(ImportReport $report): array
    {
        return $report->all();
    }

    /**
     * @return array{
     *     structure: array<string, mixed>,
     *     modules: array<string, array<string, mixed>>,
     *     projects: array<string, array<string, mixed>>,
     *     skills: array<string, array<string, mixed>>,
     *     evaluations: array<string, array<string, mixed>>
     * }
     */
    private function loadDataset(string $datasetName): array
    {
        $this->assertValidDatasetName($datasetName);

        return $this->loader->loadFromDirectory($this->dataDir . DIRECTORY_SEPARATOR . $datasetName);
    }

    /**
     * @return array{frameworkCode: string, promotionLabel: string, startAt: \DateTimeImmutable, endAt: \DateTimeImmutable, academicYear: string}
     */
    private function resolveReference(string $datasetName): array
    {
        $dataset = $this->loadDataset($datasetName);
        $meta = $dataset['structure']['meta'] ?? [];

        $academicYear = trim((string) ($meta['academicYear'] ?? ''));
        $rncpCode = trim((string) ($meta['rncpCode'] ?? ''));
        $programCode = trim((string) ($meta['programCode'] ?? ''));

        if ($academicYear === '' || $rncpCode === '' || $programCode === '') {
            throw new \RuntimeException('Le fichier structure.json est incomplet : rncpCode, programCode et academicYear sont requis.');
        }

        [$startAt, $endAt] = $this->parseAcademicYear($academicYear);

        return [
            'frameworkCode' => 'RNCP' . $rncpCode,
            'promotionLabel' => trim(sprintf('%s %s', mb_strtoupper($programCode), $academicYear)),
            'startAt' => $startAt,
            'endAt' => $endAt,
            'academicYear' => $academicYear,
        ];
    }

    /**
     * @param array{frameworkCode: string, startAt: \DateTimeImmutable, endAt: \DateTimeImmutable} $reference
     */
    private function findFramework(array $reference): ?Framework
    {
        return $this->entityManager->getRepository(Framework::class)->findOneBy([
            'code' => $reference['frameworkCode'],
            'startAt' => $reference['startAt'],
            'endAt' => $reference['endAt'],
        ]);
    }

    /**
     * @param array{promotionLabel: string} $reference
     */
    private function findPromotion(array $reference): ?Promotion
    {
        return $this->entityManager->getRepository(Promotion::class)->findOneBy([
            'label' => $reference['promotionLabel'],
        ]);
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
            new \DateTimeImmutable(sprintf('%d-09-01 00:00:00', (int) $matches['start'])),
            new \DateTimeImmutable(sprintf('%d-08-31 23:59:59', (int) $matches['end'])),
        ];
    }

    private function deleteFrameworkById(int $frameworkId): void
    {
        $connection = $this->entityManager->getConnection();
        $promotionIds = $connection->fetchFirstColumn('SELECT id FROM promotion WHERE framework_id = ?', [$frameworkId]);

        $this->deletePromotionsByIds(array_map('intval', $promotionIds));

        $blockIds = array_map('intval', $connection->fetchFirstColumn('SELECT id FROM block WHERE framework_id = ?', [$frameworkId]));
        $skillIds = array_map('intval', $connection->fetchFirstColumn('SELECT id FROM skill WHERE framework_id = ?', [$frameworkId]));
        $evaluationIds = array_map('intval', $connection->fetchFirstColumn('SELECT id FROM evaluation WHERE framework_id = ?', [$frameworkId]));

        $this->deleteManyToManyRows('module_skill', 'skill_id', $skillIds);
        $this->deleteManyToManyRows('project_skill', 'skill_id', $skillIds);
        $this->deleteManyToManyRows('skill_evaluation', 'skill_id', $skillIds);
        $this->deleteManyToManyRows('module_evaluation', 'evaluation_id', $evaluationIds);
        $this->deleteManyToManyRows('project_evaluation', 'evaluation_id', $evaluationIds);
        $this->deleteManyToManyRows('skill_evaluation', 'evaluation_id', $evaluationIds);

        $this->deleteByIds('criteria', 'skill_id', $skillIds);
        $this->deleteByIds('evaluation_part', 'evaluation_id', $evaluationIds);
        $this->deleteByIds('evaluation', 'id', $evaluationIds);
        $this->deleteByIds('skill', 'id', $skillIds);
        $this->deleteByIds('block', 'id', $blockIds);
        $connection->delete('framework', ['id' => $frameworkId]);
    }

    private function formatAcademicYear(Promotion $promotion): string
    {
        return sprintf(
            '%s-%s',
            $promotion->getStartAt()?->format('Y') ?? '0000',
            $promotion->getEndAt()?->format('Y') ?? '0000',
        );
    }

    private function countPromotionsForFramework(int $frameworkId): int
    {
        return (int) $this->entityManager->getConnection()->fetchOne(
            'SELECT COUNT(id) FROM promotion WHERE framework_id = ?',
            [$frameworkId],
        );
    }

    /**
     * @param list<int> $promotionIds
     */
    private function deletePromotionsByIds(array $promotionIds): void
    {
        if ($promotionIds === []) {
            return;
        }

        $connection = $this->entityManager->getConnection();
        $moduleIds = array_map('intval', $connection->fetchFirstColumn(
            'SELECT id FROM module WHERE promotion_id IN (?)',
            [$promotionIds],
            [ArrayParameterType::INTEGER],
        ));
        $projectIds = array_map('intval', $connection->fetchFirstColumn(
            'SELECT id FROM project WHERE promotion_id IN (?)',
            [$promotionIds],
            [ArrayParameterType::INTEGER],
        ));

        $this->deleteManyToManyRows('module_project', 'module_id', $moduleIds);
        $this->deleteManyToManyRows('module_project', 'project_id', $projectIds);
        $this->deleteManyToManyRows('module_skill', 'module_id', $moduleIds);
        $this->deleteManyToManyRows('module_evaluation', 'module_id', $moduleIds);
        $this->deleteManyToManyRows('project_skill', 'project_id', $projectIds);
        $this->deleteManyToManyRows('project_evaluation', 'project_id', $projectIds);

        $this->deleteByIds('chapter', 'module_id', $moduleIds);
        $this->deleteByIds('chapter', 'project_id', $projectIds);
        $this->deleteByIds('module', 'id', $moduleIds);
        $this->deleteByIds('project', 'id', $projectIds);
        $this->deleteByIds('promotion', 'id', $promotionIds);
    }

    /**
     * @param list<int> $ids
     */
    private function deleteManyToManyRows(string $table, string $column, array $ids): void
    {
        $this->deleteByIds($table, $column, $ids);
    }

    /**
     * @param list<int> $ids
     */
    private function deleteByIds(string $table, string $column, array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $this->entityManager->getConnection()->executeStatement(
            sprintf('DELETE FROM %s WHERE %s IN (?)', $table, $column),
            [$ids],
            [ArrayParameterType::INTEGER],
        );
    }

    private function assertValidDatasetName(string $datasetName): void
    {
        if (!preg_match('/^[a-z0-9]+_\d{4}-\d{4}$/', $datasetName)) {
            throw new \RuntimeException('Nom de dossier invalide.');
        }
    }
}
