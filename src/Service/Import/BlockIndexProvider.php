<?php

namespace App\Service\Import;

use App\Entity\Block;
use App\Entity\Framework;
use Doctrine\ORM\EntityManagerInterface;

final readonly class BlockIndexProvider
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    )
    {
    }

    /**
     * @return array<string, Block>
     */
    public function getByFramework(Framework $framework): array
    {
        /** @var list<Block> $blocks */
        $blocks = $this->entityManager->getRepository(Block::class)->findBy([
            'framework' => $framework,
        ], [
            'position' => 'ASC',
            'id' => 'ASC',
        ]);

        $index = [];

        foreach ($blocks as $block) {
            $code = (string)$block->getCode();

            if ($code !== '') {
                $index[$code] = $block;
            }
        }

        return $index;
    }
}
