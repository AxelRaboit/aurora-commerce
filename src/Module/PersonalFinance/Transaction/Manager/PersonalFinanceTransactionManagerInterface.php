<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Manager;

use Aurora\Module\PersonalFinance\Transaction\Dto\PersonalFinanceTransactionInputInterface;
use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransactionInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;

interface PersonalFinanceTransactionManagerInterface
{
    public function create(CoreUserInterface $user, PersonalFinanceWalletInterface $wallet, PersonalFinanceTransactionInputInterface $input): PersonalFinanceTransactionInterface;

    public function update(PersonalFinanceTransactionInterface $transaction, PersonalFinanceTransactionInputInterface $input): void;

    public function delete(PersonalFinanceTransactionInterface $transaction): void;
}
