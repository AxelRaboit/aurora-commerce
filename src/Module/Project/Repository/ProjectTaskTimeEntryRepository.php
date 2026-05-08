<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Repository;

use Aurora\Module\Project\Entity\ProjectTask;
use Aurora\Module\Project\Entity\ProjectTaskTimeEntry;
use Aurora\Module\Project\Entity\ProjectTaskTimeEntryInterface;
use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<ProjectTaskTimeEntryInterface> */
class ProjectTaskTimeEntryRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectTaskTimeEntry::class, ProjectTaskTimeEntryInterface::class);
    }

    public function totalMinutesForTask(ProjectTask $task): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COALESCE(SUM(e.minutes), 0)')
            ->andWhere('e.task = :task')
            ->setParameter('task', $task)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
