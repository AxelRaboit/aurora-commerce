<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepPdfTemplate\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Welding\WorkflowStepPdfTemplate\Entity\WorkflowStepPdfTemplate;
use Aurora\Module\Welding\WorkflowStepPdfTemplate\Entity\WorkflowStepPdfTemplateInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<WorkflowStepPdfTemplateInterface>
 */
class WorkflowStepPdfTemplateRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkflowStepPdfTemplate::class, WorkflowStepPdfTemplateInterface::class);
    }
}
