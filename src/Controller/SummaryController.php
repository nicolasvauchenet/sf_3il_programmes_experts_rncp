<?php

namespace App\Controller;

use App\Service\Chart\EvaluationsVsSkillsChartBuilder;
use App\Service\Chart\ModulesBySemesterChartBuilder;
use App\Service\Framework\FrameworkJsonLoader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SummaryController extends AbstractController
{
    #[Route('/promotion', name: 'app_promotion_summary')]
    public function index(
        Request $request,
        FrameworkJsonLoader $loader,
        EvaluationsVsSkillsChartBuilder $evaluationsVsSkillsChartBuilder,
        ModulesBySemesterChartBuilder $modulesBySemesterChartBuilder,
    ): Response {
        $promotion = $request->query->get('promotion');
        $year = $request->query->get('year');

        if (!$promotion || !$year) {
            return $this->redirectToRoute('app_home');
        }

        $framework = $loader->load($promotion, $year);
        $evaluationsVsSkillsChart = $evaluationsVsSkillsChartBuilder->build($framework);
        $modulesBySemesterChart = $modulesBySemesterChartBuilder->build($framework);

        return $this->render('summary/index.html.twig', [
            'promotion' => $promotion,
            'year' => $year,
            'framework' => $framework,
            'evaluationsVsSkillsChart' => $evaluationsVsSkillsChart,
            'modulesBySemesterChart' => $modulesBySemesterChart,
        ]);
    }
}
