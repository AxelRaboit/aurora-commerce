<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowTemplate\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Welding\Enum\WeldingWorkflowTemplateStatusEnum;
use Aurora\Module\Welding\WorkflowTemplate\Entity\WeldingWorkflowTemplate;
use Aurora\Module\Welding\WorkflowTemplate\Entity\WeldingWorkflowTemplateInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<WeldingWorkflowTemplateInterface>
 */
class WeldingWorkflowTemplateRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WeldingWorkflowTemplate::class, WeldingWorkflowTemplateInterface::class);
    }

    /** @return WeldingWorkflowTemplateInterface[] */
    public function findAllForIndex(): array
    {
        return $this->createQueryBuilder('workflowTemplate')
            ->orderBy('workflowTemplate.title', Order::Ascending->value)
            ->addOrderBy('workflowTemplate.version', Order::Descending->value)
            ->getQuery()
            ->getResult();
    }

    /** @return WeldingWorkflowTemplateInterface[] */
    public function findAllByStatus(WeldingWorkflowTemplateStatusEnum $status): array
    {
        return $this->createQueryBuilder('workflowTemplate')
            ->andWhere('workflowTemplate.status = :status')
            ->setParameter('status', $status)
            ->orderBy('workflowTemplate.title', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }
}
