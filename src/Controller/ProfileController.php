<?php

namespace App\Controller;

use App\Dto\ProfileInput;
use App\Entity\User;
use App\Form\ProfileType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ProfileController extends AbstractController
{
    #[Route('/profil', name: 'app_profile', methods: ['GET', 'POST'])]
    public function __invoke(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $input = new ProfileInput();
        $input->fullName = $user->getFullName() ?? '';
        $input->setEmail($user->getEmail() ?? '');

        $form = $this->createForm(ProfileType::class, $input);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $existingUser = $userRepository->findOneBy(['email' => $input->getEmail()]);

            if ($existingUser instanceof User && $existingUser->getId() !== $user->getId()) {
                $form->get('emailPrefix')->addError(new FormError('Un compte existe déjà avec cette adresse email.'));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $user
                ->setFullName($input->fullName)
                ->setEmail($input->getEmail());

            if (null !== $input->password && '' !== $input->password) {
                $user->setPassword($passwordHasher->hashPassword($user, $input->password));
            }

            $entityManager->flush();

            $this->addFlash('success', 'Votre profil a été mis à jour.');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form,
        ]);
    }
}
