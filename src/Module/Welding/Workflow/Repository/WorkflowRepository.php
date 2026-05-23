<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Workflow\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Welding\Workflow\Entity\Workflow;
use Aurora\Module\Welding\Workflow\Entity\WorkflowInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<WorkflowInterface>
 */
class WorkflowRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Workflow::class, WorkflowInterface::class);
    }

    /** @return WorkflowInterface[] */
    public function findAllForIndex(): array
    {
        return $this->createQueryBuilder('workflow')
            ->addSelect('template', 'assignee')
            ->leftJoin('workflow.template', 'template')
            ->leftJoin('workflow.assignee', 'assignee')
            ->orderBy('workflow.createdAt', Order::Descending->value)
            ->getQuery()
            ->getResult();
    }

    /** @return WorkflowInterface[] */
    public function findAllAssignedTo(int $employeeId): array
    {
        return $this->createQueryBuilder('workflow')
            ->andWhere('IDENTITY(workflow.assignee) = :employeeId')
            ->setParameter('employeeId', $employeeId)
            ->orderBy('workflow.createdAt', Order::Descending->value)
            ->getQuery()
            ->getResult();
    }
}
