<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return array{items: User[], total: int, page: int, totalPages: int}
     */
    public function findPaginatedForAdmin(int $page, ?string $search = null, int $limit = 20): array
    {
        $queryBuilder = $this->createQueryBuilder('u')->orderBy('u.createdAt', 'DESC');
        $countQueryBuilder = $this->createQueryBuilder('u')->select('COUNT(u.id)');

        if ($search) {
            $condition = 'LOWER(u.name) LIKE :search OR LOWER(u.email) LIKE :search';
            $param = '%'.mb_strtolower($search).'%';
            $queryBuilder->andWhere($condition)->setParameter('search', $param);
            $countQueryBuilder->andWhere($condition)->setParameter('search', $param);
        }

        $total = (int) $countQueryBuilder->getQuery()->getSingleScalarResult();
        $totalPages = max(1, (int) ceil($total / $limit));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $limit;

        $items = $queryBuilder->setMaxResults($limit)->setFirstResult($offset)->getQuery()->getResult();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
        ];
    }
}
