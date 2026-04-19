<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tag;
use App\Repository\Trait\PaginationTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tag>
 */
class TagRepository extends ServiceEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    public function findPaginated(int $page, int $limit = 20, ?string $search = null): array
    {
        $queryBuilder = $this->createQueryBuilder('t')->orderBy('t.createdAt', Order::Descending->value);
        $countQueryBuilder = $this->createQueryBuilder('t')->select('COUNT(t.id)');

        if ($search) {
            $condition = 'LOWER(t.name) LIKE :search';
            $param = '%'.mb_strtolower($search).'%';
            $queryBuilder->andWhere($condition)->setParameter('search', $param);
            $countQueryBuilder->andWhere($condition)->setParameter('search', $param);
        }

        return $this->paginate($queryBuilder, $countQueryBuilder, $page, $limit);
    }
}
