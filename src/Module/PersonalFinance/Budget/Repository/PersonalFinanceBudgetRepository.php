<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudget;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<PersonalFinanceBudgetInterface> */
class PersonalFinanceBudgetRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PersonalFinanceBudget::class, PersonalFinanceBudgetInterface::class);
    }

    public function findByWalletAndMonth(PersonalFinanceWalletInterface $wallet, DateTimeImmutable $month): ?PersonalFinanceBudgetInterface
    {
        $firstDay = $month->modify('first day of this month')->setTime(0, 0);

        return $this->createQueryBuilder('b')
            ->where('b.wallet = :wallet')
            ->andWhere('b.month = :month')
            ->setParameter('wallet', $wallet)
            ->setParameter('month', $firstDay)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
