<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Order\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Core\Repository\Trait\PaginationTrait;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\User\Entity\User;
use Aurora\Module\Ecommerce\Order\Entity\Order;
use Aurora\Module\Ecommerce\Order\Entity\OrderInterface;
use Aurora\Module\Ecommerce\Order\Enum\OrderStatusEnum;
use Doctrine\Common\Collections\Order as SortOrder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<OrderInterface> */
class OrderRepository extends ResolveTargetEntityRepository
{
    use PaginationTrait;

    public function __construct(
        ManagerRegistry $registry,
        private readonly SequenceGenerator $sequenceGenerator,
    ) {
        parent::__construct($registry, Order::class, OrderInterface::class);
    }

    public function findOneByToken(string $token): ?OrderInterface
    {
        return $this->findOneBy(['token' => $token]);
    }

    public function findPaginated(int $page, int $limit = 20, ?string $search = null, ?OrderStatusEnum $status = null): array
    {
        $qb = $this->createQueryBuilder('o')->orderBy('o.createdAt', SortOrder::Descending->value);
        $countQb = $this->createQueryBuilder('o')->select('COUNT(o.id)');

        if (null !== $search && '' !== $search) {
            $pattern = '%'.mb_strtolower($search).'%';
            $qb->andWhere('LOWER(o.number) LIKE :search OR LOWER(o.email) LIKE :search OR LOWER(o.name) LIKE :search')
                ->setParameter('search', $pattern);
            $countQb->andWhere('LOWER(o.number) LIKE :search OR LOWER(o.email) LIKE :search OR LOWER(o.name) LIKE :search')
                ->setParameter('search', $pattern);
        }

        if ($status instanceof OrderStatusEnum) {
            $qb->andWhere('o.status = :status')->setParameter('status', $status);
            $countQb->andWhere('o.status = :status')->setParameter('status', $status);
        }

        return $this->paginate($qb, $countQb, $page, $limit);
    }

    /** @return array<string, int> status value → count */
    public function countByStatus(): array
    {
        $rows = $this->createQueryBuilder('o')
            ->select('o.status AS status, COUNT(o.id) AS total')
            ->groupBy('o.status')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['status']->value] = (int) $row['total'];
        }

        return $counts;
    }

    public function findPaginatedForCustomer(User $customer, int $page, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('o')
            ->andWhere('o.customer = :customer')->setParameter('customer', $customer)
            ->orderBy('o.createdAt', SortOrder::Descending->value);

        $countQb = $this->createQueryBuilder('o')->select('COUNT(o.id)')
            ->andWhere('o.customer = :customer')->setParameter('customer', $customer);

        return $this->paginate($qb, $countQb, $page, $limit);
    }

    /**
     * Generate the next order number using a PostgreSQL sequence.
     * Format: {PREFIX}-{NNNNNN} — atomic, no race condition, no gaps.
     */
    public function getNextOrderNumber(string $prefix = 'ORD'): string
    {
        return $this->sequenceGenerator->next($prefix);
    }
}
