<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ModulesController extends AbstractController
{
    #[Route('/matieres', name: 'app_promotion_courses')]
    public function index(
        Request $request,
    ): Response
    {
        $promotion = (string)$request->query->get('promotion', '');
        $year = (string)$request->query->get('year', '');
        $selectedCode = strtolower((string)$request->query->get('code', ''));

        if ($promotion === '' || $year === '') {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('modules/index.html.twig', [
            'promotion' => $promotion,
            'year' => $year,
            'selectedCode' => $selectedCode,
        ]);
    }
}
