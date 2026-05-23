<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Dto;

use Aurora\Module\PersonalFinance\Wallet\Enum\PersonalFinanceWalletRoleEnum;
use Symfony\Component\Validator\Constraints as Assert;

class PersonalFinanceWalletMemberInput implements PersonalFinanceWalletMemberInputInterface
{
    public function __construct(
        #[Assert\NotNull(message: 'personal_finance.wallets.errors.role_required')]
        public readonly PersonalFinanceWalletRoleEnum $role = PersonalFinanceWalletRoleEnum::Viewer,
    ) {}

    public function getRole(): PersonalFinanceWalletRoleEnum
    {
        return $this->role;
    }
}
