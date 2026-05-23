<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Dto;

use Aurora\Module\PersonalFinance\Wallet\Enum\PersonalFinanceWalletRoleEnum;

interface PersonalFinanceWalletInvitationInputInterface
{
    public function getEmail(): string;

    public function getRole(): ?PersonalFinanceWalletRoleEnum;
}
