<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Manager;

use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DateTimeImmutable;

interface PersonalFinanceBudgetManagerInterface
{
    /**
     * Returns the budget for (wallet, month), creating it on the fly
     * if missing. Idempotent — calling twice yields the same entity.
     * The unique constraint on (wallet_id, month) guarantees there can
     * never be two budgets for the same month.
     */
    public function ensureForMonth(
        CoreUserInterface $user,
        PersonalFinanceWalletInterface $wallet,
        DateTimeImmutable $month,
    ): PersonalFinanceBudgetInterface;

    public function updateNotes(PersonalFinanceBudgetInterface $budget, ?string $notes): void;

    public function delete(PersonalFinanceBudgetInterface $budget): void;

    /**
     * Explicit rollover from the previous month's `repeatNextMonth`
     * items. Triggered by a button in the Budget UI, no longer implicit
     * on `ensureForMonth`. Sets `rolledOverAt` so the banner hides on
     * subsequent loads. Returns the count of items inserted.
     */
    public function rolloverFromPrevious(PersonalFinanceBudgetInterface $budget): int;
}
