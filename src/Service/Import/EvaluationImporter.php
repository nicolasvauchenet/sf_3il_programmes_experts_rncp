<?php

namespace App\Service\Import;

use App\Dto\Import\ImportReport;
use App\Entity\Block;
use App\Entity\Evaluation;
use App\Entity\EvaluationPart;
use App\Entity\Framework;
use App\Entity\Module;
use App\Entity\Project;
use App\Entity\Skill;
use Doctrine\ORM\EntityManagerInterface;

final readonly class EvaluationImporter
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RelationSyncService    $relationSyncService,
        private EnumResolver           $enumResolver,
        private CodeNormalizer         $codeNormalizer,
    )
    {
    }

    /**
     * @param array<string, mixed> $structure
     * @param array<string, array<string, mixed>> $evaluationFiles
     * @param array<string, Block> $blocks
     * @param array<string, Module> $modules
     * @param array<string, Project> $projects
     * @param array<string, Skill> $skills
     * @return array<string, Evaluation>
     */
    public function import(
        array        $structure,
        array        $evaluationFiles,
        Framework    $framework,
        array        $blocks,
        array        $modules,
        array        $projects,
        array        $skills,
        ImportReport $report,
    ): array
    {
        $result = [];

        foreach (($structure['evaluations'] ?? []) as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $code = $this->codeNormalizer->normalize((string)($row['code'] ?? ''));
            $blockCode = trim((string)($row['blockCode'] ?? $row['blocCode'] ?? ''));

            if ($code === '' || !isset($blocks[$blockCode])) {
                continue;
            }

            /** @var Evaluation|null $evaluation */
            $evaluation = $this->entityManager->getRepository(Evaluation::class)->findOneBy([
                'framework' => $framework,
                'code' => $code,
            ]);

            $isNew = !$evaluation instanceof Evaluation;

            if ($isNew) {
                $evaluation = new Evaluation();
                $evaluation->setFramework($framework);
                $evaluation->setCode($code);
                $this->entityManager->persist($evaluation);
                $report->markCreated('evaluations');
            } else {
                $report->markUpdated('evaluations');
            }

            $detail = $evaluationFiles[$code] ?? null;

            $evaluation->setBlock($blocks[$blockCode]);
            $evaluation->setTitle(trim((string)($detail['meta']['title'] ?? $row['title'] ?? $code)));
            $evaluation->setDescription(trim((string)($detail['description'] ?? '')) ?: null);
            $evaluation->setModalities($this->buildModalities($detail));
            $evaluation->setValidationRules($this->buildValidationRules($detail));
            $evaluation->setPosition($index + 1);

            $this->syncEvaluationParts($evaluation, $detail);

            $result[$code] = $evaluation;
        }

        if ($modules !== [] || $projects !== [] || $skills !== []) {
            $this->syncRelations($structure, $evaluationFiles, $result, $modules, $projects, $skills);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $structure
     * @param array<string, array<string, mixed>> $evaluationFiles
     * @param array<string, Evaluation> $evaluations
     * @param array<string, Module> $modules
     * @param array<string, Project> $projects
     * @param array<string, Skill> $skills
     */
    public function syncRelations(
        array $structure,
        array $evaluationFiles,
        array $evaluations,
        array $modules,
        array $projects,
        array $skills,
    ): void
    {
        foreach (($structure['evaluations'] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }

            $code = $this->codeNormalizer->normalize((string)($row['code'] ?? ''));

            if ($code === '' || !isset($evaluations[$code])) {
                continue;
            }

            $evaluation = $evaluations[$code];
            $detail = $evaluationFiles[$code] ?? null;
            $skillCodes = $this->extractDetailSkillCodes($detail);

            $this->syncModules($evaluation, (array)($row['modules'] ?? []), $modules);
            $this->syncProjects($evaluation, (array)($row['projects'] ?? []), $projects);
            $this->syncSkills($evaluation, $skillCodes !== [] ? $skillCodes : (array)($row['skills'] ?? []), $skills);
        }
    }

    /**
     * @param array<string, mixed>|null $detail
     * @return array<int, string>
     */
    private function extractDetailSkillCodes(?array $detail): array
    {
        if ($detail === null || !isset($detail['skills']) || !is_array($detail['skills'])) {
            return [];
        }

        $codes = [];

        foreach ($detail['skills'] as $skill) {
            if (!is_array($skill)) {
                continue;
            }

            $code = trim((string)($skill['code'] ?? ''));

            if ($code !== '') {
                $codes[] = $code;
            }
        }

        return array_values(array_unique($codes));
    }

    /**
     * @param array<string, mixed>|null $detail
     */
    private function syncEvaluationParts(Evaluation $evaluation, ?array $detail): void
    {
        $parts = $detail['exam']['parts'] ?? [];
        if (!is_array($parts)) {
            $parts = [];
        }

        foreach ($evaluation->getEvaluationParts()->toArray() as $part) {
            $evaluation->removeEvaluationPart($part);
            $this->entityManager->remove($part);
        }

        foreach ($parts as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $part = new EvaluationPart();
            $part->setEvaluation($evaluation);
            $part->setCode(trim((string)($row['code'] ?? sprintf('%s-P%d', (string)$evaluation->getCode(), $index + 1))));
            $part->setTitle(trim((string)($row['title'] ?? $part->getCode())));
            $part->setDescription(null);
            $part->setType($this->enumResolver->resolveEvaluationType(
                (string)($row['type'] ?? ''),
                (string)($row['title'] ?? '')
            ));
            $duration = $this->durationToMinutes($row['duration'] ?? null);
            $part->setDuration($duration);
            $part->setDurationLabel($duration === null ? $this->durationToLabel($row['duration'] ?? null) : null);
            $part->setPoints($this->toNullableInt($row['points'] ?? null));
            $part->setCoefficient($this->toNullableInt($row['coefficient'] ?? null));
            $part->setPosition($index + 1);

            $evaluation->addEvaluationPart($part);
            $this->entityManager->persist($part);
        }
    }

    /**
     * @param array<int, string> $moduleCodes
     * @param array<string, Module> $moduleIndex
     */
    private function syncModules(Evaluation $evaluation, array $moduleCodes, array $moduleIndex): void
    {
        $targets = [];

        foreach ($moduleCodes as $moduleCode) {
            $moduleCode = $this->codeNormalizer->normalize((string)$moduleCode);

            if (isset($moduleIndex[$moduleCode])) {
                $targets[] = $moduleIndex[$moduleCode];
            }
        }

        $this->relationSyncService->sync(
            currentCollection: $evaluation->getModules(),
            targetEntities: $targets,
            add: static fn(Module $module): Evaluation => $evaluation->addModule($module),
            remove: static fn(Module $module): Evaluation => $evaluation->removeModule($module),
        );
    }

    /**
     * @param array<int, string> $projectCodes
     * @param array<string, Project> $projectIndex
     */
    private function syncProjects(Evaluation $evaluation, array $projectCodes, array $projectIndex): void
    {
        $targets = [];

        foreach ($projectCodes as $projectCode) {
            $projectCode = $this->codeNormalizer->normalize((string)$projectCode);

            if (isset($projectIndex[$projectCode])) {
                $targets[] = $projectIndex[$projectCode];
            }
        }

        $this->relationSyncService->sync(
            currentCollection: $evaluation->getProjects(),
            targetEntities: $targets,
            add: static fn(Project $project): Evaluation => $evaluation->addProject($project),
            remove: static fn(Project $project): Evaluation => $evaluation->removeProject($project),
        );
    }

    /**
     * @param array<int, string> $skillCodes
     * @param array<string, Skill> $skillIndex
     */
    private function syncSkills(Evaluation $evaluation, array $skillCodes, array $skillIndex): void
    {
        $targets = [];
        $frameworkCode = (string)($evaluation->getFramework()?->getCode() ?? '');
        $blockCode = (string)($evaluation->getBlock()?->getCode() ?? '');

        foreach ($skillCodes as $skillCode) {
            $normalizedSkillCode = $this->codeNormalizer->normalizeSkillCode(
                $frameworkCode,
                $blockCode,
                (string)$skillCode
            );

            if ($normalizedSkillCode === '') {
                continue;
            }

            if (isset($skillIndex[$normalizedSkillCode])) {
                $targets[] = $skillIndex[$normalizedSkillCode];
            }
        }

        $this->relationSyncService->sync(
            currentCollection: $evaluation->getSkills(),
            targetEntities: $targets,
            add: static fn(Skill $skill): Evaluation => $evaluation->addSkill($skill),
            remove: static fn(Skill $skill): Evaluation => $evaluation->removeSkill($skill),
        );
    }

    /**
     * @param array<string, mixed>|null $detail
     * @return array<string, mixed>|null
     */
    private function buildModalities(?array $detail): ?array
    {
        if ($detail === null || !isset($detail['modalities']) || !is_array($detail['modalities'])) {
            return null;
        }

        $modalities = [
            'format' => $detail['modalities']['format'] ?? null,
            'delivery' => $detail['modalities']['delivery'] ?? null,
            'totalDuration' => $detail['modalities']['totalDuration'] ?? null,
        ];

        if (isset($detail['modalities']['deliverables']) && is_array($detail['modalities']['deliverables'])) {
            $modalities['deliverables'] = array_values(array_filter(
                array_map(
                    static fn(mixed $deliverable): string => trim((string)$deliverable),
                    $detail['modalities']['deliverables']
                ),
                static fn(string $deliverable): bool => $deliverable !== ''
            ));
        }

        return $modalities;
    }

    /**
     * @param array<string, mixed>|null $detail
     * @return array<string, mixed>|null
     */
    private function buildValidationRules(?array $detail): ?array
    {
        if ($detail === null || !isset($detail['exam']['validation']) || !is_array($detail['exam']['validation'])) {
            return null;
        }

        return $detail['exam']['validation'];
    }

    /**
     * @param mixed $value
     * @return int|null
     */
    private function durationToMinutes(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^(?:(\d+)\s*h)?\s*(?:(\d+)\s*(?:mn|min)?)?$/iu', $value, $matches)) {
            $hours = isset($matches[1]) ? (int)$matches[1] : 0;
            $minutes = isset($matches[2]) ? (int)$matches[2] : 0;

            return ($hours * 60) + $minutes;
        }

        return is_numeric($value) ? (int)$value : null;
    }

    private function durationToLabel(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string)$value);

        return $value !== '' ? $value : null;
    }

    /**
     * @param mixed $value
     * @return int|null
     */
    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int)$value;
    }
}
