<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Project\Entity\ProjectColumn;
use Aurora\Module\Project\Entity\ProjectColumnInterface;
use Aurora\Module\Project\Entity\ProjectInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<ProjectColumnInterface> */
class ProjectColumnRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectColumn::class, ProjectColumnInterface::class);
    }

    /** @return list<ProjectColumnInterface> */
    public function findByProject(ProjectInterface $project): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.project = :project')
            ->setParameter('project', $project)
            ->orderBy('c.position', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }
}
