<?php

namespace App\Service\Import;

use App\Entity\Module;
use App\Entity\Project;
use App\Entity\Promotion;
use App\Enum\Program;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DatabaseDatasetInheritanceProvider
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
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
    public function inherit(string $datasetName): array
    {
        if (!preg_match('/^(?<program>[a-z0-9]+)_(?<year>\d{4}-\d{4})$/', strtolower($datasetName), $matches)) {
            throw new \RuntimeException(sprintf('Impossible de déduire le programme et le millésime depuis "%s".', $datasetName));
        }

        $program = Program::tryFrom($matches['program']);
        if (!$program instanceof Program) {
            throw new \RuntimeException(sprintf('Programme inconnu dans le jeu de données "%s".', $datasetName));
        }

        $source = $this->findSourcePromotion($program, $matches['year']);
        if (!$source instanceof Promotion || $source->getFramework() === null) {
            throw new \RuntimeException(sprintf(
                'Aucun référentiel antérieur n’est disponible en base pour initialiser "%s" sans structure.json.',
                $datasetName,
            ));
        }

        return $this->export($source, $datasetName, $matches['year']);
    }

    private function findSourcePromotion(Program $program, string $targetYear): ?Promotion
    {
        [$targetStart] = explode('-', $targetYear, 2);
        $promotions = $this->entityManager->getRepository(Promotion::class)->findBy(
            ['program' => $program],
            ['startAt' => 'DESC'],
        );

        foreach ($promotions as $promotion) {
            if (!$promotion instanceof Promotion || !$promotion->getStartAt() instanceof \DateTimeImmutable) {
                continue;
            }

            if ($promotion->getStartAt()->format('Y') <= $targetStart) {
                return $promotion;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function export(Promotion $promotion, string $datasetName, string $academicYear): array
    {
        $framework = $promotion->getFramework();
        $rncpCode = preg_replace('/^RNCP/i', '', (string)$framework?->getCode()) ?? '';

        $structure = [
            'meta' => [
                'datasetCode' => $datasetName,
                'rncpCode' => $rncpCode,
                'programCode' => strtoupper((string)$promotion->getProgram()?->value),
                'programTitle' => (string)($framework?->getTitle() ?? ''),
                'academicYear' => $academicYear,
            ],
            'modules' => [],
            'projects' => [],
            'skills' => [],
            'evaluations' => [],
            'blocks' => [],
        ];

        foreach ($this->sorted($framework?->getBlocks() ?? []) as $block) {
            $structure['blocks'][] = [
                'code' => (string)$block->getCode(),
                'title' => (string)$block->getTitle(),
                'description' => (string)($block->getDescription() ?? ''),
            ];
        }

        $modules = [];
        foreach ($this->sorted($promotion->getModules()) as $module) {
            $code = (string)$module->getCode();
            $structure['modules'][] = [
                'code' => $code,
                'fullCode' => $code,
                'title' => (string)$module->getTitle(),
                'blockCode' => (string)$module->getBlock()?->getCode(),
                'skills' => $this->codes($module->getSkills()),
                'projects' => $this->codes($module->getProjects()),
                'evaluations' => $this->codes($module->getEvaluations()),
            ];
            $modules[$code] = [
                'meta' => [
                    'type' => 'module',
                    'code' => $code,
                    'title' => (string)$module->getTitle(),
                    'durationDays' => $module->getDurationDays(),
                    'durationHours' => $module->getDurationHours(),
                ],
                'objectives' => ['description' => (string)($module->getObjectives() ?? '')],
                'prerequisites' => ['description' => (string)($module->getPrerequisites() ?? '')],
                'outline' => ['chapters' => $this->chapters($module)],
                'exercises' => $module->getExercises() ?? [],
                'bibliography' => $module->getBibliography() ?? [],
                'onlineResources' => $module->getOnlineResources() ?? [],
                'teachingMethods' => $module->getTeachingMethods() ?? [],
            ];
        }

        $projects = [];
        foreach ($this->sorted($promotion->getProjects()) as $project) {
            $code = (string)$project->getCode();
            $structure['projects'][] = [
                'code' => $code,
                'fullCode' => $code,
                'title' => (string)$project->getTitle(),
                'blockCode' => (string)$project->getBlock()?->getCode(),
                'modules' => $this->codes($project->getModules()),
                'skills' => $this->codes($project->getSkills()),
                'evaluations' => $this->codes($project->getEvaluations()),
            ];
            $projects[$code] = [
                'meta' => [
                    'type' => 'project',
                    'code' => $code,
                    'title' => (string)$project->getTitle(),
                    'durationDays' => $project->getDurationDays(),
                    'durationHours' => $project->getDurationHours(),
                ],
                'description' => (string)($project->getDescription() ?? ''),
                'objectives' => ['description' => (string)($project->getObjectives() ?? '')],
                'prerequisites' => ['description' => (string)($project->getPrerequisites() ?? '')],
                'outline' => ['chapters' => $this->chapters($project)],
            ];
        }

        $skills = [];
        foreach ($this->sorted($framework?->getSkills() ?? []) as $skill) {
            $code = (string)$skill->getCode();
            $structure['skills'][] = [
                'code' => $this->shortCode($code),
                'title' => (string)$skill->getTitle(),
                'blockCode' => (string)$skill->getBlock()?->getCode(),
            ];
            $criteria = [];
            foreach ($this->sorted($skill->getCriteria()) as $criterion) {
                $criteria[] = [
                    'title' => (string)$criterion->getTitle(),
                    'description' => (string)($criterion->getDescription() ?? ''),
                ];
            }
            $skills[$code] = [
                'meta' => ['type' => 'skill', 'code' => $code, 'title' => (string)$skill->getTitle()],
                'description' => (string)($skill->getDescription() ?? ''),
                'criteria' => $criteria,
            ];
        }

        $evaluations = [];
        foreach ($this->sorted($framework?->getEvaluations() ?? []) as $evaluation) {
            $code = (string)$evaluation->getCode();
            $structure['evaluations'][] = [
                'code' => $code,
                'title' => (string)$evaluation->getTitle(),
                'blockCode' => (string)$evaluation->getBlock()?->getCode(),
                'modules' => $this->codes($evaluation->getModules()),
                'projects' => $this->codes($evaluation->getProjects()),
                'skills' => $this->codes($evaluation->getSkills()),
            ];
            $parts = [];
            foreach ($this->sorted($evaluation->getEvaluationParts()) as $part) {
                $parts[] = [
                    'code' => (string)$part->getCode(),
                    'title' => (string)$part->getTitle(),
                    'type' => (string)$part->getType()?->value,
                    'duration' => $part->getDuration() ?? $part->getDurationLabel(),
                    'points' => $part->getPoints(),
                    'coefficient' => $part->getCoefficient(),
                ];
            }
            $evaluations[$code] = [
                'meta' => ['type' => 'evaluation', 'code' => $code, 'title' => (string)$evaluation->getTitle()],
                'description' => (string)($evaluation->getDescription() ?? ''),
                'modalities' => $evaluation->getModalities() ?? [],
                'exam' => [
                    'parts' => $parts,
                    'validation' => $evaluation->getValidationRules() ?? [],
                ],
                'skills' => array_map(static fn(string $code): array => ['code' => $code], $this->codes($evaluation->getSkills())),
            ];
        }

        return compact('structure', 'modules', 'projects', 'skills', 'evaluations');
    }

    /** @return list<string> */
    private function codes(iterable $entities): array
    {
        $codes = [];
        foreach ($entities as $entity) {
            $code = trim((string)$entity->getCode());
            if ($code !== '') {
                $codes[] = $code;
            }
        }

        return $codes;
    }

    /** @return list<array{title: string, items: array}> */
    private function chapters(Module|Project $owner): array
    {
        $chapters = [];
        foreach ($this->sorted($owner->getChapters()) as $chapter) {
            $chapters[] = ['title' => (string)$chapter->getTitle(), 'items' => $chapter->getItems() ?? []];
        }

        return $chapters;
    }

    private function shortCode(string $code): string
    {
        $parts = explode('-', $code);

        return (string)end($parts);
    }

    /** @return list<object> */
    private function sorted(iterable $entities): array
    {
        $entities = is_array($entities) ? $entities : iterator_to_array($entities);
        usort($entities, static fn(object $left, object $right): int => [
            $left->getPosition() ?? 0,
            method_exists($left, 'getCode') ? (string)$left->getCode() : (string)$left->getTitle(),
        ] <=> [
            $right->getPosition() ?? 0,
            method_exists($right, 'getCode') ? (string)$right->getCode() : (string)$right->getTitle(),
        ]);

        return array_values($entities);
    }
}
