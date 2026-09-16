<?php

namespace App\Controller;

use App\Dto\Context\ResolvedModuleSheet;
use App\Service\Chart\ModuleChartService;
use App\Service\Context\Provider\FrameworkModulesProvider;
use App\Service\Pdf\PdfGenerator;
use App\Twig\PromotionContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ModulesController extends AbstractController
{
    #[Route('/matieres', name: 'app_promotion_modules', methods: ['GET'])]
    public function index(
        Request                  $request,
        FrameworkModulesProvider $modulesProvider,
        ModuleChartService       $moduleChartService,
        PdfGenerator             $pdfGenerator,
        PromotionContext         $promotionContext,
    ): Response
    {
        $promotion = (string)$request->query->get('promotion', '');
        $year = (string)$request->query->get('year', '');

        if ($promotion === '' || $year === '') {
            return $this->redirectToRoute('app_home');
        }

        $modules = $modulesProvider->listModules($promotion, $year);

        if ($modules === []) {
            $this->addFlash('warning', 'Aucune matière disponible pour ce référentiel.');

            return $this->redirectToRoute('app_promotion_summary', [
                'promotion' => $promotion,
                'year' => $year,
            ]);
        }

        $rawSelected = (string)$request->query->get('code', '');
        $rawSelectedBlock = (string)$request->query->get('block', '');

        $selectedFileCode = $this->normalizeSelectedCode($rawSelected, $modules);
        $selectedBlock = $this->normalizeBlockCode($rawSelectedBlock);

        if ($selectedFileCode === null) {
            $selectedModule = $this->findFirstModuleForBlock($modules, $selectedBlock) ?? $modules[0];
            $selectedFileCode = $selectedModule->fileCode;
        } else {
            $selectedModule = null;

            foreach ($modules as $module) {
                if ($module->fileCode === $selectedFileCode) {
                    $selectedModule = $module;
                    break;
                }
            }

            if ($selectedModule === null) {
                $selectedModule = $this->findFirstModuleForBlock($modules, $selectedBlock) ?? $modules[0];
                $selectedFileCode = $selectedModule->fileCode;
            }
        }

        $moduleVolumeChart = $moduleChartService->createModuleVolumeChart($selectedModule);

        $viewData = [
            'promotion' => $promotion,
            'year' => $year,
            'modules' => $modules,
            'selectedCode' => $selectedFileCode,
            'selectedBlock' => $selectedBlock,
            'module' => $selectedModule,
            'moduleVolumeChart' => $moduleVolumeChart,
        ];

        if ($request->query->get('download') === 'pdf') {
            $viewData['pdfMode'] = true;

            return $pdfGenerator->download(
                $this->renderView('modules/index.html.twig', $viewData),
                sprintf('matiere-%s.pdf', $selectedModule->moduleCode()),
                [
                    'promotionTitle' => $promotionContext->getPromotionTitle() ?? strtoupper($promotion),
                    'sheetLabel' => sprintf('Fiche Matière %s', $selectedModule->moduleCode()),
                    'sheetTitle' => $selectedModule->title() ?: 'Matière',
                ],
            );
        }

        return $this->render('modules/index.html.twig', $viewData);
    }

    /**
     * @param ResolvedModuleSheet[] $modules
     */
    private function normalizeSelectedCode(string $raw, array $modules): ?string
    {
        $raw = strtolower(trim($raw));

        if ($raw === '') {
            return null;
        }

        foreach ($modules as $module) {
            if (strtolower((string)$module->fileCode) === $raw) {
                return (string)$module->fileCode;
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
     * @param ResolvedModuleSheet[] $modules
     */
    private function findFirstModuleForBlock(array $modules, ?string $blockCode): ?ResolvedModuleSheet
    {
        if ($blockCode === null) {
            return null;
        }

        foreach ($modules as $module) {
            $moduleBlockCode = strtolower(trim((string)($module->meta['blocCode'] ?? $module->meta['blockCode'] ?? '')));

            if ($moduleBlockCode === $blockCode) {
                return $module;
            }
        }

        return null;
    }
}
