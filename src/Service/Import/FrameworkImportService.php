<?php

namespace App\Service\Import;

use App\Dto\Import\ImportReport;
use App\Enum\ImportMode;
use App\Enum\ImportStrategy;
use Doctrine\ORM\EntityManagerInterface;

final readonly class FrameworkImportService
{
    public function __construct(
        private EntityManagerInterface   $entityManager,
        private FrameworkContextImporter $frameworkContextImporter,
        private FrameworkContextResolver $frameworkContextResolver,
        private BlockImporter            $blockImporter,
        private BlockIndexProvider       $blockIndexProvider,
        private SkillImporter            $skillImporter,
        private EvaluationImporter       $evaluationImporter,
        private SkillIndexProvider       $skillIndexProvider,
        private EvaluationIndexProvider  $evaluationIndexProvider,
        private ModuleImporter           $moduleImporter,
        private ProjectImporter          $projectImporter,
    )
    {
    }

    /**
     * @param array{
     *     structure: array<string, mixed>,
     *     modules: array<string, array<string, mixed>>,
     *     projects: array<string, array<string, mixed>>,
     *     skills: array<string, array<string, mixed>>,
     *     evaluations: array<string, array<string, mixed>>
     * } $dataset
     */
    public function import(array $dataset, ImportMode $mode = ImportMode::FULL): ImportReport
    {
        $report = new ImportReport();

        $this->entityManager->wrapInTransaction(function () use ($dataset, $mode, $report): void {
            $structure = $dataset['structure'];

            if ($mode === ImportMode::FULL) {
                $context = $this->frameworkContextImporter->import($structure, $report);

                $blocks = $this->blockImporter->import(
                    structure: $structure,
                    framework: $context->framework,
                    report: $report,
                );

                $skills = $this->skillImporter->import(
                    structure: $structure,
                    skillFiles: $dataset['skills'],
                    framework: $context->framework,
                    blocks: $blocks,
                    report: $report,
                );

                $evaluations = $this->evaluationImporter->import(
                    structure: $structure,
                    evaluationFiles: $dataset['evaluations'],
                    framework: $context->framework,
                    blocks: $blocks,
                    modules: [],
                    projects: [],
                    skills: $skills,
                    report: $report,
                );
            } else {
                $context = $this->frameworkContextResolver->resolve($structure, $report);

                $blocks = $this->blockIndexProvider->getByFramework($context->framework);
                $skills = $this->skillIndexProvider->getByFramework($context->framework);
                $evaluations = $this->evaluationIndexProvider->getByFramework($context->framework);
            }

            $modules = $this->moduleImporter->import(
                structure: $structure,
                moduleFiles: $dataset['modules'],
                promotion: $context->promotion,
                blocks: $blocks,
                skills: $skills,
                evaluations: $evaluations,
                report: $report,
            );

            $projects = $this->projectImporter->import(
                structure: $structure,
                projectFiles: $dataset['projects'],
                promotion: $context->promotion,
                blocks: $blocks,
                modules: $modules,
                skills: $skills,
                evaluations: $evaluations,
                report: $report,
            );

            if ($mode === ImportMode::FULL) {
                $this->evaluationImporter->syncRelations(
                    structure: $structure,
                    evaluations: $evaluations,
                    modules: $modules,
                    projects: $projects,
                    skills: $skills,
                );
            }

            $this->entityManager->flush();
        });

        return $report;
    }

    /**
     * @param array{
     *     structure: array<string, mixed>,
     *     modules: array<string, array<string, mixed>>,
     *     projects: array<string, array<string, mixed>>,
     *     skills: array<string, array<string, mixed>>,
     *     evaluations: array<string, array<string, mixed>>
     * } $dataset
     */
    public function importWithStrategy(array $dataset, ImportStrategy $strategy): ImportReport
    {
        return match ($strategy) {
            ImportStrategy::CREATE_FULL,
            ImportStrategy::UPDATE_FULL => $this->import($dataset, ImportMode::FULL),

            ImportStrategy::CREATE_PROMOTION,
            ImportStrategy::UPDATE_PROMOTION => $this->import($dataset, ImportMode::MODULES_PROJECTS),
        };
    }
}
