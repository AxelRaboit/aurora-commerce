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
     * Number of items the last `ensureForMonth` call copied from the
     * previous month via the auto-rollover service. Zero when the
     * budget already existed (no creation, no rollover) or when no
     * previous month had repeat-flagged items.
     */
    public function lastRolloverCount(): int;
}
