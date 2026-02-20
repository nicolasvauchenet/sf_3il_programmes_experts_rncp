<?php

namespace App\Controller;

use App\Service\Framework\FrameworkAnalyticsService;
use App\Exception\DatasetNotFoundException;
use App\Exception\InvalidDatasetIdentifierException;
use App\Service\Framework\DatasetPathResolver;
use App\Service\Framework\FrameworkProvider;
use App\Service\Framework\ModuleReader;
use App\Warning\WarningCollector;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CoursesController extends AbstractController
{
    #[Route('/matieres', name: 'app_promotion_courses')]
    public function index(
        Request $request,
        DatasetPathResolver $resolver,
        FrameworkProvider $provider,
        ModuleReader $moduleReader,
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

            $structure = $provider->getStructure($context);

            $moduleCodes = $provider->getModuleCodes($context);

            $modules = $provider->getModules($context);

            $defaultCode = $moduleCodes[0] ?? '';

            $selectedCode = $selectedCode !== '' ? $selectedCode : $defaultCode;

            $selectedModule = null;
            if ($selectedCode !== '') {
                $selectedModule = $moduleReader->readOne($context, $selectedCode);
            }

            $summary = $analytics->getSummary($context);
        } catch (InvalidDatasetIdentifierException|DatasetNotFoundException) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('courses/index.html.twig', [
            'promotion' => $context->promotion,
            'year' => $context->year,
            'structure' => $structure,
            'modules' => $modules,
            'moduleCodes' => $moduleCodes,
            'selectedCode' => $selectedCode,
            'selectedModule' => $selectedModule,
            'summary' => $summary,
            'warnings' => $warnings->all(),
        ]);
    }
}
