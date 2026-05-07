<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Repository;

use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectLabel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ProjectLabel> */
class ProjectLabelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectLabel::class);
    }

    /** @return list<ProjectLabel> */
    public function findByProject(Project $project): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.project = :project')
            ->setParameter('project', $project)
            ->orderBy('l.name', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }
}
