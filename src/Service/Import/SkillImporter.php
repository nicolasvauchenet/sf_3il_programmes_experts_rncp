<?php

namespace App\Service\Import;

use App\Dto\Import\ImportReport;
use App\Entity\Block;
use App\Entity\Criteria;
use App\Entity\Framework;
use App\Entity\Skill;
use Doctrine\ORM\EntityManagerInterface;

final readonly class SkillImporter
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CodeNormalizer         $codeNormalizer,
    )
    {
    }

    /**
     * @param array<string, mixed> $structure
     * @param array<string, array<string, mixed>> $skillFiles
     * @param array<string, Block> $blocks
     * @return array<string, Skill>
     */
    public function import(
        array        $structure,
        array        $skillFiles,
        Framework    $framework,
        array        $blocks,
        ImportReport $report,
    ): array
    {
        $result = [];
        $frameworkCode = (string)$framework->getCode();

        foreach (($structure['skills'] ?? []) as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $shortCode = $this->codeNormalizer->normalizeShortSkillCode((string)($row['code'] ?? ''));
            $blockCode = $this->codeNormalizer->normalizeBlockCode((string)($row['blockCode'] ?? $row['blocCode'] ?? ''));

            if ($shortCode === '' || $blockCode === '' || !isset($blocks[$blockCode])) {
                continue;
            }

            $fullCode = $this->codeNormalizer->normalizeSkillCode($frameworkCode, $blockCode, $shortCode);

            if ($fullCode === '') {
                continue;
            }

            /** @var Skill|null $skill */
            $skill = $this->entityManager->getRepository(Skill::class)->findOneBy([
                'framework' => $framework,
                'code' => $fullCode,
            ]);

            $isNew = !$skill instanceof Skill;

            if ($isNew) {
                $skill = new Skill();
                $skill->setFramework($framework);
                $skill->setCode($fullCode);
                $this->entityManager->persist($skill);
                $report->markCreated('skills');
            } else {
                $report->markUpdated('skills');
            }

            $detailFile = $this->findSkillFile($skillFiles, $fullCode);

            $skill->setBlock($blocks[$blockCode]);
            $skill->setTitle(trim((string)($row['title'] ?? $shortCode)));
            $skill->setDescription($this->extractDescription($detailFile));
            $skill->setPosition($index + 1);

            $this->syncCriteria($skill, $detailFile);

            $result[$fullCode] = $skill;
        }

        return $result;
    }

    /**
     * @param array<string, array<string, mixed>> $skillFiles
     * @return array<string, mixed>|null
     */
    private function findSkillFile(array $skillFiles, string $fullCode): ?array
    {
        return $skillFiles[$fullCode] ?? null;
    }

    /**
     * @param array<string, mixed>|null $detailFile
     */
    private function extractDescription(?array $detailFile): ?string
    {
        $description = trim((string)($detailFile['description'] ?? ''));

        return $description !== '' ? $description : null;
    }

    /**
     * @param array<string, mixed>|null $detailFile
     */
    private function syncCriteria(Skill $skill, ?array $detailFile): void
    {
        $sourceCriteria = $detailFile['criteria'] ?? [];
        if (!is_array($sourceCriteria)) {
            $sourceCriteria = [];
        }

        $existingByCode = [];
        foreach ($skill->getCriteria() as $criteria) {
            $existingByCode[(string)$criteria->getCode()] = $criteria;
        }

        $keptCodes = [];

        foreach ($sourceCriteria as $index => $criterion) {
            $position = $index + 1;
            $code = sprintf('%s-CR%02d', (string)$skill->getCode(), $position);
            $title = sprintf('Critère %02d', $position);

            if (is_string($criterion)) {
                $description = trim($criterion);
            } elseif (is_array($criterion)) {
                $title = trim((string)($criterion['title'] ?? $criterion['description'] ?? $title));
                $description = $this->criterionDescriptionFromArray($criterion);
            } else {
                continue;
            }

            $criteria = $existingByCode[$code] ?? new Criteria();

            if ($criteria->getId() === null) {
                $criteria->setCode($code);
                $criteria->setSkill($skill);
                $this->entityManager->persist($criteria);
            }

            $criteria->setTitle($title);
            $criteria->setDescription($description !== '' ? $description : null);
            $criteria->setPosition($position);

            $skill->addCriteria($criteria);
            $keptCodes[] = $code;
        }

        foreach ($skill->getCriteria()->toArray() as $criteria) {
            $code = (string)$criteria->getCode();

            if (!in_array($code, $keptCodes, true)) {
                $skill->removeCriteria($criteria);
                $this->entityManager->remove($criteria);
            }
        }
    }

    /**
     * @param array<string, mixed> $criterion
     */
    private function criterionDescriptionFromArray(array $criterion): string
    {
        $description = trim((string)($criterion['description'] ?? ''));
        $indicators = $criterion['indicators'] ?? [];

        if (!is_array($indicators) || $indicators === []) {
            return $description;
        }

        $lines = [];

        if ($description !== '') {
            $lines[] = $description;
        }

        foreach ($indicators as $indicator) {
            $indicator = trim((string)$indicator);

            if ($indicator !== '') {
                $lines[] = '- ' . $indicator;
            }
        }

        return implode("\n", $lines);
    }
}
