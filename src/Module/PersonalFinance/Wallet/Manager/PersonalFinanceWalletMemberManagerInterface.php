<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Manager;

use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletMemberInterface;
use Aurora\Module\PersonalFinance\Wallet\Enum\PersonalFinanceWalletRoleEnum;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;

interface PersonalFinanceWalletMemberManagerInterface
{
    public function create(PersonalFinanceWalletInterface $wallet, CoreUserInterface $user, PersonalFinanceWalletRoleEnum $role): PersonalFinanceWalletMemberInterface;

    public function delete(PersonalFinanceWalletMemberInterface $member): void;
}
