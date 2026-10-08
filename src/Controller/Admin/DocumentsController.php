<?php

namespace App\Controller\Admin;

use App\Entity\{Promotion, PromotionDocument};
use App\Form\PromotionDocumentType;
use App\Service\Admin\PromotionDocumentStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/administration/referentiels/{promotionId}/documents', name: 'app_admin_documents_', requirements: ['promotionId' => '\d+'])]
#[IsGranted('ROLE_ADMIN')]
final class DocumentsController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(int $promotionId, EntityManagerInterface $em): Response
    {
        $promotion = $em->find(Promotion::class, $promotionId) ?? throw $this->createNotFoundException();
        return $this->render('admin/documents/index.html.twig', [
            'promotion' => $promotion,
            'documents' => $em->getRepository(PromotionDocument::class)->findBy(['promotion' => $promotion], ['position' => 'ASC', 'id' => 'ASC']),
        ]);
    }

    #[Route('/nouveau', name: 'new', methods: ['GET', 'POST'])]
    #[Route('/{id}/modifier', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(int $promotionId, Request $request, EntityManagerInterface $em, PromotionDocumentStorage $storage, ?int $id = null): Response
    {
        $promotion = $em->find(Promotion::class, $promotionId) ?? throw $this->createNotFoundException();
        $document = $id === null ? new PromotionDocument() : $em->getRepository(PromotionDocument::class)->findOneBy(['id' => $id, 'promotion' => $promotion]);
        if (!$document) throw $this->createNotFoundException();
        $document->promotion = $promotion;
        $form = $this->createForm(PromotionDocumentType::class, $document);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $old = $document->filename;
            $new = null;
            try {
                $file = $form->get('file')->getData();
                if ($file instanceof UploadedFile) {
                    $new = $storage->store($file);
                    $document->filename = $new;
                    $document->originalName = mb_substr(basename(str_replace('\\', '/', $file->getClientOriginalName())), 0, 255);
                }
                $em->persist($document);
                $em->flush();
            } catch (\Throwable $error) {
                if ($new !== null) $storage->remove($new);
                throw $error;
            }
            if ($new !== null) $storage->remove($old);
            $this->addFlash('success', 'Document enregistré.');
            return $this->redirectToRoute('app_admin_documents_index', ['promotionId' => $promotionId]);
        }
        return $this->render('admin/documents/edit.html.twig', ['promotion' => $promotion, 'document' => $document, 'form' => $form]);
    }

    #[Route('/{id}/supprimer', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(int $promotionId, int $id, Request $request, EntityManagerInterface $em, PromotionDocumentStorage $storage): Response
    {
        $document = $em->getRepository(PromotionDocument::class)->findOneBy(['id' => $id, 'promotion' => $promotionId]);
        if (!$document) throw $this->createNotFoundException();
        if (!$this->isCsrfTokenValid('delete_document_' . $id, (string) $request->request->get('_token'))) throw $this->createAccessDeniedException();
        $filename = $document->filename;
        $em->remove($document);
        $em->flush();
        $storage->remove($filename);
        $this->addFlash('success', 'Document supprimé.');
        return $this->redirectToRoute('app_admin_documents_index', ['promotionId' => $promotionId]);
    }
}
