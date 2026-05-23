<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Goal\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\PersonalFinance\Goal\Entity\PersonalFinanceGoal;
use Aurora\Module\PersonalFinance\Goal\Entity\PersonalFinanceGoalInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<PersonalFinanceGoalInterface> */
class PersonalFinanceGoalRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PersonalFinanceGoal::class, PersonalFinanceGoalInterface::class);
    }

    /**
     * @return list<PersonalFinanceGoalInterface>
     */
    public function findOwnedByUser(CoreUserInterface $user): array
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.category', 'c')
            ->addSelect('c')
            ->leftJoin('g.wallet', 'w')
            ->addSelect('w')
            ->where('g.user = :user')
            ->setParameter('user', $user)
            ->orderBy('g.deadline', Order::Ascending->value)
            ->addOrderBy('g.id', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns the goal auto-tracking the given category for the user, if any.
     * At most one row by the partial unique constraint `uniq_pf_goal_user_category`.
     */
    public function findOneByCategoryForUser(CoreUserInterface $user, PersonalFinanceCategoryInterface $category): ?PersonalFinanceGoalInterface
    {
        return $this->createQueryBuilder('g')
            ->where('g.user = :user')
            ->andWhere('g.category = :category')
            ->setParameter('user', $user)
            ->setParameter('category', $category)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
