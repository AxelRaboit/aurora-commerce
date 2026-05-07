<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Repository;

use Aurora\Core\User\Entity\User;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectSavedView;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ProjectSavedView> */
class ProjectSavedViewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectSavedView::class);
    }

    /** @return list<ProjectSavedView> */
    public function findForUserAndProject(User $user, Project $project): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.owner = :user')
            ->andWhere('v.project = :project')
            ->setParameter('user', $user)
            ->setParameter('project', $project)
            ->orderBy('v.name', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }
}
