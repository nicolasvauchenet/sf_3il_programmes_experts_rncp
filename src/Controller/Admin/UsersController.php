<?php

namespace App\Controller\Admin;

use App\Dto\Admin\CreateUserInput;
use App\Dto\Admin\EditUserInput;
use App\Entity\User;
use App\Form\Admin\CreateUserType;
use App\Form\Admin\EditUserType;
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
    private const PROTECTED_ADMIN_EMAIL = 'vauche@3il.fr';

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
        'unaccepted' => 'En attente',
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
        $listState = $this->resolveListState($request);
        $page = $userRepository->findForAdminList(
            filters: $filters,
            sort: $listState['sort'],
            direction: $listState['direction'],
            perPage: $listState['perPage'],
            page: $listState['page'],
        );

        return $this->render('admin/users/index.html.twig', [
            'users' => array_map($this->normalizeUser(...), $page['items']),
            'stats' => $this->buildStats($allUsers),
            'filteredUsersCount' => $page['total'],
            'usersDistributionChart' => $adminUserChartService->createUsersDistributionChart($allUsers),
            'filters' => $filters,
            'roleChoices' => self::ROLE_FILTERS,
            'statusChoices' => self::STATUS_FILTERS,
            'sort' => [
                'column' => $listState['sort'],
                'direction' => $listState['direction'],
            ],
            'pagination' => [
                'page' => $page['page'],
                'pages' => $page['pages'],
                'perPage' => $page['perPage'],
                'pageSizes' => UserRepository::ADMIN_PAGE_SIZES,
                'pageNumbers' => $this->buildPageNumbers($page['page'], $page['pages']),
            ],
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
                ->setIsActive(!$input->disabled)
                ->setIsAccepted(true);

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

    #[Route('/{id}/modifier', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        User $user,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $input = new EditUserInput();
        $input->fullName = $user->getFullName() ?? '';
        $input->email = $user->getEmail() ?? '';
        $input->role = $this->resolveEditableRole($user->getRoles());
        $input->disabled = !$user->isActive();

        $form = $this->createForm(EditUserType::class, $input);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $existingUser = $userRepository->findOneBy(['email' => strtolower($input->email)]);

            if ($existingUser instanceof User && $existingUser->getId() !== $user->getId()) {
                $form->get('email')->addError(new FormError('Un compte existe déjà avec cette adresse email.'));
            }

            $currentUser = $this->getUser();

            if ($currentUser instanceof User && $currentUser->getId() === $user->getId() && $input->disabled) {
                $form->get('disabled')->addError(new FormError('Vous ne pouvez pas désactiver votre propre compte.'));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $user
                ->setFullName($input->fullName)
                ->setEmail($input->email)
                ->setRoles([$input->role])
                ->setIsActive(!$input->disabled);

            if (null !== $input->password && '' !== $input->password) {
                $user->setPassword($passwordHasher->hashPassword($user, $input->password));
            }

            $entityManager->flush();

            $this->addFlash('success', sprintf('Le compte de %s a été modifié.', $user->getFullName()));

            return $this->redirectToRoute('app_admin_users_home');
        }

        return $this->render('admin/users/edit.html.twig', [
            'form' => $form,
            'user' => $user,
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

    #[Route('/{id}/accepter', name: 'accept', methods: ['GET'])]
    public function accept(Request $request, User $user, EntityManagerInterface $entityManager): RedirectResponse
    {
        if ($user->isAccepted()) {
            $this->addFlash('success', sprintf('Le compte de %s est déjà accepté.', $user->getFullName()));

            return $this->redirectBackToUsersList($request);
        }

        $user->setIsAccepted(true);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Le compte de %s a été accepté.', $user->getFullName()));

        return $this->redirectBackToUsersList($request);
    }

    #[Route('/{id}/supprimer', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('delete_user_'.$user->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'La suppression a échoué, merci de réessayer.');

            return $this->redirectBackToUsersList($request);
        }

        if (self::PROTECTED_ADMIN_EMAIL === $user->getEmail()) {
            $this->addFlash('error', 'Le compte administrateur Nicolas Vauché ne peut pas être supprimé.');

            return $this->redirectBackToUsersList($request);
        }

        $currentUser = $this->getUser();

        if ($currentUser instanceof User && $currentUser->getId() === $user->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');

            return $this->redirectBackToUsersList($request);
        }

        $fullName = $user->getFullName();
        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Le compte de %s a été supprimé.', $fullName));

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
     * @return array{sort: string, direction: string, perPage: int|'all', page: int}
     */
    private function resolveListState(Request $request): array
    {
        $sort = $request->query->get('sort', 'fullName');
        $direction = $request->query->get('direction', 'asc');
        $perPage = $request->query->get('perPage', '10');
        $page = $request->query->get('page', '1');

        $sort = is_string($sort) && in_array($sort, UserRepository::ADMIN_SORTS, true) ? $sort : 'fullName';
        $direction = 'desc' === $direction ? 'desc' : 'asc';
        $perPage = is_string($perPage) && in_array($perPage, UserRepository::ADMIN_PAGE_SIZES, true) ? $perPage : '10';
        $page = is_string($page) && ctype_digit($page) ? (int) $page : 1;

        return [
            'sort' => $sort,
            'direction' => $direction,
            'perPage' => 'all' === $perPage ? 'all' : (int) $perPage,
            'page' => max(1, $page),
        ];
    }

    /**
     * @return list<int|string>
     */
    private function buildPageNumbers(int $currentPage, int $totalPages): array
    {
        if ($totalPages <= 7) {
            return range(1, $totalPages);
        }

        $pages = [1];
        $start = max(2, $currentPage - 1);
        $end = min($totalPages - 1, $currentPage + 1);

        if ($start > 2) {
            $pages[] = '...';
        }

        for ($page = $start; $page <= $end; ++$page) {
            $pages[] = $page;
        }

        if ($end < $totalPages - 1) {
            $pages[] = '...';
        }

        $pages[] = $totalPages;

        return $pages;
    }

    /**
     * @param list<User> $users
     *
     * @return array{total: int, teachers: int, students: int, inactive: int, disabled: int, unaccepted: int}
     */
    private function buildStats(array $users): array
    {
        $stats = [
            'total' => count($users),
            'teachers' => 0,
            'students' => 0,
            'inactive' => 0,
            'disabled' => 0,
            'unaccepted' => 0,
        ];

        foreach ($users as $user) {
            $roles = $user->getRoles();

            if (in_array('ROLE_TEACHER', $roles, true)) {
                ++$stats['teachers'];
            }

            if (in_array('ROLE_STUDENT', $roles, true)) {
                ++$stats['students'];
            }

            if (!$user->isAccepted()) {
                ++$stats['unaccepted'];
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
     * @return array{id: int|null, role: string, fullName: string, email: string, isActive: bool, isAccepted: bool, canDelete: bool, createdAt: \DateTimeImmutable|null, loggedAt: \DateTimeImmutable|null}
     */
    private function normalizeUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'role' => $this->resolveRoleLabel($user->getRoles()),
            'fullName' => $user->getFullName() ?? '-',
            'email' => $user->getEmail() ?? '-',
            'isActive' => $user->isActive(),
            'isAccepted' => $user->isAccepted(),
            'canDelete' => self::PROTECTED_ADMIN_EMAIL !== $user->getEmail(),
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

    /**
     * @param list<string> $roles
     */
    private function resolveEditableRole(array $roles): string
    {
        return match (true) {
            in_array('ROLE_ADMIN', $roles, true) => 'ROLE_ADMIN',
            in_array('ROLE_TEACHER', $roles, true) => 'ROLE_TEACHER',
            in_array('ROLE_STUDENT', $roles, true) => 'ROLE_STUDENT',
            default => 'ROLE_USER',
        };
    }
}
