<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\WorkflowTemplate\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Welding\Enum\WorkflowTemplateStatusEnum;
use Aurora\Module\Welding\WorkflowTemplate\Entity\WorkflowTemplate;
use Aurora\Module\Welding\WorkflowTemplate\Entity\WorkflowTemplateInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<WorkflowTemplateInterface>
 */
class WorkflowTemplateRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkflowTemplate::class, WorkflowTemplateInterface::class);
    }

    /** @return WorkflowTemplateInterface[] */
    public function findAllForIndex(): array
    {
        return $this->createQueryBuilder('workflowTemplate')
            ->orderBy('workflowTemplate.title', Order::Ascending->value)
            ->addOrderBy('workflowTemplate.version', Order::Descending->value)
            ->getQuery()
            ->getResult();
    }

    /** @return WorkflowTemplateInterface[] */
    public function findAllByStatus(WorkflowTemplateStatusEnum $status): array
    {
        return $this->createQueryBuilder('workflowTemplate')
            ->andWhere('workflowTemplate.status = :status')
            ->setParameter('status', $status)
            ->orderBy('workflowTemplate.title', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }
}
