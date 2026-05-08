<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Project\Entity\ProjectTaskItem;
use Aurora\Module\Project\Entity\ProjectTaskItemInterface;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<ProjectTaskItemInterface> */
class ProjectTaskItemRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectTaskItem::class, ProjectTaskItemInterface::class);
    }
}
