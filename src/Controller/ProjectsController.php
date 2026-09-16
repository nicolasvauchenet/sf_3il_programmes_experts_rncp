<?php

namespace App\Controller;

use App\Dto\Context\ResolvedProjectSheet;
use App\Service\Chart\ProjectChartService;
use App\Service\Context\Provider\FrameworkProjectsProvider;
use App\Service\Pdf\PdfGenerator;
use App\Twig\PromotionContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProjectsController extends AbstractController
{
    #[Route('/projets', name: 'app_promotion_projects', methods: ['GET'])]
    public function index(
        Request                   $request,
        FrameworkProjectsProvider $projectsProvider,
        ProjectChartService       $projectChartService,
        PdfGenerator              $pdfGenerator,
        PromotionContext          $promotionContext,
    ): Response
    {
        $promotion = (string)$request->query->get('promotion', '');
        $year = (string)$request->query->get('year', '');

        if ($promotion === '' || $year === '') {
            return $this->redirectToRoute('app_home');
        }

        $projects = $projectsProvider->listProjects($promotion, $year);

        if ($projects === []) {
            $this->addFlash('warning', 'Aucun projet disponible pour ce référentiel.');

            return $this->redirectToRoute('app_promotion_summary', [
                'promotion' => $promotion,
                'year' => $year,
            ]);
        }

        $rawSelected = (string)$request->query->get('code', '');
        $rawSelectedBlock = (string)$request->query->get('block', '');

        $selectedFileCode = $this->normalizeSelectedCode($rawSelected, $projects);
        $selectedBlock = $this->normalizeBlockCode($rawSelectedBlock);

        if ($selectedFileCode === null) {
            $selectedProject = $this->findFirstProjectForBlock($projects, $selectedBlock) ?? $projects[0];
            $selectedFileCode = $selectedProject->fileCode;
        } else {
            $selectedProject = null;

            foreach ($projects as $project) {
                if ($project->fileCode === $selectedFileCode) {
                    $selectedProject = $project;
                    break;
                }
            }

            if ($selectedProject === null) {
                $selectedProject = $this->findFirstProjectForBlock($projects, $selectedBlock) ?? $projects[0];
                $selectedFileCode = $selectedProject->fileCode;
            }
        }

        $projectVolumeChart = $projectChartService->createProjectVolumeChart($selectedProject);

        $viewData = [
            'promotion' => $promotion,
            'year' => $year,
            'projects' => $projects,
            'selectedCode' => $selectedFileCode,
            'selectedBlock' => $selectedBlock,
            'project' => $selectedProject,
            'projectVolumeChart' => $projectVolumeChart,
        ];

        if ($request->query->get('download') === 'pdf') {
            $viewData['pdfMode'] = true;

            return $pdfGenerator->download(
                $this->renderView('projects/index.html.twig', $viewData),
                sprintf('projet-%s.pdf', $selectedProject->projectCode()),
                [
                    'promotionTitle' => $promotionContext->getPromotionTitle() ?? strtoupper($promotion),
                    'sheetLabel' => sprintf('Fiche Projet %s', $selectedProject->projectCode()),
                    'sheetTitle' => $selectedProject->title() ?: 'Projet',
                ],
            );
        }

        return $this->render('projects/index.html.twig', $viewData);
    }

    /**
     * @param ResolvedProjectSheet[] $projects
     */
    private function normalizeSelectedCode(string $raw, array $projects): ?string
    {
        $raw = strtolower(trim($raw));

        if ($raw === '') {
            return null;
        }

        foreach ($projects as $project) {
            if (strtolower((string)$project->fileCode) === $raw) {
                return (string)$project->fileCode;
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
     * @param ResolvedProjectSheet[] $projects
     */
    private function findFirstProjectForBlock(array $projects, ?string $blockCode): ?ResolvedProjectSheet
    {
        if ($blockCode === null) {
            return null;
        }

        foreach ($projects as $project) {
            $projectBlockCode = strtolower(trim($project->blocCode()));

            if ($projectBlockCode === $blockCode) {
                return $project;
            }
        }

        return null;
    }
}
