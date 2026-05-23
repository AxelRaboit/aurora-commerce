<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Service;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\PersonalFinance\Budget\Dto\PersonalFinanceMonthResetReport;
use Aurora\Module\PersonalFinance\Budget\Manager\PersonalFinanceBudgetManagerInterface;
use Aurora\Module\PersonalFinance\Budget\Repository\PersonalFinanceBudgetRepository;
use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransactionInterface;
use Aurora\Module\PersonalFinance\Transaction\Manager\PersonalFinanceTransactionManagerInterface;
use Aurora\Module\PersonalFinance\Transaction\Repository\PersonalFinanceTransactionRepository;
use Aurora\Module\PersonalFinance\Transaction\Split\Service\PersonalFinanceSplitServiceInterface;
use Aurora\Module\PersonalFinance\Transaction\Transfer\Service\PersonalFinanceTransferServiceInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Wipes a wallet × month slice. Goes through the existing services so
 * the multi-row constraints (transfer = 2 legs across wallets, split =
 * N siblings, goal sync) stay coherent. Other months are not touched —
 * see the interface doc for the design rationale.
 *
 * Non-`final` + `readonly` so a client can swap the constituents (e.g.
 * route transfer/split deletes through a custom service or extend
 * the audit envelope).
 */
#[AsAlias(PersonalFinanceMonthResetServiceInterface::class)]
readonly class PersonalFinanceMonthResetService implements PersonalFinanceMonthResetServiceInterface
{
    public function __construct(
        protected PersonalFinanceTransactionRepository $transactionRepository,
        protected PersonalFinanceTransactionManagerInterface $transactionManager,
        protected PersonalFinanceTransferServiceInterface $transferService,
        protected PersonalFinanceSplitServiceInterface $splitService,
        protected PersonalFinanceBudgetRepository $budgetRepository,
        protected PersonalFinanceBudgetManagerInterface $budgetManager,
        protected AuditLogger $auditLogger,
    ) {}

    public function reset(
        PersonalFinanceWalletInterface $wallet,
        DateTimeImmutable $fromMonth,
        bool $cascade,
        bool $clearBudget,
    ): PersonalFinanceMonthResetReport {
        $start = $fromMonth->modify('first day of this month')->setTime(0, 0);
        $end = $this->resolveEndMonth($start, $cascade);

        $totalDeleted = 0;
        $budgetClearedAny = false;
        $monthsProcessed = 0;

        $cursor = $start;
        while ($cursor <= $end) {
            [$deletedThisMonth, $budgetClearedThisMonth] = $this->resetOneMonth($wallet, $cursor, $clearBudget);
            $totalDeleted += $deletedThisMonth;
            $budgetClearedAny = $budgetClearedAny || $budgetClearedThisMonth;
            ++$monthsProcessed;

            $cursor = $cursor->modify('first day of next month');
        }

        $this->auditLogger->log(
            'personal_finance',
            'month.reset',
            'PersonalFinanceWallet',
            $wallet->getId(),
            [
                'fromMonth' => $start->format('Y-m'),
                'toMonth' => $end->format('Y-m'),
                'monthsProcessed' => $monthsProcessed,
                'deletedTransactions' => $totalDeleted,
                'budgetCleared' => $budgetClearedAny,
            ],
        );

        return new PersonalFinanceMonthResetReport($totalDeleted, $budgetClearedAny, $monthsProcessed);
    }

    /**
     * Caps the cascade at the current month — the policy lives here so
     * callers don't have to repeat it. Single-month resets resolve to
     * `$start` regardless of how far back it is.
     */
    protected function resolveEndMonth(DateTimeImmutable $start, bool $cascade): DateTimeImmutable
    {
        if (!$cascade) {
            return $start;
        }
        $today = new DateTimeImmutable('first day of this month')->setTime(0, 0);

        return $today < $start ? $start : $today;
    }

    /** @return array{0: int, 1: bool} `[deletedCount, budgetClearedThisMonth]` */
    protected function resetOneMonth(
        PersonalFinanceWalletInterface $wallet,
        DateTimeImmutable $month,
        bool $clearBudget,
    ): array {
        $transactions = $this->transactionRepository->findByWalletAndMonth($wallet, $month);

        $handledIds = [];
        $deletedCount = 0;
        foreach ($transactions as $transaction) {
            if (in_array($transaction->getId(), $handledIds, true)) {
                continue;
            }
            $deletedCount += $this->deleteTransactionGroup($transaction, $handledIds);
        }

        $budgetCleared = false;
        if ($clearBudget) {
            $budget = $this->budgetRepository->findByWalletAndMonth($wallet, $month);
            if (null !== $budget) {
                $this->budgetManager->delete($budget);
                $budgetCleared = true;
            }
        }

        return [$deletedCount, $budgetCleared];
    }

    /**
     * Routes the delete through the right service based on the
     * transaction's nature. Returns the count of rows actually removed
     * so the caller can roll up the report. Tracks every id touched in
     * `$handledIds` to skip the counterpart on the next iteration of
     * the outer loop (transfer leg + split sibling already handled).
     *
     * @param list<int|null> $handledIds
     */
    protected function deleteTransactionGroup(PersonalFinanceTransactionInterface $transaction, array &$handledIds): int
    {
        // Transfer leg → delete both legs atomically via the service.
        if (null !== $transaction->getTransferId()) {
            $siblings = $this->transactionRepository->findByTransferId($transaction->getTransferId());
            foreach ($siblings as $sibling) {
                $handledIds[] = $sibling->getId();
            }
            $this->transferService->delete($transaction->getTransferId());

            return count($siblings);
        }

        // Split parent / part → delete the whole group via the service.
        if (null !== $transaction->getSplitId()) {
            $siblings = $this->transactionRepository->findBySplitId($transaction->getSplitId());
            foreach ($siblings as $sibling) {
                $handledIds[] = $sibling->getId();
            }
            $this->splitService->delete($transaction->getSplitId());

            return count($siblings);
        }

        // Plain transaction → manager (audit + goal sync fire).
        $handledIds[] = $transaction->getId();
        $this->transactionManager->delete($transaction);

        return 1;
    }
}
