<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Dto;

use Aurora\Module\PersonalFinance\Wallet\Enum\PersonalFinanceWalletRoleEnum;
use Symfony\Component\Validator\Constraints as Assert;

class PersonalFinanceWalletInvitationInput implements PersonalFinanceWalletInvitationInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'personal_finance.wallets.errors.invitation_email_required')]
        #[Assert\Email(message: 'personal_finance.wallets.errors.invitation_email_format')]
        public readonly string $email = '',
        #[Assert\NotNull(message: 'personal_finance.wallets.errors.invitation_role_required')]
        public readonly ?PersonalFinanceWalletRoleEnum $role = null,
    ) {}

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getRole(): ?PersonalFinanceWalletRoleEnum
    {
        return $this->role;
    }
}
