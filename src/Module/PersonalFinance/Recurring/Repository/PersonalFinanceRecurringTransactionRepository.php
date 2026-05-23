<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Recurring\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\PersonalFinance\Recurring\Entity\PersonalFinanceRecurringTransaction;
use Aurora\Module\PersonalFinance\Recurring\Entity\PersonalFinanceRecurringTransactionInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DateTimeImmutable;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<PersonalFinanceRecurringTransactionInterface> */
class PersonalFinanceRecurringTransactionRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PersonalFinanceRecurringTransaction::class, PersonalFinanceRecurringTransactionInterface::class);
    }

    /**
     * @return list<PersonalFinanceRecurringTransactionInterface>
     */
    public function findOwnedByUser(CoreUserInterface $user): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.wallet', 'w')->addSelect('w')
            ->leftJoin('r.category', 'c')->addSelect('c')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.dayOfMonth', Order::Ascending->value)
            ->addOrderBy('r.id', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }

    /**
     * Active recurring rules whose dayOfMonth has already passed in the
     * given month, and that haven't been generated yet for that month.
     * Used by the personal-finance:recurring:generate command.
     *
     * The Y-m comparison is done in PHP rather than SQL to keep the
     * query portable across PostgreSQL/SQLite — the dataset is tiny
     * (one row per user-recurring rule).
     *
     * @return list<PersonalFinanceRecurringTransactionInterface>
     */
    public function findActiveDueOn(DateTimeImmutable $today): array
    {
        $candidates = $this->createQueryBuilder('r')
            ->where('r.active = true')
            ->andWhere('r.dayOfMonth <= :day')
            ->setParameter('day', (int) $today->format('j'))
            ->orderBy('r.id', Order::Ascending->value)
            ->getQuery()
            ->getResult();

        $thisMonth = $today->format('Y-m');

        return array_values(array_filter(
            $candidates,
            static fn (PersonalFinanceRecurringTransactionInterface $r): bool => $r->getLastGeneratedAt()?->format('Y-m') !== $thisMonth,
        ));
    }
}
