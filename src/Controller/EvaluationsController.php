<?php

namespace App\Controller;

use App\Dto\Context\EvaluationSheet;
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
        Request                      $request,
        FrameworkEvaluationsProvider $evaluationsProvider,
        EvaluationChartService       $evaluationChartService,
    ): Response
    {
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
        $rawSelectedBlock = (string)$request->query->get('block', '');

        $selectedFileCode = $this->normalizeSelectedCode($rawSelected, $evaluations);
        $selectedBlock = $this->normalizeBlockCode($rawSelectedBlock);

        if ($selectedFileCode === null) {
            $selectedEvaluation = $this->findFirstEvaluationForBlock($evaluations, $selectedBlock) ?? $evaluations[0];
            $selectedFileCode = $selectedEvaluation->fileCode;
        } else {
            $selectedEvaluation = $this->findSelectedEvaluation($evaluations, $selectedFileCode);

            if ($selectedEvaluation === null) {
                $selectedEvaluation = $this->findFirstEvaluationForBlock($evaluations, $selectedBlock) ?? $evaluations[0];
                $selectedFileCode = $selectedEvaluation->fileCode;
            }
        }

        $evaluationVolumeChart = $evaluationChartService->createEvaluationVolumeChart($selectedEvaluation);

        return $this->render('evaluations/index.html.twig', [
            'promotion' => $promotion,
            'year' => $year,
            'evaluations' => $evaluations,
            'selectedCode' => $selectedFileCode,
            'selectedBlock' => $selectedBlock,
            'evaluation' => $selectedEvaluation,
            'evaluationVolumeChart' => $evaluationVolumeChart,
        ]);
    }

    /**
     * @param EvaluationSheet[] $evaluations
     */
    private function normalizeSelectedCode(string $raw, array $evaluations): ?string
    {
        $raw = strtolower(trim($raw));

        if ($raw === '') {
            return null;
        }

        foreach ($evaluations as $evaluation) {
            if (strtolower($evaluation->fileCode) === $raw) {
                return $evaluation->fileCode;
            }
        }

        return null;
    }

    private function normalizeBlockCode(string $raw): ?string
    {
        $raw = strtoupper(trim($raw));

        if ($raw === '') {
            return null;
        }

        return $raw;
    }

    /**
     * @param EvaluationSheet[] $evaluations
     */
    private function findSelectedEvaluation(array $evaluations, string $selectedFileCode): ?EvaluationSheet
    {
        foreach ($evaluations as $evaluation) {
            if ($evaluation->fileCode === $selectedFileCode) {
                return $evaluation;
            }
        }

        return null;
    }

    /**
     * @param EvaluationSheet[] $evaluations
     */
    private function findFirstEvaluationForBlock(array $evaluations, ?string $blockCode): ?EvaluationSheet
    {
        if ($blockCode === null) {
            return null;
        }

        foreach ($evaluations as $evaluation) {
            if (strtoupper(trim($evaluation->blocCode())) === $blockCode) {
                return $evaluation;
            }
        }

        return null;
    }
}
