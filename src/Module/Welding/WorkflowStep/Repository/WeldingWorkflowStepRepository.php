<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStep\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Welding\WorkflowStep\Entity\WeldingWorkflowStep;
use Aurora\Module\Welding\WorkflowStep\Entity\WeldingWorkflowStepInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<WeldingWorkflowStepInterface>
 */
class WeldingWorkflowStepRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WeldingWorkflowStep::class, WeldingWorkflowStepInterface::class);
    }
}
