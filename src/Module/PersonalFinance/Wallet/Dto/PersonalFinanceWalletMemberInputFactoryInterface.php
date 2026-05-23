<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Dto;

use Aurora\Module\PersonalFinance\Wallet\Enum\PersonalFinanceWalletRoleEnum;

interface PersonalFinanceWalletMemberInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PersonalFinanceWalletMemberInputInterface;

    public function fromRole(PersonalFinanceWalletRoleEnum $role): PersonalFinanceWalletMemberInputInterface;
}
