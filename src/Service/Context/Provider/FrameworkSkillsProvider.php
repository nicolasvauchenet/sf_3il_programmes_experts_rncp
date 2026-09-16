<?php

namespace App\Service\Context\Provider;

use App\Dto\Context\ResolvedSkillSheet;
use App\Dto\Context\SkillSheet;
use App\Entity\Criteria;
use App\Entity\Evaluation;
use App\Entity\Module;
use App\Entity\Project;
use App\Entity\Promotion;
use App\Entity\Skill;
use App\Enum\Program;
use App\Repository\PromotionRepository;
use App\Service\Context\Loader\FrameworkStructureLoader;
use App\Service\Context\SkillSheetResolver;

final readonly class FrameworkSkillsProvider
{
    public function __construct(
        private PromotionRepository      $promotionRepository,
        private FrameworkStructureLoader $structureLoader,
        private SkillSheetResolver       $resolver,
    )
    {
    }

    /**
     * @return ResolvedSkillSheet[]
     */
    public function listSkills(string $promotion, string $year): array
    {
        $promotionEntity = $this->resolvePromotion($promotion, $year);
        $framework = $promotionEntity->getFramework();

        if ($framework === null) {
            return [];
        }

        $structure = $this->structureLoader->load($promotion, $year);

        $skills = $framework->getSkills()->toArray();

        usort(
            $skills,
            static fn(Skill $a, Skill $b): int => [
                    $a->getBlock()?->getPosition() ?? 0,
                    $a->getPosition() ?? 0,
                    (string)$a->getCode(),
                ] <=> [
                    $b->getBlock()?->getPosition() ?? 0,
                    $b->getPosition() ?? 0,
                    (string)$b->getCode(),
                ]
        );

        $sheets = array_map(
            fn(Skill $skill): SkillSheet => $this->mapSkillToSheet($skill, $year),
            $skills
        );

        $resolved = [];

        foreach ($sheets as $sheet) {
            $resolved[] = $this->resolver->resolve($sheet, $structure, $sheets);
        }

        return $resolved;
    }

    private function mapSkillToSheet(Skill $skill, string $year): SkillSheet
    {
        $criteria = [];

        foreach ($skill->getCriteria() as $criterion) {
            if (!$criterion instanceof Criteria) {
                continue;
            }

            $description = trim((string)($criterion->getDescription() ?? ''));
            if ($description !== '') {
                $criteria[] = $description;
            }
        }

        $criteria = array_values(array_unique($criteria));

        $modules = [];
        foreach ($skill->getModules() as $module) {
            if (!$module instanceof Module) {
                continue;
            }

            $modules[] = [
                'code' => $this->extractShortCode((string)$module->getCode()),
                'title' => (string)$module->getTitle(),
                'fullCode' => strtolower((string)$module->getCode()),
            ];
        }

        usort(
            $modules,
            static fn(array $a, array $b): int => [$a['fullCode'], $a['title']] <=> [$b['fullCode'], $b['title']]
        );

        $projects = [];
        foreach ($skill->getProjects() as $project) {
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
            $projects,
            static fn(array $a, array $b): int => [$a['blockCode'], $a['code']] <=> [$b['blockCode'], $b['code']]
        );

        $evaluationsIndex = [];

        foreach ($skill->getEvaluations() as $evaluation) {
            if (!$evaluation instanceof Evaluation) {
                continue;
            }

            $key = strtolower((string)$evaluation->getCode());

            $evaluationsIndex[$key] = [
                'code' => $key,
                'title' => (string)$evaluation->getTitle(),
                'blockCode' => (string)($evaluation->getBlock()?->getCode() ?? ''),
            ];
        }

        foreach ($skill->getModules() as $module) {
            if (!$module instanceof Module) {
                continue;
            }

            foreach ($module->getEvaluations() as $evaluation) {
                if (!$evaluation instanceof Evaluation) {
                    continue;
                }

                $key = strtolower((string)$evaluation->getCode());

                $evaluationsIndex[$key] = [
                    'code' => $key,
                    'title' => (string)$evaluation->getTitle(),
                    'blockCode' => (string)($evaluation->getBlock()?->getCode() ?? ''),
                ];
            }
        }

        foreach ($skill->getProjects() as $project) {
            if (!$project instanceof Project) {
                continue;
            }

            foreach ($project->getEvaluations() as $evaluation) {
                if (!$evaluation instanceof Evaluation) {
                    continue;
                }

                $key = strtolower((string)$evaluation->getCode());

                $evaluationsIndex[$key] = [
                    'code' => $key,
                    'title' => (string)$evaluation->getTitle(),
                    'blockCode' => (string)($evaluation->getBlock()?->getCode() ?? ''),
                ];
            }
        }

        $evaluations = array_values($evaluationsIndex);

        usort(
            $evaluations,
            static fn(array $a, array $b): int => [$a['blockCode'], $a['code']] <=> [$b['blockCode'], $b['code']]
        );

        $meta = [
            'type' => 'skill',
            'code' => (string)$skill->getCode(),
            'title' => (string)$skill->getTitle(),
            'academicYear' => $year,
            'blocCode' => (string)($skill->getBlock()?->getCode() ?? ''),
            'blocName' => (string)($skill->getBlock()?->getTitle() ?? ''),
            'rncpCode' => (string)($skill->getFramework()?->getCode() ?? ''),
        ];

        $raw = [
            'meta' => $meta,
            'description' => (string)($skill->getDescription() ?? ''),
            'criteria' => $criteria,
            'modules' => $modules,
            'projects' => $projects,
            'evaluations' => $evaluations,
        ];

        return new SkillSheet(
            fileCode: strtolower((string)$skill->getCode()),
            path: '',
            meta: $meta,
            description: (string)($skill->getDescription() ?? ''),
            criteria: $criteria,
            modules: $modules,
            projects: $projects,
            evaluations: $evaluations,
            relatedSkills: [],
            raw: $raw,
        );
    }

    private function resolvePromotion(string $promotion, string $year): Promotion
    {
        $program = Program::tryFrom(strtolower(trim($promotion)));

        if (!$program instanceof Program) {
            throw new \RuntimeException(sprintf('Programme inconnu : "%s".', $promotion));
        }

        $candidates = $this->promotionRepository->findBy([
            'program' => $program,
        ]);

        foreach ($candidates as $candidate) {
            if (!$candidate instanceof Promotion) {
                continue;
            }

            $label = strtoupper(trim((string)$candidate->getLabel()));
            if ($label === strtoupper($program->value . ' ' . $year)) {
                return $candidate;
            }

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

    private function extractShortCode(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $parts = explode('-', $value);

        return (string)end($parts);
    }
}
