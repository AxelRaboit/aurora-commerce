<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowStepPdfTemplate\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Welding\WorkflowStepPdfTemplate\Entity\WeldingWorkflowStepPdfTemplate;
use Aurora\Module\Welding\WorkflowStepPdfTemplate\Entity\WeldingWorkflowStepPdfTemplateInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<WeldingWorkflowStepPdfTemplateInterface>
 */
class WeldingWorkflowStepPdfTemplateRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WeldingWorkflowStepPdfTemplate::class, WeldingWorkflowStepPdfTemplateInterface::class);
    }
}
