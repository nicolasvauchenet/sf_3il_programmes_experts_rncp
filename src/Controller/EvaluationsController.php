<?php

namespace App\Controller;

use App\Service\Chart\EvaluationChartService;
use App\Service\Context\Provider\FrameworkEvaluationsProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EvaluationsController extends AbstractController
{
    #[Route('/evaluations', name: 'app_promotion_evaluations', methods: ['GET'])]
    public function index(
        Request $request,
        FrameworkEvaluationsProvider $evaluationsProvider,
        EvaluationChartService $evaluationChartService,
    ): Response {
        $promotion = (string)$request->query->get('promotion', '');
        $year = (string)$request->query->get('year', '');

        if ($promotion === '' || $year === '') {
            return $this->redirectToRoute('app_home');
        }

        $evaluations = $evaluationsProvider->listEvaluations($promotion, $year);

        if ($evaluations === []) {
            $this->addFlash('warning', 'Aucune évaluation disponible pour ce référentiel.');

            return $this->redirectToRoute('app_promotion_summary', [
                'promotion' => $promotion,
                'year' => $year,
            ]);
        }

        $rawSelected = (string)$request->query->get('code', '');
        $selectedFileCode = $this->normalizeSelectedCode($rawSelected, $evaluations);

        if ($selectedFileCode === null) {
            $selectedEvaluation = $evaluations[0];
            $selectedFileCode = $selectedEvaluation->fileCode;
        } else {
            $selectedEvaluation = null;

            foreach ($evaluations as $evaluation) {
                if ($evaluation->fileCode === $selectedFileCode) {
                    $selectedEvaluation = $evaluation;
                    break;
                }
            }

            if ($selectedEvaluation === null) {
                $selectedEvaluation = $evaluations[0];
                $selectedFileCode = $selectedEvaluation->fileCode;
            }
        }

        $evaluationVolumeChart = $evaluationChartService->createEvaluationVolumeChart($selectedEvaluation);

        return $this->render('evaluations/index.html.twig', [
            'promotion' => $promotion,
            'year' => $year,
            'evaluations' => $evaluations,
            'selectedCode' => $selectedFileCode,
            'evaluation' => $selectedEvaluation,
            'evaluationVolumeChart' => $evaluationVolumeChart,
        ]);
    }

    /**
     * @param array<int,mixed> $evaluations
     */
    private function normalizeSelectedCode(string $raw, array $evaluations): ?string
    {
        $raw = strtolower(trim($raw));

        if ($raw === '') {
            return null;
        }

        foreach ($evaluations as $evaluation) {
            if (strtolower((string)$evaluation->fileCode) === $raw) {
                return (string)$evaluation->fileCode;
            }
        }

        return null;
    }
}
