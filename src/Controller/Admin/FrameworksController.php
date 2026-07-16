<?php

namespace App\Controller\Admin;

use App\Service\Admin\PromotionJsonArchiveManager;
use App\Service\Admin\PromotionJsonDatabaseManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/administration/referentiels', name: 'app_admin_frameworks_')]
final class FrameworksController extends AbstractController
{
    #[Route(name: 'home', methods: ['GET'])]
    public function index(PromotionJsonArchiveManager $archiveManager, PromotionJsonDatabaseManager $databaseManager): Response
    {
        $datasets = $archiveManager->listDatasets();

        foreach ($datasets as $index => $dataset) {
            $datasets[$index]['database'] = $databaseManager->getDatabaseState($dataset['name']);
        }

        return $this->render('admin/frameworks/index.html.twig', [
            'datasets' => $datasets,
        ]);
    }

    #[Route('/upload', name: 'upload', methods: ['POST'])]
    public function upload(Request $request, PromotionJsonArchiveManager $archiveManager): Response
    {
        if (!$this->isCsrfTokenValid('upload_framework_archive', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'La sécurité du formulaire a expiré, merci de réessayer');

            return $this->redirectToRoute('app_admin_frameworks_home');
        }

        $archive = $request->files->get('archive');

        if (!$archive instanceof UploadedFile) {
            $this->addFlash('error', 'Merci de sélectionner une archive ZIP');

            return $this->redirectToRoute('app_admin_frameworks_home');
        }

        try {
            $pendingUpload = $archiveManager->storePendingUpload($archive);

            if ($archiveManager->datasetExists($pendingUpload['datasetName'])) {
                return $this->redirectToRoute('app_admin_frameworks_confirm_replace', [
                    'token' => $pendingUpload['token'],
                ]);
            }

            $datasetName = $archiveManager->importPendingArchive($pendingUpload['token'], false);
            $this->addFlash('success', sprintf('Le dossier "%s" a été importé', $datasetName));
        } catch (\Throwable $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_frameworks_home');
    }

    #[Route('/upload/{token}/conflit', name: 'confirm_replace', methods: ['GET'])]
    public function confirmReplace(string $token, PromotionJsonArchiveManager $archiveManager): Response
    {
        try {
            $pendingUpload = $archiveManager->getPendingUpload($token);

            if (!$archiveManager->datasetExists($pendingUpload['datasetName'])) {
                $datasetName = $archiveManager->importPendingArchive($token, false);
                $this->addFlash('success', sprintf('Le dossier "%s" a été importé', $datasetName));

                return $this->redirectToRoute('app_admin_frameworks_home');
            }

            return $this->render('admin/frameworks/replace.html.twig', [
                'pendingUpload' => $pendingUpload,
            ]);
        } catch (\Throwable $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('app_admin_frameworks_home');
        }
    }

    #[Route('/upload/{token}/remplacer', name: 'replace', methods: ['POST'])]
    public function replace(string $token, Request $request, PromotionJsonArchiveManager $archiveManager): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('replace_framework_archive_' . $token, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'La sécurité du formulaire a expiré, merci de réessayer');

            return $this->redirectToRoute('app_admin_frameworks_home');
        }

        try {
            $datasetName = $archiveManager->importPendingArchive($token, true);
            $this->addFlash('success', sprintf('Le dossier "%s" a été remplacé', $datasetName));
        } catch (\Throwable $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_frameworks_home');
    }

    #[Route('/upload/{token}/annuler', name: 'cancel', methods: ['POST'])]
    public function cancel(string $token, Request $request, PromotionJsonArchiveManager $archiveManager): RedirectResponse
    {
        if ($this->isCsrfTokenValid('cancel_framework_archive_' . $token, (string) $request->request->get('_token'))) {
            $archiveManager->cancelPendingArchive($token);
            $this->addFlash('success', 'Import annulé');
        } else {
            $this->addFlash('error', 'La sécurité du formulaire a expiré, merci de réessayer');
        }

        return $this->redirectToRoute('app_admin_frameworks_home');
    }

    #[Route('/{datasetName}/supprimer', name: 'delete', methods: ['POST'])]
    public function delete(string $datasetName, Request $request, PromotionJsonArchiveManager $archiveManager): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('delete_framework_dataset_' . $datasetName, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'La suppression a échoué, merci de réessayer');

            return $this->redirectToRoute('app_admin_frameworks_home');
        }

        try {
            $archiveManager->deleteDataset($datasetName);
            $this->addFlash('success', sprintf('Le dossier "%s" a été supprimé', $datasetName));
        } catch (\Throwable $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_frameworks_home');
    }

    #[Route('/{datasetName}/bdd/importer', name: 'database_import', methods: ['POST'])]
    public function importToDatabase(string $datasetName, Request $request, PromotionJsonDatabaseManager $databaseManager): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('import_framework_database_' . $datasetName, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Import impossible, merci de réessayer');

            return $this->redirectToRoute('app_admin_frameworks_home');
        }

        try {
            if ($databaseManager->exists($datasetName)) {
                return $this->redirectToRoute('app_admin_frameworks_database_confirm_import', [
                    'datasetName' => $datasetName,
                ]);
            }

            $report = $databaseManager->import($datasetName);
            $this->addFlash('success', $this->formatImportSuccess($datasetName, $databaseManager->normalizeReport($report)));
        } catch (\Throwable $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_frameworks_home');
    }

    #[Route('/{datasetName}/bdd/importer/confirmer', name: 'database_confirm_import', methods: ['GET'])]
    public function confirmDatabaseImport(string $datasetName, PromotionJsonDatabaseManager $databaseManager): Response
    {
        try {
            if (!$databaseManager->exists($datasetName)) {
                $report = $databaseManager->import($datasetName);
                $this->addFlash('success', $this->formatImportSuccess($datasetName, $databaseManager->normalizeReport($report)));

                return $this->redirectToRoute('app_admin_frameworks_home');
            }

            return $this->render('admin/frameworks/database_replace.html.twig', [
                'datasetName' => $datasetName,
                'database' => $databaseManager->getDatabaseState($datasetName),
            ]);
        } catch (\Throwable $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('app_admin_frameworks_home');
        }
    }

    #[Route('/{datasetName}/bdd/remplacer', name: 'database_replace', methods: ['POST'])]
    public function replaceInDatabase(string $datasetName, Request $request, PromotionJsonDatabaseManager $databaseManager): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('replace_framework_database_' . $datasetName, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Import impossible, merci de réessayer');

            return $this->redirectToRoute('app_admin_frameworks_home');
        }

        try {
            $report = $databaseManager->import($datasetName);
            $this->addFlash('success', $this->formatImportSuccess($datasetName, $databaseManager->normalizeReport($report)));
        } catch (\Throwable $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_frameworks_home');
    }

    #[Route('/{datasetName}/bdd/supprimer', name: 'database_delete', methods: ['POST'])]
    public function deleteFromDatabase(string $datasetName, Request $request, PromotionJsonDatabaseManager $databaseManager): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('delete_framework_database_' . $datasetName, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Suppression impossible, merci de réessayer');

            return $this->redirectToRoute('app_admin_frameworks_home');
        }

        try {
            $databaseManager->delete($datasetName);
            $this->addFlash('success', sprintf('Le référentiel "%s" a été supprimé de la BDD', $datasetName));
        } catch (\Throwable $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_frameworks_home');
    }

    /**
     * @param array<string, array{created:int, updated:int, deleted:int}> $report
     */
    private function formatImportSuccess(string $datasetName, array $report): string
    {
        $created = array_sum(array_column($report, 'created'));
        $updated = array_sum(array_column($report, 'updated'));
        $deleted = array_sum(array_column($report, 'deleted'));

        return sprintf(
            'Import BDD de "%s" terminé : %d créé(s), %d mis à jour, %d supprimé(s)',
            $datasetName,
            $created,
            $updated,
            $deleted,
        );
    }
}
