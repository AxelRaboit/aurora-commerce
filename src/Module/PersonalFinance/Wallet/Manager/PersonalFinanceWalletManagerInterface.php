<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Manager;

use Aurora\Module\PersonalFinance\Wallet\Dto\PersonalFinanceWalletInputInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;

interface PersonalFinanceWalletManagerInterface
{
    public function create(CoreUserInterface $owner, PersonalFinanceWalletInputInterface $input): PersonalFinanceWalletInterface;

    public function update(PersonalFinanceWalletInterface $wallet, PersonalFinanceWalletInputInterface $input): void;

    public function delete(PersonalFinanceWalletInterface $wallet): void;
}
