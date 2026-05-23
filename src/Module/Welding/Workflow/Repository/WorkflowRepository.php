<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\Workflow\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Core\Repository\Trait\PaginationTrait;
use Aurora\Module\Welding\Workflow\Entity\Workflow;
use Aurora\Module\Welding\Workflow\Entity\WorkflowInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<WorkflowInterface>
 */
class WorkflowRepository extends ResolveTargetEntityRepository
{
    use PaginationTrait;

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

    /**
     * @return array{items: WorkflowInterface[], total: int, totalPages: int, page: int, limit: int}
     */
    public function findPaginated(int $page, int $limit = 20, ?string $search = null, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('workflow')
            ->addSelect('template', 'assignee')
            ->leftJoin('workflow.template', 'template')
            ->leftJoin('workflow.assignee', 'assignee')
            ->orderBy('workflow.createdAt', Order::Descending->value);

        $countQb = $this->createQueryBuilder('workflow')
            ->select('COUNT(workflow.id)')
            ->leftJoin('workflow.template', 'template')
            ->leftJoin('workflow.assignee', 'assignee');

        if (null !== $search && '' !== $search) {
            $pattern = '%'.mb_strtolower($search).'%';
            $where = 'LOWER(workflow.reference) LIKE :search OR LOWER(template.title) LIKE :search OR LOWER(assignee.firstName) LIKE :search OR LOWER(assignee.lastName) LIKE :search';
            $qb->andWhere($where)->setParameter('search', $pattern);
            $countQb->andWhere($where)->setParameter('search', $pattern);
        }

        if (null !== $status && '' !== $status) {
            $qb->andWhere('workflow.status = :status')->setParameter('status', $status);
            $countQb->andWhere('workflow.status = :status')->setParameter('status', $status);
        }

        return $this->paginate($qb, $countQb, $page, $limit);
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
