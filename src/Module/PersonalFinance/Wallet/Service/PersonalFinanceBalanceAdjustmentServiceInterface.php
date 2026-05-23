<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Service;

use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransactionInterface;
use Aurora\Module\PersonalFinance\Wallet\Dto\PersonalFinanceBalanceAdjustmentInputInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;

interface PersonalFinanceBalanceAdjustmentServiceInterface
{
    /**
     * Creates an adjustment transaction that brings the wallet's
     * computed current balance to the requested newBalance value:
     *  - diff = newBalance − currentBalance
     *  - Income if diff > 0, Expense if diff < 0 (amount = |diff|)
     *  - category = lazy `BalanceAdjustment` system category.
     *
     * Throws DomainException if diff is exactly 0 (no-op adjustment).
     */
    public function adjust(
        CoreUserInterface $user,
        PersonalFinanceWalletInterface $wallet,
        PersonalFinanceBalanceAdjustmentInputInterface $input,
    ): PersonalFinanceTransactionInterface;
}
