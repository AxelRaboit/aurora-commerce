<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTaskTemplate\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Welding\WorkflowStepTaskTemplate\Entity\WeldingWorkflowStepTaskTemplate;
use Aurora\Module\Welding\WorkflowStepTaskTemplate\Entity\WeldingWorkflowStepTaskTemplateInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<WeldingWorkflowStepTaskTemplateInterface>
 */
class WeldingWorkflowStepTaskTemplateRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WeldingWorkflowStepTaskTemplate::class, WeldingWorkflowStepTaskTemplateInterface::class);
    }
}
