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
        DateTimeImmutable $month,
        bool $clearBudget,
    ): PersonalFinanceMonthResetReport {
        $transactions = $this->transactionRepository->findByWalletAndMonth($wallet, $month);

        $handledIds = [];
        $deletedCount = 0;

        foreach ($transactions as $transaction) {
            if (in_array($transaction->getId(), $handledIds, true)) {
                continue;
            }

            $deleted = $this->deleteTransactionGroup($transaction, $handledIds);
            $deletedCount += $deleted;
        }

        $budgetCleared = false;
        if ($clearBudget) {
            $budget = $this->budgetRepository->findByWalletAndMonth($wallet, $month);
            if (null !== $budget) {
                $this->budgetManager->delete($budget);
                $budgetCleared = true;
            }
        }

        $this->auditLogger->log(
            'personal_finance',
            'month.reset',
            'PersonalFinanceWallet',
            $wallet->getId(),
            [
                'month' => $month->format('Y-m'),
                'deletedTransactions' => $deletedCount,
                'budgetCleared' => $budgetCleared,
            ],
        );

        return new PersonalFinanceMonthResetReport($deletedCount, $budgetCleared);
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
