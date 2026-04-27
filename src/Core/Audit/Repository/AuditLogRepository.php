<?php

declare(strict_types=1);

namespace Aurora\Core\Audit\Repository;

use Aurora\Core\Audit\Entity\AuditLog;
use Aurora\Core\Repository\Trait\PaginationTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<AuditLog> */
class AuditLogRepository extends ServiceEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }

    public function findPaginated(int $page, int $limit = 50, ?string $module = null): array
    {
        $qb = $this->createQueryBuilder('a')->orderBy('a.createdAt', Order::Descending->value);
        $countQb = $this->createQueryBuilder('a')->select('COUNT(a.id)');

        if (null !== $module) {
            $qb->andWhere('a.module = :module')->setParameter('module', $module);
            $countQb->andWhere('a.module = :module')->setParameter('module', $module);
        }

        return $this->paginate($qb, $countQb, $page, $limit);
    }

    public function findPaginatedForEntity(string $entityType, int $entityId, int $page, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.entityType = :type')->setParameter('type', $entityType)
            ->andWhere('a.entityId = :id')->setParameter('id', $entityId)
            ->orderBy('a.createdAt', Order::Descending->value);

        $countQb = $this->createQueryBuilder('a')->select('COUNT(a.id)')
            ->andWhere('a.entityType = :type')->setParameter('type', $entityType)
            ->andWhere('a.entityId = :id')->setParameter('id', $entityId);

        return $this->paginate($qb, $countQb, $page, $limit);
    }

    /** @return array<int, string> */
    public function findDistinctModules(): array
    {
        $rows = $this->createQueryBuilder('a')
            ->select('DISTINCT a.module')
            ->orderBy('a.module', Order::Ascending->value)
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn (array $row): string => (string) $row['module'], $rows);
    }
}
