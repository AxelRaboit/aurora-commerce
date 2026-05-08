<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Deal\Repository;

use Aurora\Core\Repository\Trait\PaginationTrait;
use Aurora\Module\Crm\Deal\Entity\Deal;
use Aurora\Module\Crm\Deal\Entity\DealInterface;
use Aurora\Module\Crm\Deal\Enum\DealStageEnum;
use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<DealInterface> */
class DealRepository extends ResolveTargetEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Deal::class, DealInterface::class);
    }

    public function findPaginated(int $page, int $limit = 20, ?string $search = null, ?DealStageEnum $stage = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.contact', 'c')
            ->leftJoin('d.company', 'co')
            ->addSelect('c', 'co')
            ->orderBy('d.createdAt', Order::Descending->value);

        $countQb = $this->createQueryBuilder('d')->select('COUNT(d.id)');

        if (null !== $search && '' !== $search) {
            $pattern = '%'.mb_strtolower($search).'%';
            $qb->andWhere('LOWER(d.name) LIKE :search')->setParameter('search', $pattern);
            $countQb->andWhere('LOWER(d.name) LIKE :search')->setParameter('search', $pattern);
        }

        if ($stage instanceof DealStageEnum) {
            $qb->andWhere('d.stage = :stage')->setParameter('stage', $stage);
            $countQb->andWhere('d.stage = :stage')->setParameter('stage', $stage);
        }

        return $this->paginate($qb, $countQb, $page, $limit);
    }

    /** @return array<string, int> stage value -> count */
    public function countByStage(): array
    {
        $rows = $this->createQueryBuilder('d')
            ->select('d.stage AS stage, COUNT(d.id) AS total')
            ->groupBy('d.stage')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['stage']->value] = (int) $row['total'];
        }

        return $counts;
    }

    public function getTotalValue(?DealStageEnum $stage = null): float
    {
        $qb = $this->createQueryBuilder('d')->select('COALESCE(SUM(d.value), 0) AS total');
        if ($stage instanceof DealStageEnum) {
            $qb->andWhere('d.stage = :stage')->setParameter('stage', $stage);
        }

        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    /** @return list<Deal> */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('d')
            ->orderBy('d.name', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }
}
