<?php

namespace App\Controller;

use App\Service\Framework\DatasetCatalog;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(Request $request, DatasetCatalog $catalog): Response
    {
        $available = $catalog->listAvailable();

        $selectedPromotion = strtolower((string)$request->query->get('promotion', ''));
        $selectedYear = (string)$request->query->get('year', '');

        $allYears = [];
        foreach ($available['yearsByPromotion'] as $years) {
            foreach ($years as $y) {
                $allYears[$y] = true;
            }
        }

        $allYears = array_keys($allYears);
        rsort($allYears);

        return $this->render('home/index.html.twig', [
            'promotions' => $available['promotions'],
            'yearsByPromotion' => $available['yearsByPromotion'],
            'allYears' => $allYears,
            'selectedPromotion' => $selectedPromotion,
            'selectedYear' => $selectedYear,
        ]);
    }
}
