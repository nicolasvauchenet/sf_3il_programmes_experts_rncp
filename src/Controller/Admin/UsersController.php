<?php

namespace App\Controller\Admin;

use App\Dto\Admin\CreateUserInput;
use App\Entity\User;
use App\Form\Admin\CreateUserType;
use App\Repository\UserRepository;
use App\Service\Chart\AdminUserChartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/administration/utilisateurs', name: 'app_admin_users_')]
final class UsersController extends AbstractController
{
    private const ROLE_FILTERS = [
        'admin' => 'Administrateur',
        'teacher' => 'Enseignant',
        'student' => 'Apprenant',
        'user' => 'Utilisateur',
    ];

    private const STATUS_FILTERS = [
        'active' => 'Actif',
        'inactive' => 'Inactif',
        'disabled' => 'Désactivé',
    ];

    #[Route(name: 'home')]
    public function index(
        Request $request,
        UserRepository $userRepository,
        AdminUserChartService $adminUserChartService,
    ): Response
    {
        $allUsers = $userRepository->findBy([], ['fullName' => 'ASC', 'email' => 'ASC']);
        $filters = $this->resolveFilters($request);
        $users = $this->filterUsers($allUsers, $filters);

        return $this->render('admin/users/index.html.twig', [
            'users' => array_map($this->normalizeUser(...), $users),
            'stats' => $this->buildStats($allUsers),
            'filteredUsersCount' => count($users),
            'usersDistributionChart' => $adminUserChartService->createUsersDistributionChart($allUsers),
            'filters' => $filters,
            'roleChoices' => self::ROLE_FILTERS,
            'statusChoices' => self::STATUS_FILTERS,
        ]);
    }

    #[Route('/nouveau', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $input = new CreateUserInput();
        $form = $this->createForm(CreateUserType::class, $input);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $existingUser = $userRepository->findOneBy(['email' => strtolower($input->email)]);

            if ($existingUser instanceof User) {
                $form->get('email')->addError(new FormError('Un compte existe déjà avec cette adresse email.'));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $user = (new User())
                ->setFullName($input->fullName)
                ->setEmail($input->email)
                ->setRoles([$input->role])
                ->setIsActive(!$input->disabled);

            $user->setPassword($passwordHasher->hashPassword($user, $input->password));

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', sprintf('Le compte de %s a été créé.', $user->getFullName()));

            return $this->redirectToRoute('app_admin_users_home');
        }

        return $this->render('admin/users/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/activation', name: 'toggle_active', methods: ['GET'])]
    public function toggleActive(Request $request, User $user, EntityManagerInterface $entityManager): RedirectResponse
    {
        $currentUser = $this->getUser();

        if ($currentUser instanceof User && $currentUser->getId() === $user->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas désactiver votre propre compte.');

            return $this->redirectBackToUsersList($request);
        }

        $user->setIsActive(!$user->isActive());
        $entityManager->flush();

        $this->addFlash('success', sprintf(
            'Le compte de %s a été %s.',
            $user->getFullName(),
            $user->isActive() ? 'activé' : 'désactivé',
        ));

        return $this->redirectBackToUsersList($request);
    }

    private function redirectBackToUsersList(Request $request): RedirectResponse
    {
        $referer = $request->headers->get('referer');

        if (is_string($referer) && str_contains($referer, $this->generateUrl('app_admin_users_home'))) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_admin_users_home');
    }

    /**
     * @return array{role: string, status: string}
     */
    private function resolveFilters(Request $request): array
    {
        $role = $request->query->get('role', '');
        $status = $request->query->get('status', '');
        $role = is_string($role) ? $role : '';
        $status = is_string($status) ? $status : '';

        return [
            'role' => array_key_exists($role, self::ROLE_FILTERS) ? $role : '',
            'status' => array_key_exists($status, self::STATUS_FILTERS) ? $status : '',
        ];
    }

    /**
     * @param list<User> $users
     * @param array{role: string, status: string} $filters
     *
     * @return list<User>
     */
    private function filterUsers(array $users, array $filters): array
    {
        return array_values(array_filter($users, function (User $user) use ($filters): bool {
            return $this->matchesRoleFilter($user, $filters['role'])
                && $this->matchesStatusFilter($user, $filters['status']);
        }));
    }

    private function matchesRoleFilter(User $user, string $roleFilter): bool
    {
        $roles = $user->getRoles();

        return match ($roleFilter) {
            'admin' => in_array('ROLE_ADMIN', $roles, true),
            'teacher' => in_array('ROLE_TEACHER', $roles, true),
            'student' => in_array('ROLE_STUDENT', $roles, true),
            'user' => !in_array('ROLE_ADMIN', $roles, true)
                && !in_array('ROLE_TEACHER', $roles, true)
                && !in_array('ROLE_STUDENT', $roles, true),
            default => true,
        };
    }

    private function matchesStatusFilter(User $user, string $statusFilter): bool
    {
        return match ($statusFilter) {
            'active' => $user->isActive() && null !== $user->getLoggedAt(),
            'inactive' => $user->isActive() && null === $user->getLoggedAt(),
            'disabled' => !$user->isActive(),
            default => true,
        };
    }

    /**
     * @param list<User> $users
     *
     * @return array{total: int, teachers: int, students: int, inactive: int, disabled: int}
     */
    private function buildStats(array $users): array
    {
        $stats = [
            'total' => count($users),
            'teachers' => 0,
            'students' => 0,
            'inactive' => 0,
            'disabled' => 0,
        ];

        foreach ($users as $user) {
            $roles = $user->getRoles();

            if (in_array('ROLE_TEACHER', $roles, true)) {
                ++$stats['teachers'];
            }

            if (in_array('ROLE_STUDENT', $roles, true)) {
                ++$stats['students'];
            }

            if (!$user->isActive()) {
                ++$stats['disabled'];
                ++$stats['inactive'];

                continue;
            }

            if (null === $user->getLoggedAt()) {
                ++$stats['inactive'];
            }
        }

        return $stats;
    }

    /**
     * @return array{id: int|null, role: string, fullName: string, email: string, isActive: bool, createdAt: \DateTimeImmutable|null, loggedAt: \DateTimeImmutable|null}
     */
    private function normalizeUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'role' => $this->resolveRoleLabel($user->getRoles()),
            'fullName' => $user->getFullName() ?? '-',
            'email' => $user->getEmail() ?? '-',
            'isActive' => $user->isActive(),
            'createdAt' => $user->getCreatedAt(),
            'loggedAt' => $user->getLoggedAt(),
        ];
    }

    /**
     * @param list<string> $roles
     */
    private function resolveRoleLabel(array $roles): string
    {
        return match (true) {
            in_array('ROLE_ADMIN', $roles, true) => 'Administrateur',
            in_array('ROLE_TEACHER', $roles, true) => 'Enseignant',
            in_array('ROLE_STUDENT', $roles, true) => 'Apprenant',
            default => 'Utilisateur',
        };
    }
}
