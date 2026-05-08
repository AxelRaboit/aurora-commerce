<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Repository;

use Aurora\Module\Project\Entity\ProjectTaskComment;
use Aurora\Module\Project\Entity\ProjectTaskCommentInterface;
use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<ProjectTaskCommentInterface> */
class ProjectTaskCommentRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectTaskComment::class, ProjectTaskCommentInterface::class);
    }
}
