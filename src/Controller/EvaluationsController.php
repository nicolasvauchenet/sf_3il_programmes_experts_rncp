<?php

namespace App\Controller;

use App\Dto\Context\ResolvedEvaluationSheet;
use App\Service\Chart\EvaluationChartService;
use App\Service\Context\Provider\FrameworkEvaluationsProvider;
use App\Service\Pdf\PdfGenerator;
use App\Twig\PromotionContext;
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
        PdfGenerator                  $pdfGenerator,
        PromotionContext              $promotionContext,
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
            $selectedEvaluation = null;

            foreach ($evaluations as $evaluation) {
                if ($evaluation->fileCode === $selectedFileCode) {
                    $selectedEvaluation = $evaluation;
                    break;
                }
            }

            if ($selectedEvaluation === null) {
                $selectedEvaluation = $this->findFirstEvaluationForBlock($evaluations, $selectedBlock) ?? $evaluations[0];
                $selectedFileCode = $selectedEvaluation->fileCode;
            }
        }

        $evaluationVolumeChart = $evaluationChartService->createEvaluationVolumeChart($selectedEvaluation);

        $viewData = [
            'promotion' => $promotion,
            'year' => $year,
            'evaluations' => $evaluations,
            'selectedCode' => $selectedFileCode,
            'selectedBlock' => $selectedBlock,
            'evaluation' => $selectedEvaluation,
            'evaluationVolumeChart' => $evaluationVolumeChart,
        ];

        if ($request->query->get('download') === 'pdf') {
            $viewData['pdfMode'] = true;

            return $pdfGenerator->download(
                $this->renderView('evaluations/index.html.twig', $viewData),
                sprintf('evaluation-%s.pdf', $selectedEvaluation->evaluationCode()),
                [
                    'promotionTitle' => $promotionContext->getPromotionTitle() ?? strtoupper($promotion),
                    'sheetLabel' => sprintf('Fiche Évaluation %s', $selectedEvaluation->evaluationCode()),
                    'sheetTitle' => $selectedEvaluation->title() ?: 'Évaluation',
                ],
            );
        }

        return $this->render('evaluations/index.html.twig', $viewData);
    }

    /**
     * @param ResolvedEvaluationSheet[] $evaluations
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

    private function normalizeBlockCode(string $raw): ?string
    {
        $raw = strtolower(trim($raw));

        if ($raw === '') {
            return null;
        }

        return $raw;
    }

    /**
     * @param ResolvedEvaluationSheet[] $evaluations
     */
    private function findFirstEvaluationForBlock(array $evaluations, ?string $blockCode): ?ResolvedEvaluationSheet
    {
        if ($blockCode === null) {
            return null;
        }

        foreach ($evaluations as $evaluation) {
            $evaluationBlockCode = strtolower(trim($evaluation->blocCode()));

            if ($evaluationBlockCode === $blockCode) {
                return $evaluation;
            }
        }

        return null;
    }
}
