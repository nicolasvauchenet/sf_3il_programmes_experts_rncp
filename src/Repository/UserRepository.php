<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public const ADMIN_SORTS = ['role', 'fullName', 'email', 'createdAt', 'loggedAt'];
    public const ADMIN_PAGE_SIZES = ['10', '20', '50', '100', 'all'];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @param array{role: string, status: string} $filters
     *
     * @return array{items: list<User>, total: int, page: int, pages: int, perPage: int|'all'}
     */
    public function findForAdminList(
        array $filters,
        string $sort,
        string $direction,
        int|string $perPage,
        int $page,
    ): array {
        $users = $this->findBy([], ['fullName' => 'ASC', 'email' => 'ASC']);
        $users = $this->filterForAdminList($users, $filters);
        $users = $this->sortForAdminList($users, $sort, $direction);

        $total = count($users);

        if ('all' === $perPage) {
            return [
                'items' => $users,
                'total' => $total,
                'page' => 1,
                'pages' => 1,
                'perPage' => 'all',
            ];
        }

        $perPage = max(1, $perPage);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));

        return [
            'items' => array_slice($users, ($page - 1) * $perPage, $perPage),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'perPage' => $perPage,
        ];
    }

    /**
     * @param list<User> $users
     * @param array{role: string, status: string} $filters
     *
     * @return list<User>
     */
    private function filterForAdminList(array $users, array $filters): array
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
     * @return list<User>
     */
    private function sortForAdminList(array $users, string $sort, string $direction): array
    {
        usort($users, function (User $left, User $right) use ($sort, $direction): int {
            $leftValue = $this->resolveSortValue($left, $sort);
            $rightValue = $this->resolveSortValue($right, $sort);

            if (null === $leftValue && null === $rightValue) {
                return 0;
            }

            if (null === $leftValue) {
                return 1;
            }

            if (null === $rightValue) {
                return -1;
            }

            $comparison = $leftValue <=> $rightValue;

            return 'desc' === $direction ? -$comparison : $comparison;
        });

        return $users;
    }

    private function resolveSortValue(User $user, string $sort): int|string|null
    {
        return match ($sort) {
            'role' => $this->resolveRoleLabel($user->getRoles()),
            'email' => strtolower((string) $user->getEmail()),
            'createdAt' => $user->getCreatedAt()?->getTimestamp(),
            'loggedAt' => $user->getLoggedAt()?->getTimestamp(),
            default => strtolower((string) $user->getFullName()),
        };
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
