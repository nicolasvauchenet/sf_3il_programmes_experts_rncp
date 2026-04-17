<?php

namespace App\Service\Context\Provider;

use App\Dto\Context\ModuleSheet;
use App\Dto\Context\ResolvedModuleSheet;
use App\Dto\Context\ResolvedSkillSheet;
use App\Entity\Chapter;
use App\Entity\Evaluation;
use App\Entity\Module;
use App\Entity\Project;
use App\Entity\Promotion;
use App\Entity\Skill;
use App\Enum\Program;
use App\Repository\PromotionRepository;
use App\Service\Context\Loader\FrameworkStructureLoader;
use App\Service\Context\ModuleSheetResolver;

final readonly class FrameworkModulesProvider
{
    public function __construct(
        private PromotionRepository      $promotionRepository,
        private FrameworkSkillsProvider  $skillsProvider,
        private FrameworkStructureLoader $structureLoader,
        private ModuleSheetResolver      $resolver,
    )
    {
    }

    /**
     * @return ResolvedModuleSheet[]
     */
    public function listModules(string $promotion, string $year): array
    {
        $promotionEntity = $this->resolvePromotion($promotion, $year);
        $structure = $this->structureLoader->load($promotion, $year);
        $resolvedSkills = $this->skillsProvider->listSkills($promotion, $year);
        $skillsIndex = $this->indexSkills($resolvedSkills);

        $modules = $promotionEntity->getModules()->toArray();

        usort(
            $modules,
            static fn(Module $a, Module $b): int => [
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

        foreach ($modules as $module) {
            if (!$module instanceof Module) {
                continue;
            }

            $sheet = $this->mapModuleToSheet($module, $year);
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

    private function mapModuleToSheet(Module $module, string $year): ModuleSheet
    {
        $skills = [];

        foreach ($module->getSkills() as $skill) {
            if (!$skill instanceof Skill) {
                continue;
            }

            $skills[] = [
                'code' => $this->extractShortCode((string)$skill->getCode()),
                'fileCode' => strtolower((string)$skill->getCode()),
                'description' => (string)($skill->getDescription() ?? $skill->getTitle() ?? ''),
            ];
        }

        $evaluations = [];

        foreach ($module->getEvaluations() as $evaluation) {
            if (!$evaluation instanceof Evaluation) {
                continue;
            }

            $evaluations[] = [
                'code' => strtolower((string)$evaluation->getCode()),
                'title' => (string)$evaluation->getTitle(),
                'blockCode' => (string)($evaluation->getBlock()?->getCode() ?? ''),
            ];
        }

        $projects = [];

        foreach ($module->getProjects() as $project) {
            if (!$project instanceof Project) {
                continue;
            }

            $projects[] = [
                'code' => strtolower((string)$project->getCode()),
                'title' => (string)$project->getTitle(),
                'blockCode' => (string)($project->getBlock()?->getCode() ?? ''),
            ];
        }

        $outlineChapters = [];
        foreach ($module->getChapters() as $chapter) {
            if (!$chapter instanceof Chapter) {
                continue;
            }

            $outlineChapters[] = [
                'title' => (string)$chapter->getTitle(),
                'items' => is_array($chapter->getItems()) ? $chapter->getItems() : [],
                'position' => (int)($chapter->getPosition() ?? 0),
            ];
        }

        usort(
            $outlineChapters,
            static fn(array $a, array $b): int => ($a['position'] ?? 0) <=> ($b['position'] ?? 0)
        );

        usort(
            $evaluations,
            static fn(array $a, array $b): int => [$a['blockCode'], $a['code']] <=> [$b['blockCode'], $b['code']]
        );

        usort(
            $projects,
            static fn(array $a, array $b): int => [$a['blockCode'], $a['code']] <=> [$b['blockCode'], $b['code']]
        );

        $meta = [
            'type' => 'module',
            'code' => (string)$module->getCode(),
            'title' => (string)$module->getTitle(),
            'academicYear' => $year,
            'blocCode' => (string)($module->getBlock()?->getCode() ?? ''),
            'blocName' => (string)($module->getBlock()?->getTitle() ?? ''),
            'durationDays' => (int)($module->getDurationDays() ?? 0),
            'durationHours' => (int)($module->getDurationHours() ?? 0),
        ];

        $objectives = $this->normalizeTextBlock($module->getObjectives());
        $prerequisites = $this->normalizeTextBlock($module->getPrerequisites());
        $outline = ['chapters' => $outlineChapters];
        $exercises = is_array($module->getExercises()) ? $module->getExercises() : [];
        $bibliography = is_array($module->getBibliography()) ? $module->getBibliography() : [];
        $onlineResources = $this->normalizeStringList($module->getOnlineResources());
        $teachingMethods = $this->normalizeStringList($module->getTeachingMethods());

        $raw = [
            'meta' => $meta,
            'description' => (string)($module->getDescription() ?? ''),
            'skills' => $skills,
            'evaluations' => $evaluations,
            'projects' => $projects,
            'objectives' => $objectives,
            'prerequisites' => $prerequisites,
            'outline' => $outline,
            'exercises' => $exercises,
            'bibliography' => $bibliography,
            'onlineResources' => $onlineResources,
            'teachingMethods' => $teachingMethods,
        ];

        return new ModuleSheet(
            fileCode: strtolower((string)$module->getCode()),
            path: '',
            meta: $meta,
            skills: $skills,
            skillsWithCriteria: [],
            evaluations: $evaluations,
            projects: $projects,
            objectives: $objectives,
            prerequisites: $prerequisites,
            outline: $outline,
            exercises: $exercises,
            bibliography: $bibliography,
            onlineResources: $onlineResources,
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
            return $decoded;
        }

        $items = preg_split('/\R+/', $value) ?: [];
        $items = array_values(array_filter(array_map(
            static fn(string $item): string => trim(ltrim($item, "-• \t")),
            $items
        )));

        return [
            'text' => $value,
            'items' => $items,
        ];
    }

    /**
     * @param mixed $value
     * @return array<int,string>
     */
    private function normalizeStringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $out = [];

        foreach ($value as $item) {
            if (!is_string($item)) {
                continue;
            }

            $item = trim($item);
            if ($item !== '') {
                $out[] = $item;
            }
        }

        return array_values(array_unique($out));
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
