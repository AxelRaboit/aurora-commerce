<?php

declare(strict_types=1);

namespace Aurora\Module\Platform\User\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Core\Repository\Trait\PaginationTrait;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Platform\User\Enum\UserTypeEnum;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<CoreUserInterface>
 */
class UserRepository extends ResolveTargetEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class, CoreUserInterface::class);
    }

    /**
     * @return array{items: CoreUserInterface[], total: int, page: int, totalPages: int}
     */
    public function findPaginatedForAdmin(int $page, ?string $search = null, int $limit = 20): array
    {
        $queryBuilder = $this->createQueryBuilder('u')->orderBy('u.createdAt', Order::Descending->value);
        $countQueryBuilder = $this->createQueryBuilder('u')->select('COUNT(u.id)');

        if ($search) {
            $condition = 'LOWER(u.name) LIKE :search OR LOWER(u.email) LIKE :search';
            $param = '%'.mb_strtolower($search).'%';
            $queryBuilder->andWhere($condition)->setParameter('search', $param);
            $countQueryBuilder->andWhere($condition)->setParameter('search', $param);
        }

        return $this->paginate($queryBuilder, $countQueryBuilder, $page, $limit);
    }

    /**
     * @return array{items: CoreUserInterface[], total: int, page: int, totalPages: int}
     */
    public function findPaginated(int $page, int $limit = 20, ?string $search = null, ?string $role = null): array
    {
        $queryBuilder = $this->createQueryBuilder('u')->orderBy('u.createdAt', Order::Descending->value);
        $countQueryBuilder = $this->createQueryBuilder('u')->select('COUNT(u.id)');

        if (null !== $search && '' !== $search) {
            $condition = 'LOWER(u.name) LIKE :search OR LOWER(u.email) LIKE :search';
            $param = '%'.mb_strtolower($search).'%';
            $queryBuilder->andWhere($condition)->setParameter('search', $param);
            $countQueryBuilder->andWhere($condition)->setParameter('search', $param);
        }

        if (null !== $role && '' !== $role) {
            $matchingIds = $this->findIdsWithRole($role);
            if ([] === $matchingIds) {
                return ['items' => [], 'total' => 0, 'page' => max(1, $page), 'totalPages' => 1];
            }

            $queryBuilder->andWhere('u.id IN (:matchingIds)')->setParameter('matchingIds', $matchingIds);
            $countQueryBuilder->andWhere('u.id IN (:matchingIds)')->setParameter('matchingIds', $matchingIds);
        }

        return $this->paginate($queryBuilder, $countQueryBuilder, $page, $limit);
    }

    /**
     * Number of users with the given role + type. Used by the profile page
     * to refuse self-deletion when the user would be the last dev/admin.
     */
    public function countByRoleAndType(string $role, UserTypeEnum $type): int
    {
        $sql = 'SELECT COUNT(*) FROM core_users WHERE type = :type AND roles::text LIKE :role';

        return (int) $this->getEntityManager()->getConnection()->fetchOne($sql, [
            'type' => $type->value,
            'role' => '%"'.$role.'"%',
        ]);
    }

    /**
     * @return list<int>
     */
    private function findIdsWithRole(string $role): array
    {
        $sql = 'SELECT id FROM core_users WHERE roles::text LIKE :role';
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative($sql, [
            'role' => '%"'.$role.'"%',
        ]);

        return array_map(static fn (array $row): int => (int) $row['id'], $rows);
    }

    public function findByInvitationSelector(string $selector): ?CoreUserInterface
    {
        return $this->findOneBy(['invitationSelector' => $selector]);
    }

    /** @return list<CoreUserInterface> */
    public function findAllAdminsAlphabetical(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.type = :type')
            ->setParameter('type', UserTypeEnum::Backend->value)
            ->orderBy('u.name', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }
}
