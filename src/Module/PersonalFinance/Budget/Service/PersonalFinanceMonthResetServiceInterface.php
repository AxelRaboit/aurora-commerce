<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Service;

use Aurora\Module\PersonalFinance\Budget\Dto\PersonalFinanceMonthResetReport;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use DateTimeImmutable;

interface PersonalFinanceMonthResetServiceInterface
{
    /**
     * Wipes every transaction belonging to (wallet, fromMonth) and,
     * when `$cascade` is true, every following month up to and
     * including the current month. Transfer legs deleted via
     * TransferService so the counterpart on the destination wallet is
     * removed too; split parts via SplitService. `$clearBudget=true`
     * also deletes the Budget entity (items cascade) for every month
     * touched.
     *
     * Months OUTSIDE the resolved range are NEVER touched — per
     * Aurora's design each month is structurally independent and
     * balances of untouched months recompute on read.
     *
     * "Cascade" deliberately stops at the current month even if
     * future-dated transactions exist on the wallet: those are
     * typically scheduled materializations or planned entries the
     * user does not want silently wiped.
     */
    public function reset(
        PersonalFinanceWalletInterface $wallet,
        DateTimeImmutable $fromMonth,
        bool $cascade,
        bool $clearBudget,
    ): PersonalFinanceMonthResetReport;
}
