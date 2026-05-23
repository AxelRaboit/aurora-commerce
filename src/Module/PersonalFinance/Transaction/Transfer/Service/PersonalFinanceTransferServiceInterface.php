<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Transfer\Service;

use Aurora\Module\PersonalFinance\Transaction\Transfer\Dto\PersonalFinanceTransferInputInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;

interface PersonalFinanceTransferServiceInterface
{
    /**
     * Creates a wallet-to-wallet transfer as a pair of linked transactions
     * (one Expense on the source, one Income on the target) sharing a UUID
     * transferId. Returns the transferId.
     *
     * Both wallets must be access-checked by the caller via
     * PersonalFinanceWalletVoter::EDIT_TRANSACTIONS before invoking.
     */
    public function create(
        CoreUserInterface $user,
        PersonalFinanceWalletInterface $fromWallet,
        PersonalFinanceWalletInterface $toWallet,
        PersonalFinanceTransferInputInterface $input,
    ): string;

    /**
     * Updates the editable fields (amount, date, description) of both
     * sides of an existing transfer. Wallets cannot be changed by an
     * update — to switch wallets, delete the transfer and create a new
     * one.
     */
    public function update(string $transferId, PersonalFinanceTransferInputInterface $input): void;

    /**
     * Deletes both transactions of the transfer atomically.
     */
    public function delete(string $transferId): void;
}
