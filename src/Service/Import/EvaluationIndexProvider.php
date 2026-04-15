<?php

namespace App\Service\Import;

use App\Entity\Evaluation;
use App\Entity\Framework;
use Doctrine\ORM\EntityManagerInterface;

final readonly class EvaluationIndexProvider
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    )
    {
    }

    /**
     * @return array<string, Evaluation>
     */
    public function getByFramework(Framework $framework): array
    {
        /** @var list<Evaluation> $evaluations */
        $evaluations = $this->entityManager->getRepository(Evaluation::class)->findBy([
            'framework' => $framework,
        ], [
            'position' => 'ASC',
            'id' => 'ASC',
        ]);

        $index = [];

        foreach ($evaluations as $evaluation) {
            $code = (string)$evaluation->getCode();
            if ($code !== '') {
                $index[$code] = $evaluation;
            }
        }

        return $index;
    }
}
