<?php

namespace App\Controller;

use App\Dto\Context\ResolvedSkillSheet;
use App\Service\Chart\SkillChartService;
use App\Service\Context\Provider\FrameworkSkillsProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SkillsController extends AbstractController
{
    #[Route('/competences', name: 'app_promotion_skills', methods: ['GET'])]
    public function index(
        Request                 $request,
        FrameworkSkillsProvider $skillsProvider,
        SkillChartService       $skillChartService,
    ): Response
    {
        $promotion = (string)$request->query->get('promotion', '');
        $year = (string)$request->query->get('year', '');

        if ($promotion === '' || $year === '') {
            return $this->redirectToRoute('app_home');
        }

        $skills = $skillsProvider->listSkills($promotion, $year);

        if ($skills === []) {
            $this->addFlash('warning', 'Aucune compétence disponible pour ce référentiel.');

            return $this->redirectToRoute('app_promotion_summary', [
                'promotion' => $promotion,
                'year' => $year,
            ]);
        }

        $rawSelected = (string)$request->query->get('code', '');
        $rawSelectedBlock = (string)$request->query->get('block', '');

        $selectedFileCode = $this->normalizeSelectedCode($rawSelected, $skills);
        $selectedBlock = $this->normalizeBlockCode($rawSelectedBlock);

        if ($selectedFileCode === null) {
            $selectedSkill = $this->findFirstSkillForBlock($skills, $selectedBlock) ?? $skills[0];
            $selectedFileCode = $selectedSkill->fileCode;
        } else {
            $selectedSkill = $this->findSelectedSkill($skills, $selectedFileCode);

            if ($selectedSkill === null) {
                $selectedSkill = $this->findFirstSkillForBlock($skills, $selectedBlock) ?? $skills[0];
                $selectedFileCode = $selectedSkill->fileCode;
            }
        }

        $skillMetricsChart = $skillChartService->createSkillMetricsChart($selectedSkill);

        return $this->render('skills/index.html.twig', [
            'promotion' => $promotion,
            'year' => $year,
            'skills' => $skills,
            'selectedCode' => $selectedFileCode,
            'selectedBlock' => $selectedBlock,
            'skill' => $selectedSkill,
            'skillMetricsChart' => $skillMetricsChart,
        ]);
    }

    /**
     * @param ResolvedSkillSheet[] $skills
     */
    private function normalizeSelectedCode(string $raw, array $skills): ?string
    {
        $raw = strtolower(trim($raw));

        if ($raw === '') {
            return null;
        }

        foreach ($skills as $skill) {
            if (strtolower((string)$skill->fileCode) === $raw) {
                return (string)$skill->fileCode;
            }
        }

        return null;
    }

    private function normalizeBlockCode(string $raw): ?string
    {
        $raw = strtoupper(trim($raw));

        if ($raw === '') {
            return null;
        }

        return $raw;
    }

    /**
     * @param ResolvedSkillSheet[] $skills
     */
    private function findSelectedSkill(array $skills, string $selectedFileCode): ?ResolvedSkillSheet
    {
        foreach ($skills as $skill) {
            if ($skill->fileCode === $selectedFileCode) {
                return $skill;
            }
        }

        return null;
    }

    /**
     * @param ResolvedSkillSheet[] $skills
     */
    private function findFirstSkillForBlock(array $skills, ?string $blockCode): ?ResolvedSkillSheet
    {
        if ($blockCode === null) {
            return null;
        }

        foreach ($skills as $skill) {
            $skillBlockCode = strtoupper(trim((string)($skill->meta['blocCode'] ?? '')));

            if ($skillBlockCode === $blockCode) {
                return $skill;
            }
        }

        return null;
    }
}
