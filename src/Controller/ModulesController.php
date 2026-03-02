<?php

namespace App\Controller;

use App\Service\Context\FrameworkModulesProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ModulesController extends AbstractController
{
    #[Route('/matieres', name: 'app_promotion_modules', methods: ['GET'])]
    public function index(
        Request $request,
        FrameworkModulesProvider $modulesProvider,
    ): Response {
        $promotion = (string)$request->query->get('promotion', '');
        $year = (string)$request->query->get('year', '');

        if ($promotion === '' || $year === '') {
            return $this->redirectToRoute('app_home');
        }

        $modules = $modulesProvider->listModules($promotion, $year);

        if ($modules === []) {
            $this->addFlash('warning', 'Aucune matière disponible pour ce référentiel.');

            return $this->redirectToRoute('app_home');
        }

        $rawSelected = (string)$request->query->get('code', '');
        $selectedFileCode = $this->normalizeSelectedCode($rawSelected);

        if ($selectedFileCode === null) {
            $selectedModule = $modules[0];
            $selectedFileCode = $selectedModule->fileCode;
        } else {
            $selectedModule = null;
            foreach ($modules as $m) {
                if ($m->fileCode === $selectedFileCode) {
                    $selectedModule = $m;
                    break;
                }
            }
            if ($selectedModule === null) {
                $selectedModule = $modules[0];
                $selectedFileCode = $selectedModule->fileCode;
            }
        }

        return $this->render('modules/index.html.twig', [
            'promotion' => $promotion,
            'year' => $year,
            'modules' => $modules,
            'selectedCode' => $selectedFileCode,
            'module' => $selectedModule,
        ]);
    }

    private function normalizeSelectedCode(string $raw): ?string
    {
        $raw = strtolower(trim($raw));

        if ($raw === '') {
            return null;
        }

        if (preg_match('/^fm(\d{2})$/', $raw)) {
            return $raw;
        }

        if (preg_match('/^\d+$/', $raw)) {
            $n = (int)$raw;
            if ($n < 1) {
                return null;
            }

            return sprintf('fm%02d', $n);
        }

        return null;
    }
}
