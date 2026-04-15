<?php

namespace App\Service\Import;

use App\Dto\Import\ImportReport;
use App\Entity\Block;
use App\Entity\Chapter;
use App\Entity\Evaluation;
use App\Entity\Module;
use App\Entity\Project;
use App\Entity\Promotion;
use App\Entity\Skill;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ProjectImporter
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RelationSyncService    $relationSyncService,
        private CodeNormalizer         $codeNormalizer,
    )
    {
    }

    /**
     * @param array<string, mixed> $structure
     * @param array<string, array<string, mixed>> $projectFiles
     * @param array<string, Block> $blocks
     * @param array<string, Module> $modules
     * @param array<string, Skill> $skills
     * @param array<string, Evaluation> $evaluations
     * @return array<string, Project>
     */
    public function import(
        array        $structure,
        array        $projectFiles,
        Promotion    $promotion,
        array        $blocks,
        array        $modules,
        array        $skills,
        array        $evaluations,
        ImportReport $report,
    ): array
    {
        $result = [];

        foreach (($structure['projects'] ?? []) as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $code = $this->codeNormalizer->normalize((string)($row['code'] ?? ''));
            $blockCode = trim((string)($row['blockCode'] ?? $row['blocCode'] ?? ''));

            if ($code === '' || !isset($blocks[$blockCode])) {
                continue;
            }

            /** @var Project|null $project */
            $project = $this->entityManager->getRepository(Project::class)->findOneBy([
                'promotion' => $promotion,
                'code' => $code,
            ]);

            $isNew = !$project instanceof Project;

            if ($isNew) {
                $project = new Project();
                $project->setPromotion($promotion);
                $project->setCode($code);
                $this->entityManager->persist($project);
                $report->markCreated('projects');
            } else {
                $report->markUpdated('projects');
            }

            $detail = $projectFiles[$code] ?? null;

            $project->setBlock($blocks[$blockCode]);
            $project->setTitle(trim((string)($row['title'] ?? $detail['meta']['title'] ?? $code)));
            $project->setDescription(null);
            $project->setDurationDays($this->toNullableInt($detail['meta']['durationDays'] ?? null));
            $project->setDurationHours($this->toNullableInt($detail['meta']['durationHours'] ?? null));
            $project->setObjectives($this->extractNestedString($detail, ['objectives', 'description']));
            $project->setPrerequisites($this->extractNestedString($detail, ['prerequisites', 'description']));
            $project->setPosition($index + 1);

            $this->syncModules($project, (array)($row['modules'] ?? []), $modules);
            $this->syncSkills($project, (array)($row['skills'] ?? []), $skills);
            $this->syncEvaluations($project, (array)($row['evaluations'] ?? []), $evaluations);
            $this->syncChapters($project, $detail);

            $result[$code] = $project;
        }

        return $result;
    }

    /**
     * @param array<int, string> $moduleCodes
     * @param array<string, Module> $moduleIndex
     */
    private function syncModules(Project $project, array $moduleCodes, array $moduleIndex): void
    {
        $targets = [];

        foreach ($moduleCodes as $moduleCode) {
            $moduleCode = $this->codeNormalizer->normalize((string)$moduleCode);

            if (isset($moduleIndex[$moduleCode])) {
                $targets[] = $moduleIndex[$moduleCode];
            }
        }

        $this->relationSyncService->sync(
            currentCollection: $project->getModules(),
            targetEntities: $targets,
            add: static fn(Module $module): Project => $project->addModule($module),
            remove: static fn(Module $module): Project => $project->removeModule($module),
        );
    }

    /**
     * @param array<int, string> $skillCodes
     * @param array<string, Skill> $skillIndex
     */
    private function syncSkills(Project $project, array $skillCodes, array $skillIndex): void
    {
        $targets = [];

        foreach ($skillCodes as $skillCode) {
            $skillCode = trim((string)$skillCode);

            if (isset($skillIndex[$skillCode])) {
                $targets[] = $skillIndex[$skillCode];
            }
        }

        $this->relationSyncService->sync(
            currentCollection: $project->getSkills(),
            targetEntities: $targets,
            add: static fn(Skill $skill): Project => $project->addSkill($skill),
            remove: static fn(Skill $skill): Project => $project->removeSkill($skill),
        );
    }

    /**
     * @param array<int, string> $evaluationCodes
     * @param array<string, Evaluation> $evaluationIndex
     */
    private function syncEvaluations(Project $project, array $evaluationCodes, array $evaluationIndex): void
    {
        $targets = [];

        foreach ($evaluationCodes as $evaluationCode) {
            $evaluationCode = $this->codeNormalizer->normalize((string)$evaluationCode);

            if (isset($evaluationIndex[$evaluationCode])) {
                $targets[] = $evaluationIndex[$evaluationCode];
            }
        }

        $this->relationSyncService->sync(
            currentCollection: $project->getEvaluations(),
            targetEntities: $targets,
            add: static fn(Evaluation $evaluation): Project => $project->addEvaluation($evaluation),
            remove: static fn(Evaluation $evaluation): Project => $project->removeEvaluation($evaluation),
        );
    }

    /**
     * @param array<string, mixed>|null $detail
     */
    private function syncChapters(Project $project, ?array $detail): void
    {
        $chapters = $detail['outline']['chapters'] ?? [];
        if (!is_array($chapters)) {
            $chapters = [];
        }

        foreach ($project->getChapters()->toArray() as $chapter) {
            $project->removeChapter($chapter);
            $this->entityManager->remove($chapter);
        }

        foreach ($chapters as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $title = trim((string)($row['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $chapter = new Chapter();
            $chapter->setProject($project);
            $chapter->setModule(null);
            $chapter->setTitle($title);
            $chapter->setItems($this->normalizeStringArray($row['items'] ?? []));
            $chapter->setPosition($index + 1);

            $project->addChapter($chapter);
            $this->entityManager->persist($chapter);
        }
    }

    /**
     * @param array<string, mixed>|null $detail
     * @param array<int, string> $path
     */
    private function extractNestedString(?array $detail, array $path): ?string
    {
        if ($detail === null) {
            return null;
        }

        $current = $detail;

        foreach ($path as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return null;
            }

            $current = $current[$key];
        }

        $value = trim((string)$current);

        return $value !== '' ? $value : null;
    }

    /**
     * @param mixed $value
     */
    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int)$value;
    }

    /**
     * @param mixed $value
     * @return array<int, string>
     */
    private function normalizeStringArray(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $result = [];

        foreach ($value as $item) {
            $item = trim((string)$item);

            if ($item !== '') {
                $result[] = $item;
            }
        }

        return $result;
    }
}
