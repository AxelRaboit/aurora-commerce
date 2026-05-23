<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Split\Service;

use Aurora\Module\PersonalFinance\Transaction\Split\Dto\PersonalFinanceSplitInputInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;

interface PersonalFinanceSplitServiceInterface
{
    /**
     * Creates a split as N transactions sharing a UUID splitId. All parts
     * inherit the same wallet, type, date, description, tags — only
     * category and amount vary per part.
     *
     * Returns the splitId.
     *
     * The caller must have access-checked the wallet via
     * PersonalFinanceWalletVoter::EDIT_TRANSACTIONS.
     */
    public function create(
        CoreUserInterface $user,
        PersonalFinanceWalletInterface $wallet,
        PersonalFinanceSplitInputInterface $input,
    ): string;

    /**
     * Deletes every transaction sharing the splitId atomically.
     */
    public function delete(string $splitId): void;
}
