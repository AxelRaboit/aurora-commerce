<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Dashboard\Service;

use Aurora\Module\PersonalFinance\Budget\Repository\PersonalFinanceBudgetItemRepository;
use Aurora\Module\PersonalFinance\Budget\Repository\PersonalFinanceBudgetRepository;
use Aurora\Module\PersonalFinance\Goal\Repository\PersonalFinanceGoalRepository;
use Aurora\Module\PersonalFinance\Recurring\Repository\PersonalFinanceRecurringTransactionRepository;
use Aurora\Module\PersonalFinance\Recurring\Repository\PersonalFinanceScheduledTransactionRepository;
use Aurora\Module\PersonalFinance\Transaction\Repository\PersonalFinanceTransactionRepository;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletRepository;
use Aurora\Module\PersonalFinance\Wallet\Service\PersonalFinanceWalletBalanceServiceInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Aggregates the data the dashboard page renders. Stateless — every
 * call rebuilds from scratch so an updated transaction is reflected on
 * the next refresh. Each "block" is computed by a dedicated protected
 * helper so a client can override one without touching the others.
 */
#[AsAlias(PersonalFinanceDashboardServiceInterface::class)]
class PersonalFinanceDashboardService implements PersonalFinanceDashboardServiceInterface
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

        return [
            'today' => $today->format('Y-m-d'),
            'walletStats' => $this->walletStats($wallets),
            'monthFlow' => $this->monthFlowKpis($wallets, $today),
            'sparkline' => $this->dailyExpenseSparkline($wallets, $today),
            'topCategories' => $this->topExpenseCategories($wallets, $today),
            'pinnedWallets' => $this->pinnedWallets($wallets),
            'recentTransactions' => $this->recentTransactions($user, $wallets),
            'goals' => $this->goalsSnapshot($user),
            'upcomingRecurring' => $this->upcomingRecurring($user, $today),
            'upcomingScheduled' => $this->upcomingScheduled($user, $today),
            'budgetAlerts' => $this->budgetAlerts($wallets, $today),
        ];
    }

    /**
     * @param list<\Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface> $wallets
     *
     * @return array<string, mixed>
     */
    protected function walletStats(array $wallets): array
    {
        $total = '0.00';
        foreach ($wallets as $w) {
            $total = bcadd($total, $this->balanceService->currentBalance($w), 2);
        }

        return [
            'count' => count($wallets),
            'totalBalance' => $total,
        ];
    }

    /**
     * @param list<\Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface> $wallets
     *
     * @return array<string, mixed>
     */
    protected function monthFlowKpis(array $wallets, DateTimeImmutable $today): array
    {
        $startThis = $today->modify('first day of this month')->setTime(0, 0);
        $startNext = $startThis->modify('first day of next month');
        $startPrev = $startThis->modify('first day of last month');

        $thisIncome = '0.00';
        $thisExpense = '0.00';
        $prevIncome = '0.00';
        $prevExpense = '0.00';

        foreach ($wallets as $w) {
            $thisIncome = bcadd($thisIncome, $this->transactionRepository->sumByTypeForPeriod($w, 'income', $startThis, $startNext), 2);
            $thisExpense = bcadd($thisExpense, $this->transactionRepository->sumByTypeForPeriod($w, 'expense', $startThis, $startNext), 2);
            $prevIncome = bcadd($prevIncome, $this->transactionRepository->sumByTypeForPeriod($w, 'income', $startPrev, $startThis), 2);
            $prevExpense = bcadd($prevExpense, $this->transactionRepository->sumByTypeForPeriod($w, 'expense', $startPrev, $startThis), 2);
        }

        return [
            'income' => ['current' => $thisIncome, 'previous' => $prevIncome, 'deltaPercent' => $this->deltaPercent($prevIncome, $thisIncome)],
            'expense' => ['current' => $thisExpense, 'previous' => $prevExpense, 'deltaPercent' => $this->deltaPercent($prevExpense, $thisExpense)],
            'net' => bcsub($thisIncome, $thisExpense, 2),
        ];
    }

    /**
     * @param list<\Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface> $wallets
     *
     * @return list<array{date: string, expense: string}>
     */
    protected function dailyExpenseSparkline(array $wallets, DateTimeImmutable $today): array
    {
        $from = $today->modify('-29 days')->setTime(0, 0);
        $to = $today->modify('+1 day')->setTime(0, 0);

        $merged = [];
        foreach ($wallets as $w) {
            foreach ($this->transactionRepository->dailyExpenseSeries($w, $from, $to) as $date => $amount) {
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
     * @param list<\Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface> $wallets
     *
     * @return list<array{categoryId: int, categoryName: string, total: string, percent: float}>
     */
    protected function topExpenseCategories(array $wallets, DateTimeImmutable $today, int $limit = 5): array
    {
        $startThis = $today->modify('first day of this month')->setTime(0, 0);
        $startNext = $startThis->modify('first day of next month');

        $aggregated = [];
        foreach ($wallets as $w) {
            foreach ($this->transactionRepository->topExpenseCategories($w, $startThis, $startNext) as $row) {
                $id = (int) $row['categoryId'];
                if (!isset($aggregated[$id])) {
                    $aggregated[$id] = ['categoryId' => $id, 'categoryName' => (string) $row['categoryName'], 'total' => '0'];
                }
                $aggregated[$id]['total'] = bcadd($aggregated[$id]['total'], (string) $row['total'], 2);
            }
        }

        usort($aggregated, static fn (array $a, array $b): int => bccomp($b['total'], $a['total'], 2));
        $top = array_slice($aggregated, 0, $limit);

        $sum = '0';
        foreach ($top as $row) {
            $sum = bcadd($sum, $row['total'], 2);
        }

        return array_values(array_map(
            static fn (array $row): array => [
                'categoryId' => $row['categoryId'],
                'categoryName' => $row['categoryName'],
                'total' => $row['total'],
                'percent' => 0 === bccomp($sum, '0', 2) ? 0.0 : round((float) $row['total'] / (float) $sum * 100, 1),
            ],
            $top,
        ));
    }

    /**
     * @param list<\Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface> $wallets
     *
     * @return list<array<string, mixed>>
     */
    protected function pinnedWallets(array $wallets): array
    {
        $out = [];
        foreach ($wallets as $w) {
            if (!$w->isShowOnDashboard()) {
                continue;
            }
            $out[] = [
                'id' => $w->getId(),
                'name' => $w->getName(),
                'balance' => $this->balanceService->currentBalance($w),
            ];
        }

        return $out;
    }

    /**
     * @param list<\Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface> $wallets
     *
     * @return list<array<string, mixed>>
     */
    protected function recentTransactions(CoreUserInterface $user, array $wallets, int $limit = 6): array
    {
        if ([] === $wallets) {
            return [];
        }

        $rows = $this->transactionRepository->findRecentAcrossWallets($wallets, $limit);

        return array_map(
            static fn ($tx): array => [
                'id' => $tx->getId(),
                'date' => $tx->getDate()->format('Y-m-d'),
                'description' => $tx->getDescription(),
                'amount' => $tx->getAmount(),
                'type' => $tx->getType()->value,
                'walletName' => $tx->getWallet()->getName(),
                'categoryName' => $tx->getCategory()?->getName(),
            ],
            $rows,
        );
    }

    /** @return array<string, mixed> */
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

    /** @return list<array<string, mixed>> */
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
     * @param list<\Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface> $wallets
     *
     * @return list<array<string, mixed>>
     */
    protected function budgetAlerts(array $wallets, DateTimeImmutable $today): array
    {
        $month = $today->modify('first day of this month')->setTime(0, 0);
        $end = $month->modify('first day of next month');

        $alerts = [];
        foreach ($wallets as $w) {
            $budget = $this->budgetRepository->findByWalletAndMonth($w, $month);
            if (null === $budget) {
                continue;
            }
            $items = $this->budgetItemRepository->findByBudget($budget);
            $actuals = $this->transactionRepository->actualsByCategoryForMonth($w, $month, $end);

            foreach ($items as $item) {
                $categoryId = $item->getCategory()?->getId();
                $actual = null === $categoryId ? '0.00' : ($actuals[$categoryId] ?? '0.00');
                $expected = bcadd($item->getPlannedAmount(), $item->getCarriedOver(), 2);
                if (1 !== bccomp($actual, $expected, 2)) {
                    continue;
                }
                $alerts[] = [
                    'walletId' => $w->getId(),
                    'walletName' => $w->getName(),
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
