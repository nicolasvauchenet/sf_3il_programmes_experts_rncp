<?php

namespace App\Service\Context\Loader;

use App\Dto\Context\FrameworkStructure;
use App\Entity\Block;
use App\Entity\Evaluation;
use App\Entity\Framework;
use App\Entity\Module;
use App\Entity\Project;
use App\Entity\Promotion;
use App\Entity\Skill;
use App\Enum\Program;
use App\Repository\PromotionRepository;

final readonly class FrameworkStructureLoader
{
    public function __construct(
        private PromotionRepository $promotionRepository,
    )
    {
    }

    public function load(string $promotion, string $year): FrameworkStructure
    {
        $promotionEntity = $this->resolvePromotion($promotion, $year);
        $framework = $promotionEntity->getFramework();

        if (!$framework instanceof Framework) {
            throw new \RuntimeException('Framework introuvable pour la promotion demandée.');
        }

        $modules = $this->buildModules($promotionEntity);
        $projects = $this->buildProjects($promotionEntity);
        $skills = $this->buildSkills($framework);
        $evaluations = $this->buildEvaluations($framework);
        $blocks = $this->buildBlocks($framework);

        $meta = [
            'datasetCode' => strtolower($promotion) . '_' . $year,
            'rncpCode' => $framework->getCode(),
            'programCode' => strtolower($promotionEntity->getProgram()?->value ?? $promotion),
            'programTitle' => $promotionEntity->getProgram()?->label() ?? $framework->getTitle() ?? '',
            'certificationName' => $framework->getTitle() ?? '',
            'academicYear' => $year,
        ];

        $raw = [
            'meta' => $meta,
            'modules' => $modules,
            'projects' => $projects,
            'skills' => $skills,
            'evaluations' => $evaluations,
            'blocks' => $blocks,
        ];

        return new FrameworkStructure(
            meta: $meta,
            modules: $modules,
            skills: $skills,
            blocks: $blocks,
            evaluations: $evaluations,
            projects: $projects,
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

        if ($candidates === []) {
            throw new \RuntimeException(sprintf('Aucune promotion trouvée pour le programme "%s".', $promotion));
        }

        $expectedLabel = strtoupper($program->value) . ' ' . $year;

        foreach ($candidates as $candidate) {
            if (!$candidate instanceof Promotion) {
                continue;
            }

            $label = strtoupper(trim((string)$candidate->getLabel()));
            if ($label === $expectedLabel) {
                return $candidate;
            }
        }

        foreach ($candidates as $candidate) {
            if (!$candidate instanceof Promotion) {
                continue;
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

        foreach ($candidates as $candidate) {
            if (!$candidate instanceof Promotion) {
                continue;
            }

            if (str_contains(strtoupper((string)$candidate->getLabel()), strtoupper($year))) {
                return $candidate;
            }
        }

        throw new \RuntimeException(sprintf(
            'Impossible de résoudre la promotion "%s" pour l’année "%s".',
            $promotion,
            $year
        ));
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function buildBlocks(Framework $framework): array
    {
        $blocks = $framework->getBlocks()->toArray();

        usort(
            $blocks,
            static fn(Block $a, Block $b): int => ($a->getPosition() ?? 0) <=> ($b->getPosition() ?? 0)
        );

        return array_map(
            static fn(Block $block): array => [
                'code' => (string)$block->getCode(),
                'title' => (string)$block->getTitle(),
                'description' => (string)($block->getDescription() ?? ''),
                'position' => (int)($block->getPosition() ?? 0),
            ],
            $blocks
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function buildModules(Promotion $promotion): array
    {
        $modules = $promotion->getModules()->toArray();

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

        return array_map(function (Module $module): array {
            $blockCode = (string)($module->getBlock()?->getCode() ?? '');
            $moduleCode = (string)$module->getCode();

            return [
                'code' => $this->extractShortCode($moduleCode),
                'fullCode' => $moduleCode,
                'title' => (string)$module->getTitle(),
                'blockCode' => $blockCode,
                'blockName' => (string)($module->getBlock()?->getTitle() ?? ''),
                'position' => (int)($module->getPosition() ?? 0),
            ];
        }, $modules);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function buildProjects(Promotion $promotion): array
    {
        $projects = $promotion->getProjects()->toArray();

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

        return array_map(function (Project $project): array {
            $evaluations = [];

            foreach ($project->getEvaluations() as $evaluation) {
                if (!$evaluation instanceof Evaluation) {
                    continue;
                }

                $evaluations[] = (string)$evaluation->getCode();
            }

            $evaluations = array_values(array_unique(array_filter($evaluations)));

            return [
                'code' => (string)$project->getCode(),
                'title' => (string)$project->getTitle(),
                'blockCode' => (string)($project->getBlock()?->getCode() ?? ''),
                'blockName' => (string)($project->getBlock()?->getTitle() ?? ''),
                'position' => (int)($project->getPosition() ?? 0),
                'evaluations' => $evaluations,
            ];
        }, $projects);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function buildSkills(Framework $framework): array
    {
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

        return array_map(function (Skill $skill): array {
            $modules = [];
            foreach ($skill->getModules() as $module) {
                if (!$module instanceof Module) {
                    continue;
                }

                $modules[] = (string)$module->getCode();
            }

            $evaluations = [];
            foreach ($skill->getEvaluations() as $evaluation) {
                if (!$evaluation instanceof Evaluation) {
                    continue;
                }

                $evaluations[] = (string)$evaluation->getCode();
            }

            return [
                'code' => $this->extractShortCode((string)$skill->getCode()),
                'fullCode' => (string)$skill->getCode(),
                'title' => (string)$skill->getTitle(),
                'blockCode' => (string)($skill->getBlock()?->getCode() ?? ''),
                'blockName' => (string)($skill->getBlock()?->getTitle() ?? ''),
                'description' => (string)($skill->getDescription() ?? ''),
                'position' => (int)($skill->getPosition() ?? 0),
                'modules' => array_values(array_unique(array_filter($modules))),
                'evaluations' => array_values(array_unique(array_filter($evaluations))),
            ];
        }, $skills);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function buildEvaluations(Framework $framework): array
    {
        $evaluations = $framework->getEvaluations()->toArray();

        usort(
            $evaluations,
            static fn(Evaluation $a, Evaluation $b): int => ($a->getPosition() ?? 0) <=> ($b->getPosition() ?? 0)
        );

        return array_map(function (Evaluation $evaluation): array {
            $modules = [];

            foreach ($evaluation->getModules() as $module) {
                if (!$module instanceof Module) {
                    continue;
                }

                $modules[] = (string)$module->getCode();
            }

            $skills = [];

            foreach ($evaluation->getSkills() as $skill) {
                if (!$skill instanceof Skill) {
                    continue;
                }

                $skills[] = (string)$skill->getCode();
            }

            $fullCode = (string)$evaluation->getCode();

            return [
                'shortCode' => $this->extractShortCode($fullCode),
                'code' => $fullCode,
                'fullCode' => $fullCode,
                'title' => (string)$evaluation->getTitle(),
                'blockCode' => (string)($evaluation->getBlock()?->getCode() ?? ''),
                'blockName' => (string)($evaluation->getBlock()?->getTitle() ?? ''),
                'position' => (int)($evaluation->getPosition() ?? 0),
                'modules' => array_values(array_unique(array_filter($modules))),
                'skills' => array_values(array_unique(array_filter($skills))),
            ];
        }, $evaluations);
    }

    private function extractShortCode(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $parts = explode('-', $value);

        return trim((string)end($parts));
    }
}
