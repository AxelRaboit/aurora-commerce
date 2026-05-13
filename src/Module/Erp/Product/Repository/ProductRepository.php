<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\Product\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Core\Repository\Trait\PaginationTrait;
use Aurora\Module\Erp\Product\Entity\Product;
use Aurora\Module\Erp\Product\Entity\ProductInterface;
use Aurora\Module\Erp\Product\Enum\ProductStatusEnum;
use Aurora\Module\Erp\Product\Enum\ProductTypeEnum;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<ProductInterface>
 */
class ProductRepository extends ResolveTargetEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class, ProductInterface::class);
    }

    public function findPaginated(int $page, int $limit = 20, ?string $search = null, ?ProductStatusEnum $status = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.image', 'img')
            ->addSelect('img')
            ->orderBy('p.name', Order::Ascending->value);
        $countQb = $this->createQueryBuilder('p')->select('COUNT(p.id)');

        if (null !== $search && '' !== $search) {
            $pattern = '%'.mb_strtolower($search).'%';
            $qb->andWhere('LOWER(p.name) LIKE :search OR LOWER(p.reference) LIKE :search')
                ->setParameter('search', $pattern);
            $countQb->andWhere('LOWER(p.name) LIKE :search OR LOWER(p.reference) LIKE :search')
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

    /** @return array<string, int> type value -> count */
    public function countByType(): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('p.type AS type, COUNT(p.id) AS total')
            ->groupBy('p.type')
            ->getQuery()
            ->getArrayResult();

        $counts = array_fill_keys(array_column(ProductTypeEnum::cases(), 'value'), 0);
        foreach ($rows as $row) {
            $counts[$row['type']->value] = (int) $row['total'];
        }

        return $counts;
    }

    public function countOutOfStock(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.status = :active')
            ->andWhere('p.stockQuantity = 0')
            ->setParameter('active', ProductStatusEnum::Active)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findOneByReference(string $reference): ?ProductInterface
    {
        return $this->findOneBy(['reference' => $reference]);
    }
}
