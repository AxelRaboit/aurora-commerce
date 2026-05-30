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

    /**
     * Most-recent month for which a Budget row exists on the wallet —
     * used by the month-reset service to extend a cascade past today
     * when the user has planned budgets in the future.
     */
    public function findLatestMonth(PersonalFinanceWalletInterface $wallet): ?DateTimeImmutable
    {
        $result = $this->createQueryBuilder('b')
            ->select('MAX(b.month) AS maxMonth')
            ->where('b.wallet = :wallet')
            ->setParameter('wallet', $wallet)
            ->getQuery()
            ->getSingleScalarResult();

        if (null === $result) {
            return null;
        }

        // getSingleScalarResult() promises a scalar, but Doctrine actually
        // hands back the DateTimeImmutable when MAX() is applied to a
        // date_immutable column. Coerce to string + reparse to stay
        // within the documented contract regardless.
        return new DateTimeImmutable((string) $result);
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

    /**
     * Bulk variant of {@see findByWalletAndMonth} : one query returns
     * `{walletId => Budget}` for every wallet in `$wallets` that has a
     * budget row for `$month`. Wallets without a budget are absent
     * from the map. Powers the Overview budget-alerts panel without
     * N round-trips.
     *
     * @param list<PersonalFinanceWalletInterface> $wallets
     *
     * @return array<int, PersonalFinanceBudgetInterface>
     */
    public function findByWalletsAndMonth(array $wallets, DateTimeImmutable $month): array
    {
        if ([] === $wallets) {
            return [];
        }

        $firstDay = $month->modify('first day of this month')->setTime(0, 0);

        $rows = $this->createQueryBuilder('b')
            ->where('b.wallet IN (:wallets)')
            ->andWhere('b.month = :month')
            ->setParameter('wallets', $wallets)
            ->setParameter('month', $firstDay)
            ->getQuery()
            ->getResult();

        $out = [];
        foreach ($rows as $budget) {
            $out[(int) $budget->getWallet()->getId()] = $budget;
        }

        return $out;
    }
}
