<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Service;

use Aurora\Module\PersonalFinance\Budget\Dto\PersonalFinanceMonthResetReport;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use DateTimeImmutable;

interface PersonalFinanceMonthResetServiceInterface
{
    /**
     * Wipes every transaction belonging to (wallet, month) — transfer
     * legs deleted via TransferService so the counterpart on the
     * destination wallet is removed too, split parts via SplitService.
     * `clearBudget=true` also deletes the Budget entity (items cascade).
     *
     * Other months are NEVER touched: per Aurora's design, each month
     * is structurally independent. The current month's balance will
     * shift accordingly when transactions for prior months disappear,
     * which is the intended behaviour.
     */
    public function reset(
        PersonalFinanceWalletInterface $wallet,
        DateTimeImmutable $month,
        bool $clearBudget,
    ): PersonalFinanceMonthResetReport;
}
