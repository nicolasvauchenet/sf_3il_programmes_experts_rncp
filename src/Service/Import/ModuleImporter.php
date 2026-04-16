<?php

namespace App\Service\Import;

use App\Dto\Import\ImportReport;
use App\Entity\Block;
use App\Entity\Chapter;
use App\Entity\Evaluation;
use App\Entity\Module;
use App\Entity\Promotion;
use App\Entity\Skill;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ModuleImporter
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
     * @param array<string, array<string, mixed>> $moduleFiles
     * @param array<string, Block> $blocks
     * @param array<string, Skill> $skills
     * @param array<string, Evaluation> $evaluations
     * @return array<string, Module>
     */
    public function import(
        array        $structure,
        array        $moduleFiles,
        Promotion    $promotion,
        array        $blocks,
        array        $skills,
        array        $evaluations,
        ImportReport $report,
    ): array
    {
        $result = [];
        $frameworkCode = (string)($promotion->getFramework()?->getCode() ?? '');

        foreach (($structure['modules'] ?? []) as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $code = $this->codeNormalizer->normalize((string)($row['code'] ?? ''));
            $blockCode = $this->codeNormalizer->normalizeBlockCode((string)($row['blockCode'] ?? $row['blocCode'] ?? ''));

            if ($code === '' || $blockCode === '' || !isset($blocks[$blockCode])) {
                continue;
            }

            /** @var Module|null $module */
            $module = $this->entityManager->getRepository(Module::class)->findOneBy([
                'promotion' => $promotion,
                'code' => $code,
            ]);

            $isNew = !$module instanceof Module;

            if ($isNew) {
                $module = new Module();
                $module->setPromotion($promotion);
                $module->setCode($code);
                $this->entityManager->persist($module);
                $report->markCreated('modules');
            } else {
                $report->markUpdated('modules');
            }

            $detail = $moduleFiles[$code] ?? null;

            $module->setBlock($blocks[$blockCode]);
            $module->setTitle(trim((string)($row['title'] ?? $detail['meta']['title'] ?? $code)));
            $module->setDescription(null);
            $module->setDurationDays($this->toNullableInt($detail['meta']['durationDays'] ?? null));
            $module->setDurationHours($this->toNullableInt($detail['meta']['durationHours'] ?? null));
            $module->setObjectives($this->extractNestedString($detail, ['objectives', 'description']));
            $module->setPrerequisites($this->extractNestedString($detail, ['prerequisites', 'description']));
            $module->setExercises($this->nullableArray($detail['exercises'] ?? null));
            $module->setBibliography($this->nullableArray($detail['bibliography'] ?? null));
            $module->setOnlineResources($this->nullableArray($detail['onlineResources'] ?? null));
            $module->setTeachingMethods($this->nullableArray($detail['teachingMethods'] ?? null));
            $module->setPosition($index + 1);

            $this->syncSkills($module, (array)($row['skills'] ?? []), $skills, $frameworkCode, $blockCode);
            $this->syncEvaluations($module, (array)($row['evaluations'] ?? []), $evaluations);
            $this->syncChapters($module, $detail);

            $result[$code] = $module;
        }

        return $result;
    }

    /**
     * @param array<int, string> $skillCodes
     * @param array<string, Skill> $skillIndex
     */
    private function syncSkills(
        Module $module,
        array  $skillCodes,
        array  $skillIndex,
        string $frameworkCode,
        string $blockCode,
    ): void
    {
        $targets = [];

        foreach ($skillCodes as $skillCode) {
            $normalizedSkillCode = $this->codeNormalizer->normalizeSkillCode(
                $frameworkCode,
                $blockCode,
                (string)$skillCode,
            );

            if ($normalizedSkillCode !== '' && isset($skillIndex[$normalizedSkillCode])) {
                $targets[] = $skillIndex[$normalizedSkillCode];
            }
        }

        $this->relationSyncService->sync(
            currentCollection: $module->getSkills(),
            targetEntities: $targets,
            add: static fn(Skill $skill): Module => $module->addSkill($skill),
            remove: static fn(Skill $skill): Module => $module->removeSkill($skill),
        );
    }

    /**
     * @param array<int, string> $evaluationCodes
     * @param array<string, Evaluation> $evaluationIndex
     */
    private function syncEvaluations(Module $module, array $evaluationCodes, array $evaluationIndex): void
    {
        $targets = [];

        foreach ($evaluationCodes as $evaluationCode) {
            $evaluationCode = $this->codeNormalizer->normalize((string)$evaluationCode);

            if (isset($evaluationIndex[$evaluationCode])) {
                $targets[] = $evaluationIndex[$evaluationCode];
            }
        }

        $this->relationSyncService->sync(
            currentCollection: $module->getEvaluations(),
            targetEntities: $targets,
            add: static fn(Evaluation $evaluation): Module => $module->addEvaluation($evaluation),
            remove: static fn(Evaluation $evaluation): Module => $module->removeEvaluation($evaluation),
        );
    }

    /**
     * @param array<string, mixed>|null $detail
     */
    private function syncChapters(Module $module, ?array $detail): void
    {
        $chapters = $detail['outline']['chapters'] ?? [];
        if (!is_array($chapters)) {
            $chapters = [];
        }

        foreach ($module->getChapters()->toArray() as $chapter) {
            $module->removeChapter($chapter);
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
            $chapter->setModule($module);
            $chapter->setProject(null);
            $chapter->setTitle($title);
            $chapter->setItems($this->normalizeStringArray($row['items'] ?? []));
            $chapter->setPosition($index + 1);

            $module->addChapter($chapter);
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
     * @return array<int, mixed>|null
     */
    private function nullableArray(mixed $value): ?array
    {
        return is_array($value) ? $value : null;
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
