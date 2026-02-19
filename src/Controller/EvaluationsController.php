<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EvaluationsController extends AbstractController
{
    #[Route('/evaluations', name: 'app_promotion_evaluations')]
    public function index(Request $request): Response
    {
        $promotion = $request->query->get('promotion');
        $year = $request->query->get('year');

        if (!$promotion || !$year) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('evaluations/index.html.twig', [
            'promotion' => $promotion,
            'year' => $year,
        ]);
    }
}
