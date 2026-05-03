<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Repository;

use Aurora\Core\Repository\Trait\PaginationTrait;
use Aurora\Module\Billing\Invoice\Entity\Supplier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Supplier>
 */
class SupplierRepository extends ServiceEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Supplier::class);
    }

    public function findPaginated(int $page, int $limit = 20, ?string $search = null): array
    {
        $queryBuilder = $this->createQueryBuilder('s')->orderBy('s.name', Order::Ascending->value);
        $countQueryBuilder = $this->createQueryBuilder('s')->select('COUNT(s.id)');

        if (null !== $search && '' !== $search) {
            $pattern = '%'.mb_strtolower($search).'%';
            $queryBuilder->andWhere('LOWER(s.name) LIKE :search OR LOWER(s.vatNumber) LIKE :search')
                ->setParameter('search', $pattern);
            $countQueryBuilder->andWhere('LOWER(s.name) LIKE :search OR LOWER(s.vatNumber) LIKE :search')
                ->setParameter('search', $pattern);
        }

        return $this->paginate($queryBuilder, $countQueryBuilder, $page, $limit);
    }

    public function findOneByVatNumber(string $vatNumber): ?Supplier
    {
        return $this->findOneBy(['vatNumber' => $vatNumber]);
    }

    public function findOneByNameLike(string $name): ?Supplier
    {
        return $this->createQueryBuilder('s')
            ->andWhere('LOWER(s.name) = LOWER(:name)')
            ->setParameter('name', $name)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
