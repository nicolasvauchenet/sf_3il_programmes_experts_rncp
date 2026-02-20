<?php

namespace App\Controller;

use App\Service\Framework\FrameworkAnalyticsService;
use App\Exception\DatasetNotFoundException;
use App\Exception\InvalidDatasetIdentifierException;
use App\Service\Framework\DatasetPathResolver;
use App\Service\Framework\FrameworkProvider;
use App\Service\Framework\SkillReader;
use App\Warning\WarningCollector;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SkillsController extends AbstractController
{
    #[Route('/competences', name: 'app_promotion_skills')]
    public function index(
        Request $request,
        DatasetPathResolver $resolver,
        FrameworkProvider $provider,
        SkillReader $skillReader,
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

            $skillCodes = $provider->getSkillCodes($context);
            $skills = $provider->getSkills($context);

            $defaultCode = $skillCodes[0] ?? '';
            $selectedCode = $selectedCode !== '' ? $selectedCode : $defaultCode;

            $selectedSkill = null;
            if ($selectedCode !== '') {
                $selectedSkill = $skillReader->readOne($context, $selectedCode);
            }

            $summary = $analytics->getSummary($context);
        } catch (InvalidDatasetIdentifierException|DatasetNotFoundException) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('skills/index.html.twig', [
            'promotion' => $context->promotion,
            'year' => $context->year,
            'skills' => $skills,
            'skillCodes' => $skillCodes,
            'selectedCode' => $selectedCode,
            'selectedSkill' => $selectedSkill,
            'summary' => $summary,
            'warnings' => $warnings->all(),
        ]);
    }
}
