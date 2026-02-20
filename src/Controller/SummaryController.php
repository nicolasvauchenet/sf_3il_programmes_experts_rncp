<?php

namespace App\Controller;

use App\Service\Framework\FrameworkAnalyticsService;
use App\Exception\DatasetNotFoundException;
use App\Exception\InvalidDatasetIdentifierException;
use App\Service\Framework\DatasetPathResolver;
use App\Service\Framework\FrameworkProvider;
use App\Warning\WarningCollector;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SummaryController extends AbstractController
{
    #[Route('/promotion', name: 'app_promotion_summary')]
    public function index(
        Request $request,
        DatasetPathResolver $resolver,
        FrameworkProvider $provider,
        FrameworkAnalyticsService $analytics,
        WarningCollector $warnings,
    ): Response {
        $promotion = (string)$request->query->get('promotion', '');
        $year = (string)$request->query->get('year', '');

        if ($promotion === '' || $year === '') {
            return $this->redirectToRoute('app_home');
        }

        try {
            $context = $resolver->validateAndCreateContext($promotion, $year);

            $structure = $provider->getStructure($context);

            $modules = $provider->getModules($context);
            $skills = $provider->getSkills($context);

            $summary = $analytics->getSummary($context);
            $skillsPerModule = $analytics->getSkillsPerModuleDataset($context);
            $unlinkedSkills = $analytics->getUnlinkedSkillsDataset($context);
        } catch (InvalidDatasetIdentifierException) {
            return $this->redirectToRoute('app_home');
        } catch (DatasetNotFoundException) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('summary/index.html.twig', [
            'promotion' => $context->promotion,
            'year' => $context->year,
            'structure' => $structure,
            'modules' => $modules,
            'skills' => $skills,
            'summary' => $summary,
            'skillsPerModule' => $skillsPerModule,
            'unlinkedSkills' => $unlinkedSkills,
            'warnings' => $warnings->all(),
        ]);
    }
}
