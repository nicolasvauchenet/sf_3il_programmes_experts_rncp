<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/administration/utilisateurs', name: 'app_admin_users_')]
final class UsersController extends AbstractController
{
    #[Route(name: 'home')]
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findBy([], ['fullName' => 'ASC', 'email' => 'ASC']);

        return $this->render('admin/users/index.html.twig', [
            'users' => array_map($this->normalizeUser(...), $users),
            'stats' => $this->buildStats($users),
        ]);
    }

    /**
     * @param list<User> $users
     *
     * @return array{total: int, teachers: int, students: int, inactive: int}
     */
    private function buildStats(array $users): array
    {
        $stats = [
            'total' => count($users),
            'teachers' => 0,
            'students' => 0,
            'inactive' => 0,
        ];

        foreach ($users as $user) {
            $roles = $user->getRoles();

            if (in_array('ROLE_TEACHER', $roles, true)) {
                ++$stats['teachers'];
            }

            if (in_array('ROLE_STUDENT', $roles, true)) {
                ++$stats['students'];
            }

            if (null === $user->getLoggedAt()) {
                ++$stats['inactive'];
            }
        }

        return $stats;
    }

    /**
     * @return array{role: string, fullName: string, email: string, createdAt: \DateTimeImmutable|null, loggedAt: \DateTimeImmutable|null}
     */
    private function normalizeUser(User $user): array
    {
        return [
            'role' => $this->resolveRoleLabel($user->getRoles()),
            'fullName' => $user->getFullName() ?? '-',
            'email' => $user->getEmail() ?? '-',
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
