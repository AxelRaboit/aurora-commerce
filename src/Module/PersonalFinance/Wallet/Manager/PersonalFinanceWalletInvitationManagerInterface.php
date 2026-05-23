<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Manager;

use Aurora\Module\PersonalFinance\Wallet\Dto\PersonalFinanceWalletInvitationInputInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInvitationInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletMemberInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;

interface PersonalFinanceWalletInvitationManagerInterface
{
    public function send(
        PersonalFinanceWalletInterface $wallet,
        PersonalFinanceWalletInvitationInputInterface $input,
        CoreUserInterface $invitedBy,
    ): PersonalFinanceWalletInvitationInterface;

    public function accept(string $token, CoreUserInterface $accepter): ?PersonalFinanceWalletMemberInterface;

    public function decline(string $token): bool;

    public function revoke(PersonalFinanceWalletInvitationInterface $invitation): void;

    public function resend(PersonalFinanceWalletInvitationInterface $invitation): void;
}
