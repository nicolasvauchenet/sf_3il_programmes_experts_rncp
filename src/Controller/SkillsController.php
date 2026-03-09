<?php

namespace App\Controller;

use App\Dto\Context\ResolvedSkillSheet;
use App\Service\Chart\SkillChartService;
use App\Service\Context\FrameworkSkillsProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SkillsController extends AbstractController
{
    #[Route('/competences', name: 'app_promotion_skills', methods: ['GET'])]
    public function index(
        Request $request,
        FrameworkSkillsProvider $skillsProvider,
        SkillChartService $skillChartService,
    ): Response {
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
        $selectedFileCode = $this->normalizeSelectedCode($rawSelected, $skills);

        if ($selectedFileCode === null) {
            $selectedSkill = $skills[0];
            $selectedFileCode = $selectedSkill->fileCode;
        } else {
            $selectedSkill = $this->findSelectedSkill($skills, $selectedFileCode);

            if ($selectedSkill === null) {
                $selectedSkill = $skills[0];
                $selectedFileCode = $selectedSkill->fileCode;
            }
        }

        $skillMetricsChart = $skillChartService->createSkillMetricsChart($selectedSkill);

        return $this->render('skills/index.html.twig', [
            'promotion' => $promotion,
            'year' => $year,
            'skills' => $skills,
            'selectedCode' => $selectedFileCode,
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
            if (strtolower($skill->fileCode) === $raw) {
                return $skill->fileCode;
            }
        }

        return null;
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
}
