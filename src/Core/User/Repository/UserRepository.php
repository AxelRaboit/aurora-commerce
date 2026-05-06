<?php

declare(strict_types=1);

namespace Aurora\Core\User\Repository;

use Aurora\Core\Repository\Trait\PaginationTrait;
use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Enum\UserTypeEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return array{items: User[], total: int, page: int, totalPages: int}
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
     * @return array{items: User[], total: int, page: int, totalPages: int}
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
     * @return list<int>
     */
    private function findIdsWithRole(string $role): array
    {
        $sql = 'SELECT id FROM users WHERE roles::text LIKE :role';
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative($sql, [
            'role' => '%"'.$role.'"%',
        ]);

        return array_map(static fn (array $row): int => (int) $row['id'], $rows);
    }

    public function findByInvitationSelector(string $selector): ?User
    {
        return $this->findOneBy(['invitationSelector' => $selector]);
    }

    /** @return list<User> */
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
