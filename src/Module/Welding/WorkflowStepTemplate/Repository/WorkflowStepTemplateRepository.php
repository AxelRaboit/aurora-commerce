<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepTemplate\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WorkflowStepTemplate;
use Aurora\Module\Welding\WorkflowStepTemplate\Entity\WorkflowStepTemplateInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<WorkflowStepTemplateInterface>
 */
class WorkflowStepTemplateRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkflowStepTemplate::class, WorkflowStepTemplateInterface::class);
    }
}
