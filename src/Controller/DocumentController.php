<?php

namespace App\Controller;

use App\Entity\PromotionDocument;
use App\Service\Admin\PromotionDocumentStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Response, ResponseHeaderBag};
use Symfony\Component\Routing\Attribute\Route;

final class DocumentController extends AbstractController
{
    #[Route('/documents/{id}', name: 'app_document_download', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function download(int $id, EntityManagerInterface $em, PromotionDocumentStorage $storage): Response
    {
        $document = $em->find(PromotionDocument::class, $id);
        if (!$document || (!$document->visible && !$this->isGranted('ROLE_ADMIN'))) throw $this->createNotFoundException();
        $path = $storage->path($document->filename);
        if (!is_file($path)) throw $this->createNotFoundException('Fichier introuvable.');
        $response = $this->file($path, $document->originalName, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Cache-Control', 'private, no-store');
        return $response;
    }
}
