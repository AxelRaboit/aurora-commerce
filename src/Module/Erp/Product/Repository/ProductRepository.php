<?php

declare(strict_types=1);

namespace App\Module\Erp\Product\Repository;

use App\Core\Repository\Trait\PaginationTrait;
use App\Module\Erp\Product\Entity\Product;
use App\Module\Erp\Product\Enum\ProductStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findPaginated(int $page, int $limit = 20, ?string $search = null, ?ProductStatusEnum $status = null): array
    {
        $qb = $this->createQueryBuilder('p')->orderBy('p.name', Order::Ascending->value);
        $countQb = $this->createQueryBuilder('p')->select('COUNT(p.id)');

        if (null !== $search && '' !== $search) {
            $pattern = '%'.mb_strtolower($search).'%';
            $qb->andWhere('LOWER(p.name) LIKE :search OR LOWER(p.sku) LIKE :search')
                ->setParameter('search', $pattern);
            $countQb->andWhere('LOWER(p.name) LIKE :search OR LOWER(p.sku) LIKE :search')
                ->setParameter('search', $pattern);
        }

        if ($status instanceof ProductStatusEnum) {
            $qb->andWhere('p.status = :status')->setParameter('status', $status);
            $countQb->andWhere('p.status = :status')->setParameter('status', $status);
        }

        return $this->paginate($qb, $countQb, $page, $limit);
    }

    /** @return array<string, int> status value -> count */
    public function countByStatus(): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('p.status AS status, COUNT(p.id) AS total')
            ->groupBy('p.status')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['status']->value] = (int) $row['total'];
        }

        return $counts;
    }

    public function getTotalInventoryCents(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COALESCE(SUM(p.priceCents), 0) AS total')
            ->andWhere('p.status != :archived')->setParameter('archived', ProductStatusEnum::Archived)
            ->getQuery()->getSingleScalarResult();
    }

    public function findOneBySku(string $sku): ?Product
    {
        return $this->findOneBy(['sku' => $sku]);
    }
}
