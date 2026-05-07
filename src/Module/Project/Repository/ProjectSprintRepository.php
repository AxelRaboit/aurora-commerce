<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Repository;

use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectSprint;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ProjectSprint> */
class ProjectSprintRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectSprint::class);
    }

    /** @return list<ProjectSprint> */
    public function findByProject(Project $project): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.project = :project')
            ->setParameter('project', $project)
            ->orderBy('s.startDate', Order::Descending->value)
            ->getQuery()
            ->getResult();
    }
}
