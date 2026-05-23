<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Core\Repository\Trait\PaginationTrait;
use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransaction;
use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransactionInterface;
use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use DateTimeImmutable;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<PersonalFinanceTransactionInterface> */
class PersonalFinanceTransactionRepository extends ResolveTargetEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PersonalFinanceTransaction::class, PersonalFinanceTransactionInterface::class);
    }

    /**
     * Paginated list of transactions for a wallet. Search filter matches
     * description or joined category name.
     *
     * @return array{items: list<PersonalFinanceTransactionInterface>, total: int, page: int, totalPages: int}
     */
    public function findPaginatedByWallet(PersonalFinanceWalletInterface $wallet, int $page, int $limit = 30, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.category', 'c')
            ->addSelect('c')
            ->where('t.wallet = :wallet')
            ->setParameter('wallet', $wallet)
            ->orderBy('t.date', Order::Descending->value)
            ->addOrderBy('t.id', Order::Descending->value);

        $countQb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->leftJoin('t.category', 'c')
            ->where('t.wallet = :wallet')
            ->setParameter('wallet', $wallet);

        if (null !== $search && '' !== $search) {
            $pattern = '%'.mb_strtolower($search).'%';
            $qb->andWhere('LOWER(t.description) LIKE :search OR LOWER(c.name) LIKE :search')->setParameter('search', $pattern);
            $countQb->andWhere('LOWER(t.description) LIKE :search OR LOWER(c.name) LIKE :search')->setParameter('search', $pattern);
        }

        return $this->paginate($qb, $countQb, $page, $limit);
    }

    /**
     * @return list<PersonalFinanceTransactionInterface>
     */
    public function findByWallet(PersonalFinanceWalletInterface $wallet, int $limit = 200): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.category', 'c')
            ->addSelect('c')
            ->where('t.wallet = :wallet')
            ->setParameter('wallet', $wallet)
            ->orderBy('t.date', Order::Descending->value)
            ->addOrderBy('t.id', Order::Descending->value)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns the transactions sharing the given transferId. Expected
     * count is exactly 2 (one Expense on the source wallet, one Income
     * on the target wallet). Returning an empty list indicates either a
     * missing or partially-deleted transfer — callers should handle that
     * as an invariant violation.
     *
     * @return list<PersonalFinanceTransactionInterface>
     */
    public function findByTransferId(string $transferId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.transferId = :transferId')
            ->setParameter('transferId', $transferId)
            ->orderBy('t.type', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns every transaction sharing the given splitId. Order is
     * insertion order (by id) to keep the UI display stable.
     *
     * @return list<PersonalFinanceTransactionInterface>
     */
    public function findBySplitId(string $splitId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.splitId = :splitId')
            ->setParameter('splitId', $splitId)
            ->orderBy('t.id', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns the signed net flow on the wallet over the given closed
     * interval [from, to). Income is positive, Expense negative. The
     * sum is returned as a decimal string (bcmath-safe) — never as
     * float, to avoid drift on long histories.
     *
     * Pass `null` for either bound to leave it open.
     */
    public function netFlow(PersonalFinanceWalletInterface $wallet, ?DateTimeImmutable $from = null, ?DateTimeImmutable $to = null): string
    {
        $qb = $this->createQueryBuilder('t')
            ->select('t.type', 'SUM(t.amount) AS total')
            ->where('t.wallet = :wallet')
            ->setParameter('wallet', $wallet)
            ->groupBy('t.type');

        if (null !== $from) {
            $qb->andWhere('t.date >= :from')->setParameter('from', $from);
        }
        if (null !== $to) {
            $qb->andWhere('t.date < :to')->setParameter('to', $to);
        }

        $income = '0';
        $expense = '0';
        foreach ($qb->getQuery()->getResult() as $row) {
            if (PersonalFinanceTransactionTypeEnum::Income === $row['type']) {
                $income = (string) $row['total'];
            } elseif (PersonalFinanceTransactionTypeEnum::Expense === $row['type']) {
                $expense = (string) $row['total'];
            }
        }

        return bcsub($income, $expense, 2);
    }

    /**
     * @return list<PersonalFinanceTransactionInterface>
     */
    public function findByWalletAndMonth(PersonalFinanceWalletInterface $wallet, DateTimeImmutable $month): array
    {
        $start = $month->modify('first day of this month')->setTime(0, 0);
        $end = $month->modify('first day of next month')->setTime(0, 0);

        return $this->createQueryBuilder('t')
            ->leftJoin('t.category', 'c')
            ->addSelect('c')
            ->where('t.wallet = :wallet')
            ->andWhere('t.date >= :start')
            ->andWhere('t.date < :end')
            ->setParameter('wallet', $wallet)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('t.date', Order::Descending->value)
            ->addOrderBy('t.id', Order::Descending->value)
            ->getQuery()
            ->getResult();
    }
}
