<?php

namespace App\Service\Context\Provider;

use App\Dto\Context\EvaluationSheet;
use App\Entity\Criteria;
use App\Entity\Evaluation;
use App\Entity\EvaluationPart;
use App\Entity\Module;
use App\Entity\Project;
use App\Entity\Promotion;
use App\Entity\Skill;
use App\Enum\Program;
use App\Repository\PromotionRepository;
use App\Service\Context\EvaluationSheetResolver;
use App\Service\Context\Loader\FrameworkStructureLoader;

final readonly class FrameworkEvaluationsProvider
{
    public function __construct(
        private PromotionRepository      $promotionRepository,
        private FrameworkStructureLoader $structureLoader,
        private EvaluationSheetResolver  $resolver,
    )
    {
    }

    /**
     * @return array<int,\App\Dto\Context\ResolvedEvaluationSheet>
     */
    public function listEvaluations(string $promotion, string $year): array
    {
        $promotionEntity = $this->resolvePromotion($promotion, $year);
        $framework = $promotionEntity->getFramework();

        if ($framework === null) {
            return [];
        }

        $structure = $this->structureLoader->load($promotion, $year);
        $evaluations = $framework->getEvaluations()->toArray();

        usort(
            $evaluations,
            static fn(Evaluation $a, Evaluation $b): int => ($a->getPosition() ?? 0) <=> ($b->getPosition() ?? 0)
        );

        $sheets = [];

        foreach ($evaluations as $evaluation) {
            if (!$evaluation instanceof Evaluation) {
                continue;
            }

            $sheet = $this->mapEvaluationToSheet($evaluation, $year);
            $sheets[] = $this->resolver->resolve($sheet, $structure);
        }

        return $sheets;
    }

    private function mapEvaluationToSheet(Evaluation $evaluation, string $year): EvaluationSheet
    {
        $skills = [];
        $criteria = [];
        $skillsWithCriteria = [];

        foreach ($this->collectSkills($evaluation) as $skill) {
            $shortCode = $this->extractShortCode((string)$skill->getCode());
            $fileCode = strtolower((string)$skill->getCode());
            $description = (string)($skill->getDescription() ?? $skill->getTitle() ?? '');

            $skills[] = [
                'code' => $shortCode,
                'fileCode' => $fileCode,
                'description' => $description,
            ];

            $skillCriteria = [];

            foreach ($skill->getCriteria() as $criterion) {
                if (!$criterion instanceof Criteria) {
                    continue;
                }

                $formattedCriterion = $this->formatCriterion($criterion);

                if ($formattedCriterion === '') {
                    continue;
                }

                $skillCriteria[] = $formattedCriterion;
                $criteria[] = $formattedCriterion;
            }

            $skillsWithCriteria[] = [
                'code' => $shortCode,
                'fileCode' => $fileCode,
                'description' => $description,
                'criteria' => $this->uniqueCriteria($skillCriteria),
            ];
        }

        $criteria = $this->uniqueCriteria($criteria);

        $modules = [];

        foreach ($evaluation->getModules() as $module) {
            if (!$module instanceof Module) {
                continue;
            }

            $modules[] = [
                'code' => strtolower((string)$module->getCode()),
                'title' => (string)$module->getTitle(),
                'blockCode' => (string)($module->getBlock()?->getCode() ?? ''),
            ];
        }

        $projects = [];

        foreach ($evaluation->getProjects() as $project) {
            if (!$project instanceof Project) {
                continue;
            }

            $projects[] = [
                'code' => strtolower((string)$project->getCode()),
                'title' => (string)$project->getTitle(),
                'blockCode' => (string)($project->getBlock()?->getCode() ?? ''),
            ];
        }

        usort(
            $modules,
            static fn(array $a, array $b): int => [$a['blockCode'], $a['code']] <=> [$b['blockCode'], $b['code']]
        );
        $modules = $this->uniqueReferences($modules);

        usort(
            $projects,
            static fn(array $a, array $b): int => [$a['blockCode'], $a['code']] <=> [$b['blockCode'], $b['code']]
        );
        $projects = $this->uniqueReferences($projects);

        $parts = [];
        foreach ($evaluation->getEvaluationParts() as $part) {
            if (!$part instanceof EvaluationPart) {
                continue;
            }

            $parts[] = [
                'code' => (string)$part->getCode(),
                'title' => (string)$part->getTitle(),
                'type' => $part->getType()?->label() ?? '',
                'description' => (string)($part->getDescription() ?? ''),
                'duration' => (int)($part->getDuration() ?? 0),
                'points' => (int)($part->getPoints() ?? 0),
                'coefficient' => (int)($part->getCoefficient() ?? 0),
                'position' => (int)($part->getPosition() ?? 0),
            ];
        }

        usort(
            $parts,
            static fn(array $a, array $b): int => ($a['position'] ?? 0) <=> ($b['position'] ?? 0)
        );

        $modalities = is_array($evaluation->getModalities()) ? $evaluation->getModalities() : [];
        $validationRules = is_array($evaluation->getValidationRules()) ? $evaluation->getValidationRules() : [];

        $meta = [
            'type' => 'evaluation',
            'code' => (string)$evaluation->getCode(),
            'title' => (string)$evaluation->getTitle(),
            'academicYear' => $year,
            'rncpCode' => (string)($evaluation->getFramework()?->getCode() ?? ''),
            'blocCode' => (string)($evaluation->getBlock()?->getCode() ?? ''),
            'blocName' => (string)($evaluation->getBlock()?->getTitle() ?? ''),
        ];

        $exam = [
            'parts' => $parts,
            'validation' => $validationRules,
        ];

        $raw = [
            'meta' => $meta,
            'description' => (string)($evaluation->getDescription() ?? ''),
            'skills' => $skills,
            'criteria' => $criteria,
            'skillsWithCriteria' => $skillsWithCriteria,
            'modules' => $modules,
            'projects' => $projects,
            'modalities' => $modalities,
            'exam' => $exam,
        ];

        return new EvaluationSheet(
            fileCode: strtolower((string)$evaluation->getCode()),
            path: '',
            meta: $meta,
            evaluationNumber: $this->extractEvaluationNumber((string)$evaluation->getCode()),
            description: (string)($evaluation->getDescription() ?? ''),
            skills: $skills,
            criteria: $criteria,
            skillsWithCriteria: $skillsWithCriteria,
            modules: $modules,
            projects: $projects,
            modalities: $modalities,
            exam: $exam,
            raw: $raw,
        );
    }

    /**
     * @return array<int,Skill>
     */
    private function collectSkills(Evaluation $evaluation): array
    {
        $skills = [];

        foreach ($evaluation->getSkills() as $skill) {
            if (!$skill instanceof Skill) {
                continue;
            }

            $skills[] = $skill;
        }

        usort(
            $skills,
            static fn(Skill $a, Skill $b): int => [
                    $a->getPosition() ?? 0,
                    strtolower((string)$a->getCode()),
                ] <=> [
                    $b->getPosition() ?? 0,
                    strtolower((string)$b->getCode()),
                ]
        );

        return $skills;
    }

    private function resolvePromotion(string $promotion, string $year): Promotion
    {
        $program = Program::tryFrom(strtolower(trim($promotion)));

        if (!$program instanceof Program) {
            throw new \RuntimeException(sprintf('Programme inconnu "%s".', $promotion));
        }

        $promotions = $this->promotionRepository->findBy([
            'program' => $program,
        ]);

        foreach ($promotions as $candidate) {
            $start = $candidate->getStartAt();
            $end = $candidate->getEndAt();

            if (
                $start instanceof \DateTimeImmutable
                && $end instanceof \DateTimeImmutable
                && sprintf('%s-%s', $start->format('Y'), $end->format('Y')) === $year
            ) {
                return $candidate;
            }
        }

        throw new \RuntimeException(sprintf(
            'Promotion introuvable pour le programme "%s" et l’année "%s".',
            $promotion,
            $year
        ));
    }

    private function formatCriterion(Criteria $criterion): string
    {
        $title = trim((string)($criterion->getTitle() ?? ''));
        $description = trim((string)($criterion->getDescription() ?? ''));

        if ($description !== '') {
            return $description;
        }

        return $title;
    }

    /**
     * @param array<int,string> $criteria
     * @return array<int,string>
     */
    private function uniqueCriteria(array $criteria): array
    {
        $criteria = array_map(
            static fn(string $criterion): string => trim($criterion),
            $criteria
        );

        $criteria = array_filter(
            $criteria,
            static fn(string $criterion): bool => $criterion !== ''
        );

        return array_values(array_unique($criteria));
    }

    /**
     * @param array<int,array{code:string,title:string,blockCode:string}> $references
     * @return array<int,array{code:string,title:string,blockCode:string}>
     */
    private function uniqueReferences(array $references): array
    {
        $unique = [];

        foreach ($references as $reference) {
            $key = strtolower(trim($reference['code']));

            if ($key === '' || isset($unique[$key])) {
                continue;
            }

            $unique[$key] = $reference;
        }

        return array_values($unique);
    }

    private function extractShortCode(string $code): string
    {
        $code = trim($code);

        if ($code === '') {
            return '';
        }

        if (preg_match('/(C\d{2})$/i', $code, $matches) === 1) {
            return strtoupper($matches[1]);
        }

        return strtoupper($code);
    }

    private function extractEvaluationNumber(string $code): int
    {
        if (preg_match('/EC(\d{2})$/i', $code, $matches) === 1) {
            return (int)$matches[1];
        }

        return 0;
    }
}
