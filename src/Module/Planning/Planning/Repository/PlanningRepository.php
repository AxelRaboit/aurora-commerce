<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Planning\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Planning\Planning\Entity\Planning;
use Aurora\Module\Planning\Planning\Entity\PlanningInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<PlanningInterface> */
class PlanningRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Planning::class, PlanningInterface::class);
    }

    /** @return list<PlanningInterface> */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('planning')
            ->orderBy('planning.name', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }
}
