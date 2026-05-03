<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Repository;

use Aurora\Core\Repository\Trait\PaginationTrait;
use Aurora\Module\Billing\Invoice\Entity\Invoice;
use Aurora\Module\Billing\Invoice\Enum\InvoiceStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Invoice>
 */
class InvoiceRepository extends ServiceEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    /**
     * Iterator over all invoices matching the same filter as findPaginated,
     * but unbounded — used by streaming exports. Returns a generator to keep
     * memory flat regardless of dataset size.
     *
     * @return iterable<Invoice>
     */
    public function findAllMatching(?string $search, ?InvoiceStatusEnum $status): iterable
    {
        $queryBuilder = $this->createQueryBuilder('i')
            ->leftJoin('i.supplier', 's')->addSelect('s')
            ->orderBy('i.issuedAt', Order::Descending->value)
            ->addOrderBy('i.id', Order::Descending->value);

        if (null !== $search && '' !== $search) {
            $pattern = '%'.mb_strtolower($search).'%';
            $queryBuilder->andWhere('LOWER(i.number) LIKE :search OR LOWER(s.name) LIKE :search')
                ->setParameter('search', $pattern);
        }
        if ($status instanceof InvoiceStatusEnum) {
            $queryBuilder->andWhere('i.status = :status')->setParameter('status', $status);
        }

        return $queryBuilder->getQuery()->toIterable();
    }

    public function findPaginated(
        int $page,
        int $limit = 20,
        ?string $search = null,
        ?InvoiceStatusEnum $status = null,
        ?int $supplierId = null,
    ): array {
        $queryBuilder = $this->createQueryBuilder('i')
            ->leftJoin('i.supplier', 's')->addSelect('s')
            ->orderBy('i.issuedAt', Order::Descending->value)
            ->addOrderBy('i.id', Order::Descending->value);
        $countQueryBuilder = $this->createQueryBuilder('i')->select('COUNT(i.id)')->leftJoin('i.supplier', 's');

        if (null !== $search && '' !== $search) {
            $pattern = '%'.mb_strtolower($search).'%';
            $queryBuilder->andWhere('LOWER(i.number) LIKE :search OR LOWER(s.name) LIKE :search')
                ->setParameter('search', $pattern);
            $countQueryBuilder->andWhere('LOWER(i.number) LIKE :search OR LOWER(s.name) LIKE :search')
                ->setParameter('search', $pattern);
        }

        if ($status instanceof InvoiceStatusEnum) {
            $queryBuilder->andWhere('i.status = :status')->setParameter('status', $status);
            $countQueryBuilder->andWhere('i.status = :status')->setParameter('status', $status);
        }

        if (null !== $supplierId) {
            $queryBuilder->andWhere('s.id = :supplierId')->setParameter('supplierId', $supplierId);
            $countQueryBuilder->andWhere('s.id = :supplierId')->setParameter('supplierId', $supplierId);
        }

        return $this->paginate($queryBuilder, $countQueryBuilder, $page, $limit);
    }

    /** Sum of total_gross_cents over all non-draft invoices linked to a supplier. */
    public function sumGrossForSupplier(int $supplierId): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COALESCE(SUM(i.totalGrossCents), 0)')
            ->andWhere('IDENTITY(i.supplier) = :supplierId')
            ->andWhere('i.status != :draft')
            ->setParameter('supplierId', $supplierId)
            ->setParameter('draft', InvoiceStatusEnum::Draft)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countForSupplier(int $supplierId): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('IDENTITY(i.supplier) = :supplierId')
            ->setParameter('supplierId', $supplierId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Sum of total_gross_cents over invoices issued in the given period (all
     * statuses except draft).
     */
    public function sumGrossInPeriod(\DateTimeInterface $from, \DateTimeInterface $to): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COALESCE(SUM(i.totalGrossCents), 0)')
            ->andWhere('i.issuedAt BETWEEN :from AND :to')
            ->andWhere('i.status != :draft')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('draft', InvoiceStatusEnum::Draft)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<array{supplierId: int, supplierName: string, total: int}>
     */
    public function topSuppliers(int $limit = 5): array
    {
        $rows = $this->createQueryBuilder('i')
            ->select('s.id AS supplierId, s.name AS supplierName, SUM(i.totalGrossCents) AS total')
            ->innerJoin('i.supplier', 's')
            ->andWhere('i.status != :draft')
            ->andWhere('i.totalGrossCents IS NOT NULL')
            ->setParameter('draft', InvoiceStatusEnum::Draft)
            ->groupBy('s.id', 's.name')
            ->orderBy('total', Order::Descending->value)
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): array => [
            'supplierId' => (int) $row['supplierId'],
            'supplierName' => (string) $row['supplierName'],
            'total' => (int) $row['total'],
        ], $rows);
    }

    /**
     * Most recently created invoices flagged as needing human review,
     * used to populate the dashboard queue.
     *
     * @return list<Invoice>
     */
    public function findRecentNeedingReview(int $limit = 5): array
    {
        return $this->findBy(
            ['status' => InvoiceStatusEnum::NeedsReview],
            ['createdAt' => Order::Descending->value],
            $limit,
        );
    }

    /** @return array<string, int> */
    public function countByStatus(): array
    {
        $rows = $this->createQueryBuilder('i')
            ->select('i.status AS status, COUNT(i.id) AS total')
            ->groupBy('i.status')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['status']->value] = (int) $row['total'];
        }

        return $counts;
    }
}
