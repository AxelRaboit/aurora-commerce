<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Project\Entity\ProjectInterface;
use Aurora\Module\Project\Entity\ProjectTask;
use Aurora\Module\Project\Entity\ProjectTaskInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<ProjectTaskInterface> */
class ProjectTaskRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectTask::class, ProjectTaskInterface::class);
    }

    /** @return list<ProjectTask> */
    public function searchByTitle(string $query, int $limit = 8): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.project', 'p')
            ->addSelect('p')
            ->andWhere('LOWER(t.title) LIKE :q')
            ->setParameter('q', '%'.mb_strtolower($query).'%')
            ->orderBy('t.createdAt', Order::Descending->value)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** @return list<ProjectTaskInterface> */
    public function findByProject(ProjectInterface $project): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.project = :project')
            ->setParameter('project', $project)
            ->orderBy('t.position', Order::Ascending->value)
            ->addOrderBy('t.createdAt', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }

    /** @return list<ProjectTaskInterface> */
    public function findByAssignee(CoreUserInterface $user): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.assignee = :user')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', Order::Descending->value)
            ->getQuery()
            ->getResult();
    }
}
