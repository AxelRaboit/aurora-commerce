<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetInterface;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetItem;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetItemInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<PersonalFinanceBudgetItemInterface> */
class PersonalFinanceBudgetItemRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PersonalFinanceBudgetItem::class, PersonalFinanceBudgetItemInterface::class);
    }

    /**
     * @return list<PersonalFinanceBudgetItemInterface>
     */
    public function findByBudget(PersonalFinanceBudgetInterface $budget): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.category', 'c')
            ->addSelect('c')
            ->where('i.budget = :budget')
            ->setParameter('budget', $budget)
            ->orderBy('i.section', Order::Ascending->value)
            ->addOrderBy('i.position', Order::Ascending->value)
            ->addOrderBy('i.id', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }

    /**
     * Items flagged repeat_next_month from the given prior budget — used
     * by the carry-over service to seed the next month's budget.
     *
     * @return list<PersonalFinanceBudgetItemInterface>
     */
    public function findRepeatableByBudget(PersonalFinanceBudgetInterface $budget): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.budget = :budget')
            ->andWhere('i.repeatNextMonth = true')
            ->setParameter('budget', $budget)
            ->orderBy('i.section', Order::Ascending->value)
            ->addOrderBy('i.position', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }
}
