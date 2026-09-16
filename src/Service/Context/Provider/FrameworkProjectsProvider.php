<?php

namespace App\Service\Context\Provider;

use App\Dto\Context\ProjectSheet;
use App\Dto\Context\ResolvedProjectSheet;
use App\Dto\Context\ResolvedSkillSheet;
use App\Entity\Evaluation;
use App\Entity\Module;
use App\Entity\Project;
use App\Entity\Promotion;
use App\Entity\Skill;
use App\Enum\Program;
use App\Repository\PromotionRepository;
use App\Service\Context\Loader\FrameworkStructureLoader;
use App\Service\Context\ProjectSheetResolver;

final readonly class FrameworkProjectsProvider
{
    public function __construct(
        private PromotionRepository      $promotionRepository,
        private FrameworkSkillsProvider  $skillsProvider,
        private FrameworkStructureLoader $structureLoader,
        private ProjectSheetResolver     $resolver,
    )
    {
    }

    /**
     * @return ResolvedProjectSheet[]
     */
    public function listProjects(string $promotion, string $year): array
    {
        $promotionEntity = $this->resolvePromotion($promotion, $year);
        $structure = $this->structureLoader->load($promotion, $year);
        $resolvedSkills = $this->skillsProvider->listSkills($promotion, $year);
        $skillsIndex = $this->indexSkills($resolvedSkills);

        $projects = $promotionEntity->getProjects()->toArray();

        usort(
            $projects,
            static fn(Project $a, Project $b): int => [
                    $a->getBlock()?->getPosition() ?? 0,
                    $a->getPosition() ?? 0,
                    (string)$a->getCode(),
                ] <=> [
                    $b->getBlock()?->getPosition() ?? 0,
                    $b->getPosition() ?? 0,
                    (string)$b->getCode(),
                ]
        );

        $sheets = [];

        foreach ($projects as $project) {
            if (!$project instanceof Project) {
                continue;
            }

            $sheet = $this->mapProjectToSheet($project, $year);
            $sheets[] = $this->resolver->resolve($sheet, $structure, $skillsIndex);
        }

        return $sheets;
    }

    /**
     * @param ResolvedSkillSheet[] $skills
     * @return array<string,ResolvedSkillSheet>
     */
    private function indexSkills(array $skills): array
    {
        $index = [];

        foreach ($skills as $skill) {
            $key = $this->buildSkillKey($skill->blocCode(), $skill->skillCode());
            $index[$key] = $skill;
        }

        return $index;
    }

    private function mapProjectToSheet(Project $project, string $year): ProjectSheet
    {
        $skills = [];

        foreach ($project->getSkills() as $skill) {
            if (!$skill instanceof Skill) {
                continue;
            }

            $skills[] = [
                'code' => $this->extractShortCode((string)$skill->getCode()),
                'fileCode' => strtolower((string)$skill->getCode()),
                'description' => (string)($skill->getDescription() ?? $skill->getTitle() ?? ''),
            ];
        }

        $modules = [];

        foreach ($project->getModules() as $module) {
            if (!$module instanceof Module) {
                continue;
            }

            $modules[] = [
                'code' => strtolower((string)$module->getCode()),
                'title' => (string)$module->getTitle(),
                'blockCode' => (string)($module->getBlock()?->getCode() ?? ''),
            ];
        }

        $evaluations = [];

        foreach ($project->getEvaluations() as $evaluation) {
            if (!$evaluation instanceof Evaluation) {
                continue;
            }

            $evaluations[] = [
                'code' => strtolower((string)$evaluation->getCode()),
                'title' => (string)$evaluation->getTitle(),
                'blockCode' => (string)($evaluation->getBlock()?->getCode() ?? ''),
            ];
        }

        usort(
            $modules,
            static fn(array $a, array $b): int => [$a['blockCode'], $a['code']] <=> [$b['blockCode'], $b['code']]
        );

        usort(
            $evaluations,
            static fn(array $a, array $b): int => [$a['blockCode'], $a['code']] <=> [$b['blockCode'], $b['code']]
        );

        $meta = [
            'type' => 'project',
            'code' => (string)$project->getCode(),
            'title' => (string)$project->getTitle(),
            'academicYear' => $year,
            'blocCode' => (string)($project->getBlock()?->getCode() ?? ''),
            'blocName' => (string)($project->getBlock()?->getTitle() ?? ''),
            'durationDays' => (int)($project->getDurationDays() ?? 0),
            'durationHours' => (int)($project->getDurationHours() ?? 0),
        ];

        $objectives = $this->normalizeTextBlock($project->getObjectives());
        $prerequisites = $this->normalizeTextBlock($project->getPrerequisites());
        $outline = [];
        $exercises = [];
        $bibliography = [];
        $teachingMethods = [];

        $raw = [
            'meta' => $meta,
            'description' => (string)($project->getDescription() ?? ''),
            'skills' => $skills,
            'modules' => $modules,
            'evaluations' => $evaluations,
            'objectives' => $objectives,
            'prerequisites' => $prerequisites,
            'outline' => $outline,
            'exercises' => $exercises,
            'bibliography' => $bibliography,
            'teachingMethods' => $teachingMethods,
        ];

        return new ProjectSheet(
            fileCode: strtolower((string)$project->getCode()),
            path: '',
            meta: $meta,
            skills: $skills,
            skillsWithCriteria: [],
            modules: $modules,
            evaluations: $evaluations,
            objectives: $objectives,
            prerequisites: $prerequisites,
            outline: $outline,
            exercises: $exercises,
            bibliography: $bibliography,
            teachingMethods: $teachingMethods,
            raw: $raw,
        );
    }

    private function resolvePromotion(string $promotion, string $year): Promotion
    {
        $program = Program::tryFrom(strtolower(trim($promotion)));

        if (!$program instanceof Program) {
            throw new \RuntimeException(sprintf('Programme inconnu : "%s".', $promotion));
        }

        $candidates = $this->promotionRepository->findBy(['program' => $program]);

        foreach ($candidates as $candidate) {
            if (!$candidate instanceof Promotion) {
                continue;
            }

            if (strtoupper(trim((string)$candidate->getLabel())) === strtoupper($program->value . ' ' . $year)) {
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

    private function buildSkillKey(string $blocCode, string $skillCode): string
    {
        return strtolower(trim($blocCode) . '|' . trim($skillCode));
    }

    /**
     * @return array<string,mixed>
     */
    private function normalizeTextBlock(?string $value): array
    {
        $value = trim((string)$value);

        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        if (is_array($decoded)) {
            if (!isset($decoded['description'])) {
                $description = trim((string)($decoded['text'] ?? $decoded['summary'] ?? ''));
                if ($description !== '') {
                    $decoded['description'] = $description;
                }
            }

            return $decoded;
        }

        return [
            'description' => $value,
        ];
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
