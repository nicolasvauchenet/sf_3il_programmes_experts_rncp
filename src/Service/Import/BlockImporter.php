<?php

namespace App\Service\Import;

use App\Dto\Import\ImportReport;
use App\Entity\Block;
use App\Entity\Framework;
use Doctrine\ORM\EntityManagerInterface;

final readonly class BlockImporter
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    )
    {
    }

    /**
     * @param array<string, mixed> $structure
     * @return array<string, Block>
     */
    public function import(array $structure, Framework $framework, ImportReport $report): array
    {
        $result = [];

        foreach (($structure['blocks'] ?? []) as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $code = trim((string)($row['code'] ?? ''));
            if ($code === '') {
                continue;
            }

            /** @var Block|null $block */
            $block = $this->entityManager->getRepository(Block::class)->findOneBy([
                'framework' => $framework,
                'code' => $code,
            ]);

            $isNew = !$block instanceof Block;

            if ($isNew) {
                $block = new Block();
                $block->setFramework($framework);
                $block->setCode($code);
                $this->entityManager->persist($block);
                $report->markCreated('blocks');
            } else {
                $report->markUpdated('blocks');
            }

            $block->setTitle(trim((string)($row['title'] ?? $code)));
            $block->setDescription(null);
            $block->setPosition($index + 1);

            $result[$code] = $block;
        }

        return $result;
    }
}
