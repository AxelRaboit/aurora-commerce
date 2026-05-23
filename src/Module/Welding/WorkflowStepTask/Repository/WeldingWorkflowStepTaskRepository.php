<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTask\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Welding\WorkflowStepTask\Entity\WeldingWorkflowStepTask;
use Aurora\Module\Welding\WorkflowStepTask\Entity\WeldingWorkflowStepTaskInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<WeldingWorkflowStepTaskInterface>
 */
class WeldingWorkflowStepTaskRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WeldingWorkflowStepTask::class, WeldingWorkflowStepTaskInterface::class);
    }
}
