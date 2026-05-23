<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Core\Repository\Trait\PaginationTrait;
use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransaction;
use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransactionInterface;
use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
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
    public function findPaginatedByWallet(PersonalFinanceWalletInterface $wallet, int $page, int $limit = 30, ?string $search = null, ?string $tag = null): array
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

        if (null !== $tag && '' !== $tag) {
            $ids = $this->idsTaggedWith($wallet, $tag);
            if ([] === $ids) {
                return ['items' => [], 'total' => 0, 'page' => 1, 'totalPages' => 1];
            }
            $qb->andWhere('t.id IN (:tagIds)')->setParameter('tagIds', $ids);
            $countQb->andWhere('t.id IN (:tagIds)')->setParameter('tagIds', $ids);
        }

        return $this->paginate($qb, $countQb, $page, $limit);
    }

    /**
     * Returns the transaction ids on the given wallet whose `tags` JSON
     * array contains the exact tag value. DQL can't express JSONB
     * containment portably so we drop to native SQL — the JSON
     * serialization always quotes string values, so the `%"<tag>"%`
     * pattern matches the whole token without false-positives on
     * substrings (e.g. searching `bio` won't match `biology`).
     *
     * @return list<int>
     */
    private function idsTaggedWith(PersonalFinanceWalletInterface $wallet, string $tag): array
    {
        $rows = $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT id FROM core_personal_finance_transaction
             WHERE wallet_id = :walletId
               AND tags::text LIKE :pattern',
            [
                'walletId' => $wallet->getId(),
                'pattern' => '%"'.$tag.'"%',
            ],
        )->fetchAllAssociative();

        return array_map(static fn (array $row): int => (int) $row['id'], $rows);
    }

    /**
     * Latest transaction date on the wallet, regardless of type. Used
     * by the month-reset service to compute the upper bound of a
     * cascade reset — going past this date never has anything to do.
     */
    public function findLatestDate(PersonalFinanceWalletInterface $wallet): ?DateTimeImmutable
    {
        $result = $this->createQueryBuilder('t')
            ->select('MAX(t.date) AS maxDate')
            ->where('t.wallet = :wallet')
            ->setParameter('wallet', $wallet)
            ->getQuery()
            ->getSingleScalarResult();

        if (null === $result) {
            return null;
        }

        return $result instanceof DateTimeImmutable ? $result : new DateTimeImmutable((string) $result);
    }

    /**
     * Full (unpaginated) list of transactions for a wallet, applying the
     * same search + tag filters as findPaginatedByWallet. Used by the
     * XLSX exporter so the file reflects exactly what the user is
     * currently looking at. Memory-bound by design — large wallets stream
     * in worst-case ~5–10k rows; PhpSpreadsheet's writer keeps memory low.
     *
     * @return list<PersonalFinanceTransactionInterface>
     */
    public function findAllByWalletFiltered(PersonalFinanceWalletInterface $wallet, ?string $search = null, ?string $tag = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.category', 'c')
            ->addSelect('c')
            ->where('t.wallet = :wallet')
            ->setParameter('wallet', $wallet)
            ->orderBy('t.date', Order::Descending->value)
            ->addOrderBy('t.id', Order::Descending->value);

        if (null !== $search && '' !== $search) {
            $pattern = '%'.mb_strtolower($search).'%';
            $qb->andWhere('LOWER(t.description) LIKE :search OR LOWER(c.name) LIKE :search')->setParameter('search', $pattern);
        }

        if (null !== $tag && '' !== $tag) {
            $ids = $this->idsTaggedWith($wallet, $tag);
            if ([] === $ids) {
                return [];
            }
            $qb->andWhere('t.id IN (:tagIds)')->setParameter('tagIds', $ids);
        }

        return $qb->getQuery()->getResult();
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
     * Sum of `amount` for the given wallet, filtered by transaction
     * type, within [from, to). Transfer legs excluded so transfers
     * don't double-count in monthly flow KPIs.
     */
    public function sumByTypeForPeriod(PersonalFinanceWalletInterface $wallet, string $typeValue, DateTimeImmutable $from, DateTimeImmutable $to): string
    {
        $total = $this->createQueryBuilder('t')
            ->select('SUM(t.amount) AS total')
            ->where('t.wallet = :wallet')
            ->andWhere('t.type = :type')
            ->andWhere('t.transferId IS NULL')
            ->andWhere('t.date >= :from')
            ->andWhere('t.date < :to')
            ->setParameter('wallet', $wallet)
            ->setParameter('type', $typeValue)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();

        return bcadd('0', (string) ($total ?? '0'), 2);
    }

    /**
     * Daily expense series for the wallet over [from, to). Returns a
     * map of YYYY-MM-DD → decimal sum (bcmath string). Excludes
     * transfer legs.
     *
     * @return array<string, string>
     */
    public function dailyExpenseSeries(PersonalFinanceWalletInterface $wallet, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $rows = $this->createQueryBuilder('t')
            ->select('t.date AS day', 'SUM(t.amount) AS total')
            ->where('t.wallet = :wallet')
            ->andWhere('t.type = :type')
            ->andWhere('t.transferId IS NULL')
            ->andWhere('t.date >= :from')
            ->andWhere('t.date < :to')
            ->setParameter('wallet', $wallet)
            ->setParameter('type', 'expense')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->groupBy('t.date')
            ->getQuery()
            ->getResult();

        $out = [];
        foreach ($rows as $row) {
            $day = $row['day'] instanceof DateTimeImmutable ? $row['day']->format('Y-m-d') : (string) $row['day'];
            $out[$day] = bcadd('0', (string) $row['total'], 2);
        }

        return $out;
    }

    /**
     * Top expense categories for the wallet over [from, to). Returns
     * raw rows {categoryId, categoryName, total}; the caller can
     * sort / take(N) / merge across wallets.
     *
     * @return list<array{categoryId: int, categoryName: string, total: string}>
     */
    public function topExpenseCategories(PersonalFinanceWalletInterface $wallet, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $rows = $this->createQueryBuilder('t')
            ->select('IDENTITY(t.category) AS categoryId', 'c.name AS categoryName', 'SUM(t.amount) AS total')
            ->leftJoin('t.category', 'c')
            ->where('t.wallet = :wallet')
            ->andWhere('t.type = :type')
            ->andWhere('t.category IS NOT NULL')
            ->andWhere('t.transferId IS NULL')
            ->andWhere('t.date >= :from')
            ->andWhere('t.date < :to')
            ->setParameter('wallet', $wallet)
            ->setParameter('type', 'expense')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->groupBy('t.category', 'c.name')
            ->getQuery()
            ->getResult();

        return array_map(
            static fn (array $row): array => [
                'categoryId' => (int) $row['categoryId'],
                'categoryName' => (string) $row['categoryName'],
                'total' => bcadd('0', (string) $row['total'], 2),
            ],
            $rows,
        );
    }

    /**
     * @param list<PersonalFinanceWalletInterface> $wallets
     *
     * @return list<PersonalFinanceTransactionInterface>
     */
    public function findRecentAcrossWallets(array $wallets, int $limit = 6): array
    {
        if ([] === $wallets) {
            return [];
        }

        return $this->createQueryBuilder('t')
            ->leftJoin('t.category', 'c')->addSelect('c')
            ->leftJoin('t.wallet', 'w')->addSelect('w')
            ->where('t.wallet IN (:wallets)')
            ->andWhere('t.transferId IS NULL')
            ->setParameter('wallets', $wallets)
            ->orderBy('t.date', Order::Descending->value)
            ->addOrderBy('t.id', Order::Descending->value)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Sums |amount| of every transaction belonging to the user in the
     * given category, ignoring type sign (Spendly-compatible: both
     * income and expense add up — the user decides the semantic).
     * Transfer legs are excluded.
     *
     * Used by PersonalFinanceGoalManager::recomputeSavedAmount.
     */
    public function sumByCategoryForUser(CoreUserInterface $user, PersonalFinanceCategoryInterface $category): string
    {
        $total = $this->createQueryBuilder('t')
            ->select('SUM(t.amount) AS total')
            ->where('t.user = :user')
            ->andWhere('t.category = :category')
            ->andWhere('t.transferId IS NULL')
            ->setParameter('user', $user)
            ->setParameter('category', $category)
            ->getQuery()
            ->getSingleScalarResult();

        return bcadd('0', (string) ($total ?? '0'), 2);
    }

    /**
     * Returns the absolute spending/income per category for the wallet
     * within [from, to). Used by the Budget UI to show "actual vs
     * planned" per BudgetItem. Excludes transfer legs (transferId !=
     * null) since transfers shouldn't count as budget consumption.
     *
     * Categories with zero activity are NOT in the result — the caller
     * should fall back to '0.00' for missing keys.
     *
     * @return array<int, string> Map of categoryId → decimal sum (bcmath string)
     */
    public function actualsByCategoryForMonth(PersonalFinanceWalletInterface $wallet, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $rows = $this->createQueryBuilder('t')
            ->select('IDENTITY(t.category) AS categoryId', 'SUM(t.amount) AS total')
            ->where('t.wallet = :wallet')
            ->andWhere('t.category IS NOT NULL')
            ->andWhere('t.transferId IS NULL')
            ->andWhere('t.date >= :from')
            ->andWhere('t.date < :to')
            ->setParameter('wallet', $wallet)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->groupBy('t.category')
            ->getQuery()
            ->getResult();

        $actuals = [];
        foreach ($rows as $row) {
            $categoryId = (int) $row['categoryId'];
            $actuals[$categoryId] = bcadd('0', (string) $row['total'], 2);
        }

        return $actuals;
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
     * Paginated transactions in a single category over a given month —
     * used by the Budgets page to drill down from a budget item into
     * its actuals (with infinite scroll on the front end). Optional
     * search filter matches the transaction description (case-insensitive).
     *
     * @return array{items: list<PersonalFinanceTransactionInterface>, total: int, page: int, totalPages: int}
     */
    public function findPaginatedByCategoryAndMonth(PersonalFinanceCategoryInterface $category, DateTimeImmutable $month, int $page, int $limit = 20, ?string $search = null): array
    {
        $start = $month->modify('first day of this month')->setTime(0, 0);
        $end = $month->modify('first day of next month')->setTime(0, 0);

        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.category', 'c')
            ->addSelect('c')
            ->where('t.category = :category')
            ->andWhere('t.date >= :start')
            ->andWhere('t.date < :end')
            ->setParameter('category', $category)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('t.date', Order::Descending->value)
            ->addOrderBy('t.id', Order::Descending->value);

        $countQb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.category = :category')
            ->andWhere('t.date >= :start')
            ->andWhere('t.date < :end')
            ->setParameter('category', $category)
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        if (null !== $search && '' !== $search) {
            $pattern = '%'.mb_strtolower($search).'%';
            $qb->andWhere('LOWER(t.description) LIKE :search')->setParameter('search', $pattern);
            $countQb->andWhere('LOWER(t.description) LIKE :search')->setParameter('search', $pattern);
        }

        return $this->paginate($qb, $countQb, $page, $limit);
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
