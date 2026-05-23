<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStep\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Welding\WorkflowStep\Entity\WorkflowStep;
use Aurora\Module\Welding\WorkflowStep\Entity\WorkflowStepInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<WorkflowStepInterface>
 */
class WorkflowStepRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkflowStep::class, WorkflowStepInterface::class);
    }
}
