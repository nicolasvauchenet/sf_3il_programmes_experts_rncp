<?php

namespace App\Service\Import;

use App\Entity\Framework;
use App\Entity\Skill;
use Doctrine\ORM\EntityManagerInterface;

final readonly class SkillIndexProvider
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    )
    {
    }

    /**
     * @return array<string, Skill>
     */
    public function getByFramework(Framework $framework): array
    {
        /** @var list<Skill> $skills */
        $skills = $this->entityManager->getRepository(Skill::class)->findBy([
            'framework' => $framework,
        ], [
            'position' => 'ASC',
            'id' => 'ASC',
        ]);

        $index = [];

        foreach ($skills as $skill) {
            $code = trim((string)$skill->getCode());

            if ($code !== '') {
                $index[$code] = $skill;
            }
        }

        return $index;
    }
}
