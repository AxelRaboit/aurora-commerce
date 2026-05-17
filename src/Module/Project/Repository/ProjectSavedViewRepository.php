<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectSavedView;
use Aurora\Module\Project\Entity\ProjectSavedViewInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<ProjectSavedViewInterface> */
class ProjectSavedViewRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectSavedView::class, ProjectSavedViewInterface::class);
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
