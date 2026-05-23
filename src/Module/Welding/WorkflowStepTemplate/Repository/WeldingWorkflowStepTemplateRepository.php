<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTemplate\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WeldingWorkflowStepTemplate;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WeldingWorkflowStepTemplateInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<WeldingWorkflowStepTemplateInterface>
 */
class WeldingWorkflowStepTemplateRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WeldingWorkflowStepTemplate::class, WeldingWorkflowStepTemplateInterface::class);
    }
}
