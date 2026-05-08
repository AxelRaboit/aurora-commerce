<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Event\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Planning\Event\Entity\PlanningEvent;
use Aurora\Module\Planning\Event\Entity\PlanningEventInterface;
use Aurora\Module\Planning\Planning\Entity\PlanningInterface;
use DateTimeImmutable;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<PlanningEventInterface> */
class PlanningEventRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlanningEvent::class, PlanningEventInterface::class);
    }

    public function findOneBySource(string $sourceType, int $sourceId): ?PlanningEventInterface
    {
        return $this->findOneBy(['sourceType' => $sourceType, 'sourceId' => $sourceId]);
    }

    /** @return list<PlanningEventInterface> */
    public function findInRange(PlanningInterface $planning, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('event')
            ->andWhere('event.planning = :planning')
            ->andWhere('event.startAt < :to')
            ->andWhere('event.endAt > :from')
            ->setParameter('planning', $planning)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('event.startAt', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }
}
