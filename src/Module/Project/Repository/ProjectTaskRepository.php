<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Repository;

use Aurora\Core\User\Entity\User;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectTask;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ProjectTask> */
class ProjectTaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectTask::class);
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

    /** @return list<ProjectTask> */
    public function findByProject(Project $project): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.project = :project')
            ->setParameter('project', $project)
            ->orderBy('t.position', Order::Ascending->value)
            ->addOrderBy('t.createdAt', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }

    /** @return list<ProjectTask> */
    public function findByAssignee(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.assignee = :user')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', Order::Descending->value)
            ->getQuery()
            ->getResult();
    }
}
