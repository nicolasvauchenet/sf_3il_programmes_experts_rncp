<?php

namespace App\Controller;

use App\Exception\DatasetNotFoundException;
use App\Exception\InvalidDatasetIdentifierException;
use App\Service\Framework\DatasetPathResolver;
use App\Service\Framework\FrameworkAnalyticsService;
use App\Service\Framework\FrameworkProvider;
use App\Warning\WarningCollector;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EvaluationsController extends AbstractController
{
    #[Route('/evaluations', name: 'app_promotion_evaluations')]
    public function index(
        Request $request,
        DatasetPathResolver $resolver,
        FrameworkProvider $provider,
        FrameworkAnalyticsService $analytics,
        WarningCollector $warnings,
    ): Response {
        $promotion = (string)$request->query->get('promotion', '');
        $year = (string)$request->query->get('year', '');
        $selectedCode = strtolower((string)$request->query->get('code', ''));

        if ($promotion === '' || $year === '') {
            return $this->redirectToRoute('app_home');
        }

        try {
            $context = $resolver->validateAndCreateContext($promotion, $year);

            $provider->getStructure($context);

            $evaluations = $provider->getEvaluations($context);
            $evaluationCodes = $provider->getEvaluationCodes($context);

            $defaultCode = $evaluationCodes[0] ?? '';
            $selectedCode = $selectedCode !== '' ? $selectedCode : $defaultCode;

            $selectedEvaluation = $provider->findEvaluation($context, $selectedCode);

            $summary = $analytics->getSummary($context);
        } catch (InvalidDatasetIdentifierException|DatasetNotFoundException) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('evaluations/index.html.twig', [
            'promotion' => $context->promotion,
            'year' => $context->year,
            'evaluations' => $evaluations,
            'evaluationCodes' => $evaluationCodes,
            'selectedCode' => $selectedCode,
            'selectedEvaluation' => $selectedEvaluation,
            'summary' => $summary,
            'warnings' => $warnings->all(),
        ]);
    }
}
