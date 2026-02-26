<?php

namespace App\Controller;

use App\Service\Context\FrameworkStructureLoader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SummaryController extends AbstractController
{
    #[Route('/promotion', name: 'app_promotion_summary', methods: ['GET'])]
    public function index(
        Request                  $request,
        FrameworkStructureLoader $loader,
    ): Response
    {
        $promotion = (string)$request->query->get('promotion', '');
        $year = (string)$request->query->get('year', '');

        if ($promotion === '' || $year === '') {
            $this->addFlash('warning', "Les paramètres d'url sont invalides");

            return $this->redirectToRoute('app_home');
        }

        try {
            $structure = $loader->load($promotion, $year);
        } catch (\Throwable $error) {
            if ($this->getParameter('kernel.debug')) {
                $this->addFlash('warning', $error->getMessage());
            } else {
                $this->addFlash('warning', "La structure JSON est invalide");
            }

            return $this->redirectToRoute('app_home');
        }

        return $this->render('summary/index.html.twig', [
            'promotion' => $promotion,
            'year' => $year,
            'structure' => $structure,
        ]);
    }
}
