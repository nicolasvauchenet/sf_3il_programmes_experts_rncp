<?php

namespace App\Controller;

use App\Service\Chart\SummaryChartService;
use App\Service\Context\Loader\FrameworkStructureLoader;
use App\Service\Context\Provider\FrameworkEvaluationsProvider;
use App\Service\Context\Provider\FrameworkProjectsProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SummaryController extends AbstractController
{
    #[Route('/promotion', name: 'app_promotion_summary', methods: ['GET'])]
    public function index(
        Request                      $request,
        FrameworkStructureLoader     $loader,
        SummaryChartService          $summaryChartService,
        FrameworkProjectsProvider    $projectsProvider,
        FrameworkEvaluationsProvider $evaluationsProvider,
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
            $evaluationSheets = $evaluationsProvider->listEvaluations($promotion, $year);
        } catch (\Throwable $error) {
            if ($this->getParameter('kernel.debug')) {
                $this->addFlash('warning', $error->getMessage());
            } else {
                $this->addFlash('warning', "La structure JSON est invalide");
            }

            return $this->redirectToRoute('app_home');
        }

        $projects = $projectsProvider->listProjects($promotion, $year);

        return $this->render('summary/index.html.twig', [
            'promotion' => $promotion,
            'year' => $year,
            'structure' => $structure,
            'referentialVolumeChart' => $summaryChartService->createReferentialVolumeChart($structure, count($projects)),
            'skillsPerBlockChart' => $summaryChartService->createSkillsPerBlockChart($structure),
            'evaluationsPerBlockChart' => $summaryChartService->createEvaluationsPerBlockChart($structure),
            'modulesPerBlockChart' => $summaryChartService->createModulesPerBlockChart($structure),
            'projectsPerBlockChart' => $summaryChartService->createProjectsPerBlockChart($projects),
            'skillsPerEvaluationChart' => $summaryChartService->createSkillsPerEvaluationChartFromSheets($evaluationSheets),
            'modulesPerEvaluationChart' => $summaryChartService->createModulesPerEvaluationChartFromSheets($evaluationSheets),
        ]);
    }
}
