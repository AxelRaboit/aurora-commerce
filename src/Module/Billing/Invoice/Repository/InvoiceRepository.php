<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Repository;

use Aurora\Core\Repository\Trait\PaginationTrait;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Module\Billing\Invoice\Entity\Invoice;
use Aurora\Module\Billing\Invoice\Enum\InvoiceStatusEnum;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Invoice>
 */
class InvoiceRepository extends ServiceEntityRepository
{
    use PaginationTrait;

    public function __construct(
        ManagerRegistry $registry,
        private readonly SequenceGenerator $sequenceGenerator,
    ) {
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
            ->leftJoin('i.tiers', 's')->addSelect('s')
            ->addSelect('CASE WHEN i.status = :priorityStatus THEN 0 ELSE 1 END AS HIDDEN status_priority')
            ->setParameter('priorityStatus', InvoiceStatusEnum::NeedsReview)
            ->orderBy('status_priority', Order::Ascending->value)
            ->addOrderBy('i.issuedAt', Order::Descending->value)
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
        ?int $tiersId = null,
    ): array {
        $queryBuilder = $this->createQueryBuilder('i')
            ->leftJoin('i.tiers', 's')->addSelect('s')
            ->addSelect('CASE WHEN i.status = :priorityStatus THEN 0 ELSE 1 END AS HIDDEN status_priority')
            ->setParameter('priorityStatus', InvoiceStatusEnum::NeedsReview)
            ->orderBy('status_priority', Order::Ascending->value)
            ->addOrderBy('i.issuedAt', Order::Descending->value)
            ->addOrderBy('i.id', Order::Descending->value);
        $countQueryBuilder = $this->createQueryBuilder('i')->select('COUNT(i.id)')->leftJoin('i.tiers', 's');

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

        if (null !== $tiersId) {
            $queryBuilder->andWhere('s.id = :tiersId')->setParameter('tiersId', $tiersId);
            $countQueryBuilder->andWhere('s.id = :tiersId')->setParameter('tiersId', $tiersId);
        }

        return $this->paginate($queryBuilder, $countQueryBuilder, $page, $limit);
    }

    /** Sum of total_gross_cents over all non-draft invoices linked to a tiers. */
    public function sumGrossForTiers(int $tiersId): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COALESCE(SUM(i.totalGrossCents), 0)')
            ->andWhere('IDENTITY(i.tiers) = :tiersId')
            ->andWhere('i.status != :draft')
            ->setParameter('tiersId', $tiersId)
            ->setParameter('draft', InvoiceStatusEnum::Draft)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countForTiers(int $tiersId): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('IDENTITY(i.tiers) = :tiersId')
            ->setParameter('tiersId', $tiersId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Sum of total_gross_cents over invoices issued in the given period (all
     * statuses except draft).
     */
    public function sumGrossInPeriod(DateTimeInterface $from, DateTimeInterface $to): int
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
     * @return list<array{tiersId: int, tiersName: string, total: int}>
     */
    public function topTiers(int $limit = 5): array
    {
        $rows = $this->createQueryBuilder('i')
            ->select('s.id AS tiersId, s.name AS tiersName, SUM(i.totalGrossCents) AS total')
            ->innerJoin('i.tiers', 's')
            ->andWhere('i.status != :draft')
            ->andWhere('i.totalGrossCents IS NOT NULL')
            ->setParameter('draft', InvoiceStatusEnum::Draft)
            ->groupBy('s.id', 's.name')
            ->orderBy('total', Order::Descending->value)
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): array => [
            'tiersId' => (int) $row['tiersId'],
            'tiersName' => (string) $row['tiersName'],
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

    /**
     * Returns invoice numbers grouped by year, for all non-draft invoices with
     * a non-null number. Used by the compliance sequence checker.
     *
     * @return array<int, list<string>> keyed by 4-digit year
     */
    /**
     * Generate the next sequential invoice number using a PostgreSQL sequence.
     * Format: {PREFIX}-{YEAR}-{NNNN} — atomic, no race condition, no gaps.
     */
    public function getNextNumber(string $prefix, int $year): string
    {
        return $this->sequenceGenerator->nextYearly($prefix, $year);
    }

    public function findInvoiceNumbersByYear(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $rows = $conn->executeQuery(
            'SELECT number, EXTRACT(YEAR FROM issued_at) AS year
             FROM billing_invoices
             WHERE number IS NOT NULL
               AND issued_at IS NOT NULL
               AND status NOT IN (:drafts)
             ORDER BY issued_at ASC',
            ['drafts' => ['draft', 'needs_review']],
            ['drafts' => ArrayParameterType::STRING],
        )->fetchAllAssociative();

        $byYear = [];
        foreach ($rows as $row) {
            $byYear[(int) $row['year']][] = $row['number'];
        }

        return $byYear;
    }

    /**
     * Invoices in Validated/Paid status issued more than $years ago that have
     * not yet been archived — flagged by the compliance screen.
     *
     * @return list<array{id: int, number: ?string, issuedAt: string, status: string}>
     */
    public function findOverdueForArchiving(int $years = 6): array
    {
        $threshold = new DateTimeImmutable(sprintf('-%d years', $years));

        $rows = $this->createQueryBuilder('i')
            ->select('i.id', 'i.number', 'i.issuedAt', 'i.status')
            ->where('i.status IN (:statuses)')
            ->andWhere('i.issuedAt < :threshold')
            ->setParameter('statuses', [
                InvoiceStatusEnum::Validated,
                InvoiceStatusEnum::Paid,
            ])
            ->setParameter('threshold', $threshold)
            ->orderBy('i.issuedAt', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $r): array => [
            'id' => $r['id'],
            'number' => $r['number'],
            'issuedAt' => $r['issuedAt']?->format('Y-m-d'),
            'status' => $r['status']->value,
        ], $rows);
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
