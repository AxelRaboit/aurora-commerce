<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Statistics\Service;

use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Aurora\Module\PersonalFinance\Transaction\Repository\PersonalFinanceTransactionRepository;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletRepository;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Temporal analytics for the Statistics page. Computes monthly
 * income/expense series + per-category trend + year-over-year
 * comparison for the trailing N months window.
 *
 * Sibling of OverviewService — same hook-based, stateless,
 * non-`final` pattern so a client can swap any helper. Where
 * Overview is "right now snapshot", Statistics is "evolution over
 * time".
 */
#[AsAlias(PersonalFinanceStatisticsServiceInterface::class)]
class PersonalFinanceStatisticsService implements PersonalFinanceStatisticsServiceInterface
{
    /** @var list<int> Allowed period sizes — anything else is clamped to the nearest. */
    protected const array ALLOWED_PERIODS = [3, 6, 12];

    public function __construct(
        protected readonly PersonalFinanceWalletRepository $walletRepository,
        protected readonly PersonalFinanceTransactionRepository $transactionRepository,
    ) {}

    /** @return array<string, mixed> */
    public function snapshot(CoreUserInterface $user, int $months = 6, ?DateTimeImmutable $today = null): array
    {
        $today ??= new DateTimeImmutable('today');
        $months = in_array($months, self::ALLOWED_PERIODS, true) ? $months : 6;
        $wallets = $this->walletRepository->findAccessibleByUser($user);

        return [
            'today' => $today->format('Y-m-d'),
            'months' => $months,
            'allowedPeriods' => self::ALLOWED_PERIODS,
            'monthlyFlow' => $this->monthlyFlow($wallets, $today, $months),
            'categoryTrend' => $this->categoryTrend($wallets, $today, $months),
            'yoyComparison' => $this->yoyComparison($wallets, $today),
        ];
    }

    /**
     * For each of the trailing N months (oldest first), return total
     * income / expense / net summed cross-wallet. The latest entry
     * is the current month (partial).
     *
     * @param list<PersonalFinanceWalletInterface> $wallets
     *
     * @return list<array{month: string, income: string, expense: string, net: string}>
     */
    protected function monthlyFlow(array $wallets, DateTimeImmutable $today, int $months): array
    {
        $series = [];
        $currentMonthStart = $today->modify('first day of this month')->setTime(0, 0);

        for ($i = $months - 1; $i >= 0; --$i) {
            $start = $currentMonthStart->modify(sprintf('-%d months', $i));
            $end = $start->modify('first day of next month');
            $income = '0.00';
            $expense = '0.00';
            foreach ($wallets as $wallet) {
                $income = bcadd($income, $this->transactionRepository->sumByTypeForPeriod($wallet, PersonalFinanceTransactionTypeEnum::Income->value, $start, $end), 2);
                $expense = bcadd($expense, $this->transactionRepository->sumByTypeForPeriod($wallet, PersonalFinanceTransactionTypeEnum::Expense->value, $start, $end), 2);
            }
            $series[] = [
                'month' => $start->format('Y-m'),
                'income' => $income,
                'expense' => $expense,
                'net' => bcsub($income, $expense, 2),
            ];
        }

        return $series;
    }

    /**
     * Top 5 expense categories over the period, each with its
     * monthly series (same N-month window). Lets the front-end
     * render a small multi : 5 mini sparklines one above the other.
     *
     * @param list<PersonalFinanceWalletInterface> $wallets
     *
     * @return list<array{categoryName: string, total: string, series: list<array{month: string, expense: string}>}>
     */
    protected function categoryTrend(array $wallets, DateTimeImmutable $today, int $months): array
    {
        $periodStart = $today->modify('first day of this month')->modify(sprintf('-%d months', $months - 1))->setTime(0, 0);
        $periodEnd = $today->modify('first day of next month')->setTime(0, 0);

        // Aggregate top categories over the whole period to pick the top 5.
        $aggregated = [];
        foreach ($wallets as $wallet) {
            foreach ($this->transactionRepository->topExpenseCategories($wallet, $periodStart, $periodEnd) as $row) {
                $key = $row['categoryName'] ?? 'uncategorized';
                if (!isset($aggregated[$key])) {
                    $aggregated[$key] = ['categoryName' => $key, 'total' => '0.00'];
                }
                $aggregated[$key]['total'] = bcadd($aggregated[$key]['total'], (string) $row['total'], 2);
            }
        }

        if ([] === $aggregated) {
            return [];
        }

        usort($aggregated, static fn (array $a, array $b): int => bccomp($b['total'], $a['total'], 2));
        $top = array_slice($aggregated, 0, 5);

        // Build per-month series for each surviving category.
        $currentMonthStart = $today->modify('first day of this month')->setTime(0, 0);
        foreach ($top as $i => $row) {
            $series = [];
            for ($m = $months - 1; $m >= 0; --$m) {
                $monthStart = $currentMonthStart->modify(sprintf('-%d months', $m));
                $monthEnd = $monthStart->modify('first day of next month');
                $monthTotal = '0.00';
                foreach ($wallets as $wallet) {
                    foreach ($this->transactionRepository->topExpenseCategories($wallet, $monthStart, $monthEnd) as $perMonth) {
                        if (($perMonth['categoryName'] ?? null) === $row['categoryName']) {
                            $monthTotal = bcadd($monthTotal, (string) $perMonth['total'], 2);
                        }
                    }
                }
                $series[] = ['month' => $monthStart->format('Y-m'), 'expense' => $monthTotal];
            }
            $top[$i]['series'] = $series;
        }

        return array_values($top);
    }

    /**
     * Year-over-year comparison : current month vs the same month
     * one year ago, for income and expense. deltaPercent is null
     * when the prior value is zero (avoid division by zero).
     *
     * @param list<PersonalFinanceWalletInterface> $wallets
     *
     * @return array<string, mixed>
     */
    protected function yoyComparison(array $wallets, DateTimeImmutable $today): array
    {
        $thisStart = $today->modify('first day of this month')->setTime(0, 0);
        $thisEnd = $thisStart->modify('first day of next month');
        $lastYearStart = $thisStart->modify('-1 year');
        $lastYearEnd = $lastYearStart->modify('first day of next month');

        $thisIncome = '0.00';
        $thisExpense = '0.00';
        $lastIncome = '0.00';
        $lastExpense = '0.00';

        foreach ($wallets as $wallet) {
            $thisIncome = bcadd($thisIncome, $this->transactionRepository->sumByTypeForPeriod($wallet, 'income', $thisStart, $thisEnd), 2);
            $thisExpense = bcadd($thisExpense, $this->transactionRepository->sumByTypeForPeriod($wallet, 'expense', $thisStart, $thisEnd), 2);
            $lastIncome = bcadd($lastIncome, $this->transactionRepository->sumByTypeForPeriod($wallet, 'income', $lastYearStart, $lastYearEnd), 2);
            $lastExpense = bcadd($lastExpense, $this->transactionRepository->sumByTypeForPeriod($wallet, 'expense', $lastYearStart, $lastYearEnd), 2);
        }

        return [
            'thisMonth' => $thisStart->format('Y-m'),
            'lastYearMonth' => $lastYearStart->format('Y-m'),
            'income' => ['current' => $thisIncome, 'previous' => $lastIncome, 'deltaPercent' => $this->deltaPercent($lastIncome, $thisIncome)],
            'expense' => ['current' => $thisExpense, 'previous' => $lastExpense, 'deltaPercent' => $this->deltaPercent($lastExpense, $thisExpense)],
            'net' => [
                'current' => bcsub($thisIncome, $thisExpense, 2),
                'previous' => bcsub($lastIncome, $lastExpense, 2),
            ],
        ];
    }

    protected function deltaPercent(string $previous, string $current): ?float
    {
        if (0 === bccomp($previous, '0', 2)) {
            return null;
        }

        return round(((float) $current - (float) $previous) / (float) $previous * 100, 1);
    }
}
