<?php

namespace App\Controller;

use App\Service\Context\Provider\AvailableFrameworksProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        AvailableFrameworksProvider $provider
    ): Response
    {
        $contexts = $provider->listAvailable();

        $allPromotions = array_values(array_unique(array_map(
            static fn($c) => $c->promotionCode,
            $contexts
        )));

        $allYears = array_values(array_unique(array_map(
            static fn($c) => $c->academicYear,
            $contexts
        )));

        sort($allPromotions);
        rsort($allYears);

        return $this->render('home/index.html.twig', [
            'allPromotions' => $allPromotions,
            'allYears' => $allYears,
            'selectedPromotion' => null,
            'selectedYear' => null,
        ]);
    }
}
