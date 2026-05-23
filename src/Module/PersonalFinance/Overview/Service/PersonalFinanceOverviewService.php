<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Overview\Service;

use Aurora\Module\PersonalFinance\Budget\Repository\PersonalFinanceBudgetItemRepository;
use Aurora\Module\PersonalFinance\Budget\Repository\PersonalFinanceBudgetRepository;
use Aurora\Module\PersonalFinance\Goal\Repository\PersonalFinanceGoalRepository;
use Aurora\Module\PersonalFinance\Recurring\Repository\PersonalFinanceRecurringTransactionRepository;
use Aurora\Module\PersonalFinance\Recurring\Repository\PersonalFinanceScheduledTransactionRepository;
use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Aurora\Module\PersonalFinance\Transaction\Repository\PersonalFinanceTransactionRepository;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletRepository;
use Aurora\Module\PersonalFinance\Wallet\Service\PersonalFinanceWalletBalanceServiceInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Cross-wallet aggregations for the unified Overview page. Absorbs
 * the former DashboardService — the two pages had heavy overlap and
 * shipping a single canonical "PF home" reduces cognitive load.
 *
 * Stateless, non-`final`, hook-based — clients can swap any
 * computation by extending one of the protected helpers.
 */
#[AsAlias(PersonalFinanceOverviewServiceInterface::class)]
class PersonalFinanceOverviewService implements PersonalFinanceOverviewServiceInterface
{
    public function __construct(
        protected readonly PersonalFinanceWalletRepository $walletRepository,
        protected readonly PersonalFinanceTransactionRepository $transactionRepository,
        protected readonly PersonalFinanceBudgetRepository $budgetRepository,
        protected readonly PersonalFinanceBudgetItemRepository $budgetItemRepository,
        protected readonly PersonalFinanceGoalRepository $goalRepository,
        protected readonly PersonalFinanceRecurringTransactionRepository $recurringRepository,
        protected readonly PersonalFinanceScheduledTransactionRepository $scheduledRepository,
        protected readonly PersonalFinanceWalletBalanceServiceInterface $balanceService,
    ) {}

    /** @return array<string, mixed> */
    public function snapshot(CoreUserInterface $user, ?DateTimeImmutable $today = null): array
    {
        $today ??= new DateTimeImmutable('today');
        $wallets = $this->walletRepository->findAccessibleByUser($user);

        $monthStart = $today->modify('first day of this month')->setTime(0, 0);
        $monthEnd = $today->modify('first day of next month')->setTime(0, 0);

        return [
            'today' => $today->format('Y-m-d'),
            'month' => $monthStart->format('Y-m'),
            'totals' => $this->totals($wallets, $monthStart, $monthEnd),
            'monthFlow' => $this->monthFlow($wallets, $today),
            'sparkline' => $this->dailyExpenseSparkline($wallets, $today),
            'walletsBreakdown' => $this->walletsBreakdown($wallets, $monthStart, $monthEnd),
            'categoryBreakdown' => $this->categoryBreakdown($wallets, $monthStart, $monthEnd),
            'recentTransactions' => $this->recentTransactions($wallets),
            'goals' => $this->goalsSnapshot($user),
            'upcomingRecurring' => $this->upcomingRecurring($user, $today),
            'upcomingScheduled' => $this->upcomingScheduled($user, $today),
            'budgetAlerts' => $this->budgetAlerts($wallets, $today),
        ];
    }

    /**
     * Simple cross-wallet sums : count, balance, raw month income /
     * expense / net. The delta-vs-previous-month is exposed
     * separately by `monthFlow()`.
     *
     * @param list<PersonalFinanceWalletInterface> $wallets
     *
     * @return array<string, mixed>
     */
    protected function totals(array $wallets, DateTimeImmutable $monthStart, DateTimeImmutable $monthEnd): array
    {
        $totalBalance = '0.00';
        $monthIncome = '0.00';
        $monthExpense = '0.00';

        foreach ($wallets as $wallet) {
            $totalBalance = bcadd($totalBalance, $this->balanceService->currentBalance($wallet), 2);
            $monthIncome = bcadd($monthIncome, $this->transactionRepository->sumByTypeForPeriod($wallet, PersonalFinanceTransactionTypeEnum::Income->value, $monthStart, $monthEnd), 2);
            $monthExpense = bcadd($monthExpense, $this->transactionRepository->sumByTypeForPeriod($wallet, PersonalFinanceTransactionTypeEnum::Expense->value, $monthStart, $monthEnd), 2);
        }

        return [
            'walletCount' => count($wallets),
            'totalBalance' => $totalBalance,
            'monthIncome' => $monthIncome,
            'monthExpense' => $monthExpense,
            'monthNet' => bcsub($monthIncome, $monthExpense, 2),
        ];
    }

    /**
     * Month-over-month KPI block : income / expense / net for the
     * current month with previous-month comparison + delta % so the
     * UI can render `+12 %` / `−4 %` chips next to each tile.
     *
     * @param list<PersonalFinanceWalletInterface> $wallets
     *
     * @return array<string, mixed>
     */
    protected function monthFlow(array $wallets, DateTimeImmutable $today): array
    {
        $startThis = $today->modify('first day of this month')->setTime(0, 0);
        $startNext = $startThis->modify('first day of next month');
        $startPrev = $startThis->modify('first day of last month');

        $thisIncome = '0.00';
        $thisExpense = '0.00';
        $prevIncome = '0.00';
        $prevExpense = '0.00';

        foreach ($wallets as $wallet) {
            $thisIncome = bcadd($thisIncome, $this->transactionRepository->sumByTypeForPeriod($wallet, 'income', $startThis, $startNext), 2);
            $thisExpense = bcadd($thisExpense, $this->transactionRepository->sumByTypeForPeriod($wallet, 'expense', $startThis, $startNext), 2);
            $prevIncome = bcadd($prevIncome, $this->transactionRepository->sumByTypeForPeriod($wallet, 'income', $startPrev, $startThis), 2);
            $prevExpense = bcadd($prevExpense, $this->transactionRepository->sumByTypeForPeriod($wallet, 'expense', $startPrev, $startThis), 2);
        }

        return [
            'income' => ['current' => $thisIncome, 'previous' => $prevIncome, 'deltaPercent' => $this->deltaPercent($prevIncome, $thisIncome)],
            'expense' => ['current' => $thisExpense, 'previous' => $prevExpense, 'deltaPercent' => $this->deltaPercent($prevExpense, $thisExpense)],
            'net' => bcsub($thisIncome, $thisExpense, 2),
        ];
    }

    /**
     * 30-day daily-expense series feeding an inline SVG sparkline on
     * the front-end. Each entry has the calendar date + the
     * cross-wallet expense total for that day.
     *
     * @param list<PersonalFinanceWalletInterface> $wallets
     *
     * @return list<array{date: string, expense: string}>
     */
    protected function dailyExpenseSparkline(array $wallets, DateTimeImmutable $today): array
    {
        $from = $today->modify('-29 days')->setTime(0, 0);
        $to = $today->modify('+1 day')->setTime(0, 0);

        $merged = [];
        foreach ($wallets as $wallet) {
            foreach ($this->transactionRepository->dailyExpenseSeries($wallet, $from, $to) as $date => $amount) {
                $merged[$date] = bcadd($merged[$date] ?? '0', $amount, 2);
            }
        }

        $series = [];
        for ($i = 0; $i < 30; ++$i) {
            $d = $from->modify('+'.$i.' days')->format('Y-m-d');
            $series[] = ['date' => $d, 'expense' => $merged[$d] ?? '0.00'];
        }

        return $series;
    }

    /**
     * Per-wallet row : balance + month flow + share of the absolute
     * cumulative balance. Sorted by balance desc.
     *
     * @param list<PersonalFinanceWalletInterface> $wallets
     *
     * @return list<array<string, mixed>>
     */
    protected function walletsBreakdown(array $wallets, DateTimeImmutable $monthStart, DateTimeImmutable $monthEnd): array
    {
        $rows = [];
        $absTotal = '0.00';

        foreach ($wallets as $wallet) {
            $balance = $this->balanceService->currentBalance($wallet);
            $rows[] = [
                'id' => $wallet->getId(),
                'name' => $wallet->getName(),
                'balance' => $balance,
                'monthIncome' => $this->transactionRepository->sumByTypeForPeriod($wallet, PersonalFinanceTransactionTypeEnum::Income->value, $monthStart, $monthEnd),
                'monthExpense' => $this->transactionRepository->sumByTypeForPeriod($wallet, PersonalFinanceTransactionTypeEnum::Expense->value, $monthStart, $monthEnd),
            ];
            $absTotal = bcadd($absTotal, bcadd($balance, '0', 2) >= 0 ? $balance : bcmul($balance, '-1', 2), 2);
        }

        $absTotalFloat = (float) $absTotal;
        foreach ($rows as $i => $row) {
            $abs = abs((float) $row['balance']);
            $rows[$i]['share'] = $absTotalFloat > 0 ? (int) round(($abs / $absTotalFloat) * 100) : 0;
        }

        usort($rows, static fn (array $a, array $b): int => bccomp($b['balance'], $a['balance'], 2));

        return array_values($rows);
    }

    /**
     * Top 10 expense categories cross-wallet for the current month
     * with their percentage of the total expense pot. Excludes
     * transfer legs (filtered by `topExpenseCategories`).
     *
     * @param list<PersonalFinanceWalletInterface> $wallets
     *
     * @return list<array<string, mixed>>
     */
    protected function categoryBreakdown(array $wallets, DateTimeImmutable $monthStart, DateTimeImmutable $monthEnd): array
    {
        $aggregated = [];

        foreach ($wallets as $wallet) {
            foreach ($this->transactionRepository->topExpenseCategories($wallet, $monthStart, $monthEnd) as $row) {
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
        $aggregated = array_slice($aggregated, 0, 10);

        $sum = '0.00';
        foreach ($aggregated as $row) {
            $sum = bcadd($sum, $row['total'], 2);
        }
        $sumFloat = (float) $sum;
        foreach ($aggregated as $i => $row) {
            $aggregated[$i]['percent'] = $sumFloat > 0 ? (int) round(((float) $row['total'] / $sumFloat) * 100) : 0;
        }

        return array_values($aggregated);
    }

    /**
     * Recent transactions across every accessible wallet.
     *
     * @param list<PersonalFinanceWalletInterface> $wallets
     *
     * @return list<array<string, mixed>>
     */
    protected function recentTransactions(array $wallets, int $limit = 8): array
    {
        if ([] === $wallets) {
            return [];
        }

        $out = [];
        foreach ($this->transactionRepository->findRecentAcrossWallets($wallets, $limit) as $tx) {
            $out[] = [
                'id' => $tx->getId(),
                'walletId' => $tx->getWallet()->getId(),
                'walletName' => $tx->getWallet()->getName(),
                'categoryName' => $tx->getCategory()?->getName(),
                'date' => $tx->getDate()->format('Y-m-d'),
                'amount' => $tx->getAmount(),
                'type' => $tx->getType()->value,
                'description' => $tx->getDescription(),
            ];
        }

        return $out;
    }

    /**
     * Savings-goals snapshot : counts + top 3 active goals.
     *
     * @return array<string, mixed>
     */
    protected function goalsSnapshot(CoreUserInterface $user): array
    {
        $goals = $this->goalRepository->findOwnedByUser($user);
        $active = array_values(array_filter($goals, static fn ($g): bool => !$g->isCompleted()));

        return [
            'totalCount' => count($goals),
            'activeCount' => count($active),
            'top' => array_values(array_map(
                static fn ($g): array => [
                    'id' => $g->getId(),
                    'name' => $g->getName(),
                    'progress' => round($g->getProgress(), 1),
                    'color' => $g->getColor(),
                ],
                array_slice($active, 0, 3),
            )),
        ];
    }

    /**
     * Recurring rules that haven't fired yet this month, sorted by
     * day-of-month so the next-due surfaces first.
     *
     * @return list<array<string, mixed>>
     */
    protected function upcomingRecurring(CoreUserInterface $user, DateTimeImmutable $today, int $limit = 5): array
    {
        $thisMonth = $today->format('Y-m');
        $todayDay = (int) $today->format('j');

        $rules = $this->recurringRepository->findOwnedByUser($user);
        $upcoming = [];
        foreach ($rules as $r) {
            if (!$r->isActive()) {
                continue;
            }
            if ($r->getLastGeneratedAt()?->format('Y-m') === $thisMonth && $r->getDayOfMonth() <= $todayDay) {
                continue;
            }
            $upcoming[] = [
                'id' => $r->getId(),
                'description' => $r->getDescription(),
                'amount' => $r->getAmount(),
                'type' => $r->getType()->value,
                'dayOfMonth' => $r->getDayOfMonth(),
                'walletName' => $r->getWallet()->getName(),
            ];
        }

        usort($upcoming, static fn (array $a, array $b): int => $a['dayOfMonth'] <=> $b['dayOfMonth']);

        return array_slice($upcoming, 0, $limit);
    }

    /**
     * Scheduled (one-off) transactions still in the future and not
     * yet materialised.
     *
     * @return list<array<string, mixed>>
     */
    protected function upcomingScheduled(CoreUserInterface $user, DateTimeImmutable $today, int $limit = 5): array
    {
        $rows = $this->scheduledRepository->findOwnedByUser($user);
        $upcoming = [];
        foreach ($rows as $s) {
            if ($s->isGenerated()) {
                continue;
            }
            if ($s->getScheduledDate() < $today) {
                continue;
            }
            $upcoming[] = [
                'id' => $s->getId(),
                'description' => $s->getDescription(),
                'amount' => $s->getAmount(),
                'type' => $s->getType()->value,
                'scheduledDate' => $s->getScheduledDate()->format('Y-m-d'),
                'walletName' => $s->getWallet()->getName(),
            ];
        }

        return array_slice($upcoming, 0, $limit);
    }

    /**
     * Budget items where actual already exceeds expected for the
     * current month — sorted by overshoot desc so the worst
     * offenders are reported first.
     *
     * @param list<PersonalFinanceWalletInterface> $wallets
     *
     * @return list<array<string, mixed>>
     */
    protected function budgetAlerts(array $wallets, DateTimeImmutable $today): array
    {
        $month = $today->modify('first day of this month')->setTime(0, 0);
        $end = $month->modify('first day of next month');

        $alerts = [];
        foreach ($wallets as $wallet) {
            $budget = $this->budgetRepository->findByWalletAndMonth($wallet, $month);
            if (null === $budget) {
                continue;
            }
            $items = $this->budgetItemRepository->findByBudget($budget);
            $actuals = $this->transactionRepository->actualsByCategoryForMonth($wallet, $month, $end);

            foreach ($items as $item) {
                $categoryId = $item->getCategory()?->getId();
                $actual = null === $categoryId ? '0.00' : ($actuals[$categoryId] ?? '0.00');
                $expected = bcadd($item->getPlannedAmount(), $item->getCarriedOver(), 2);
                if (1 !== bccomp($actual, $expected, 2)) {
                    continue;
                }
                $alerts[] = [
                    'walletId' => $wallet->getId(),
                    'walletName' => $wallet->getName(),
                    'itemId' => $item->getId(),
                    'label' => $item->getLabel(),
                    'section' => $item->getSection()->value,
                    'expected' => $expected,
                    'actual' => $actual,
                    'overshoot' => bcsub($actual, $expected, 2),
                ];
            }
        }

        usort($alerts, static fn (array $a, array $b): int => bccomp($b['overshoot'], $a['overshoot'], 2));

        return $alerts;
    }

    protected function deltaPercent(string $previous, string $current): ?float
    {
        if (0 === bccomp($previous, '0', 2)) {
            return null;
        }

        return round(((float) $current - (float) $previous) / (float) $previous * 100, 1);
    }
}
