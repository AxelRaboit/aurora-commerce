<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Overview\Service;

use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Aurora\Module\PersonalFinance\Transaction\Repository\PersonalFinanceTransactionRepository;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Repository\PersonalFinanceWalletRepository;
use Aurora\Module\PersonalFinance\Wallet\Service\PersonalFinanceWalletBalanceServiceInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Cross-wallet aggregations for the Overview page. Distinct from
 * the Dashboard which focuses on pinned wallets + current-month
 * KPIs — Overview is the unfiltered global view : every accessible
 * wallet contributes, every category sums together, the user reads
 * the big picture in one glance.
 *
 * Stateless, non-`final`, hook-based per the convention — clients
 * can swap any computation by extending one of the protected helpers.
 */
#[AsAlias(PersonalFinanceOverviewServiceInterface::class)]
class PersonalFinanceOverviewService implements PersonalFinanceOverviewServiceInterface
{
    public function __construct(
        protected readonly PersonalFinanceWalletRepository $walletRepository,
        protected readonly PersonalFinanceTransactionRepository $transactionRepository,
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
            'walletsBreakdown' => $this->walletsBreakdown($wallets, $monthStart, $monthEnd),
            'categoryBreakdown' => $this->categoryBreakdown($wallets, $monthStart, $monthEnd),
            'recentTransactions' => $this->recentTransactions($wallets),
        ];
    }

    /**
     * Headline KPIs : cross-wallet total balance, count of accessible
     * wallets, total income / expense / net for the current month.
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
     * Per-wallet row: name, current balance, this-month income/expense,
     * share of the total balance as a percentage (rounded int, 0–100).
     * Sorted by balance descending so the heaviest wallets surface first.
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

        // Percentage share of the absolute total — uses abs so negative
        // wallets still get a sensible bar relative to the cumulative mass.
        $absTotalFloat = (float) $absTotal;
        foreach ($rows as $i => $row) {
            $abs = abs((float) $row['balance']);
            $rows[$i]['share'] = $absTotalFloat > 0 ? (int) round(($abs / $absTotalFloat) * 100) : 0;
        }

        usort($rows, static fn (array $a, array $b): int => bccomp($b['balance'], $a['balance'], 2));

        return array_values($rows);
    }

    /**
     * Top categories cross-wallet for the current month. Returns the
     * top 10 by total spent, with a percentage relative to the total
     * expense pot. Excludes system categories (transfer legs +
     * balance-adjustment), since they're not real spending.
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
     * Recent transactions across all wallets (the Dashboard already
     * exposes a similar block but limited to pinned wallets — Overview
     * goes broader).
     *
     * @param list<PersonalFinanceWalletInterface> $wallets
     *
     * @return list<array<string, mixed>>
     */
    protected function recentTransactions(array $wallets): array
    {
        if ([] === $wallets) {
            return [];
        }

        $out = [];
        foreach ($this->transactionRepository->findRecentAcrossWallets($wallets, limit: 8) as $tx) {
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
}
