<?php

namespace App\Controller;

use App\Service\Framework\FrameworkJsonLoader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SkillsController extends AbstractController
{
    #[Route('/competences', name: 'app_promotion_skills')]
    public function index(
        Request $request,
        FrameworkJsonLoader $loader
    ): Response {
        $promotion = $request->query->get('promotion');
        $year = $request->query->get('year');

        if (!$promotion || !$year) {
            return $this->redirectToRoute('app_home');
        }

        $framework = $loader->load($promotion, $year);

        return $this->render('skills/index.html.twig', [
            'promotion' => $promotion,
            'year' => $year,
            'framework' => $framework,
        ]);
    }
}
