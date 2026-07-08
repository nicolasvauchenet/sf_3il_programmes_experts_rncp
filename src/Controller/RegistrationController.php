<?php

namespace App\Controller;

use App\Dto\RegisterUserInput;
use App\Entity\User;
use App\Form\RegisterUserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController extends AbstractController
{
    #[Route('/inscription', name: 'app_register', methods: ['GET', 'POST'])]
    public function __invoke(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $input = new RegisterUserInput();
        $form = $this->createForm(RegisterUserType::class, $input);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $existingUser = $userRepository->findOneBy(['email' => $input->getEmail()]);

            if ($existingUser instanceof User) {
                $form->get('emailPrefix')->addError(new FormError('Un compte existe déjà avec cette adresse email.'));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $user = (new User())
                ->setFullName($input->fullName)
                ->setEmail($input->getEmail())
                ->setRoles([$input->role])
                ->setIsActive(true)
                ->setIsAccepted(false);

            $user->setPassword($passwordHasher->hashPassword($user, $input->password));

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Votre compte a été créé. Il devra être accepté avant votre première connexion.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'form' => $form,
        ]);
    }
}
