<?php

namespace App\Controller;

use App\Service\Context\FrameworkSkillsProvider;
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

            return $this->redirectToRoute('app_promotion_summary', ['promotion' => $promotion, 'year' => $year]);
        }

        $rawSelected = (string)$request->query->get('code', '');
        $selectedFileCode = $this->normalizeSelectedCode($rawSelected, $skills);

        if ($selectedFileCode === null) {
            $selectedSkill = $skills[0];
            $selectedFileCode = $selectedSkill->fileCode;
        } else {
            $selectedSkill = null;
            foreach ($skills as $s) {
                if ($s->fileCode === $selectedFileCode) {
                    $selectedSkill = $s;
                    break;
                }
            }
            if ($selectedSkill === null) {
                $selectedSkill = $skills[0];
                $selectedFileCode = $selectedSkill->fileCode;
            }
        }

        return $this->render('skills/index.html.twig', [
            'promotion' => $promotion,
            'year' => $year,
            'skills' => $skills,
            'selectedCode' => $selectedFileCode,
            'skill' => $selectedSkill,
        ]);
    }

    private function normalizeSelectedCode(string $raw, array $skills): ?string
    {
        $raw = strtolower(trim($raw));

        if ($raw === '') {
            return null;
        }

        foreach ($skills as $s) {
            if (strtolower((string)$s->fileCode) === $raw) {
                return (string)$s->fileCode;
            }
        }

        return null;
    }
}
