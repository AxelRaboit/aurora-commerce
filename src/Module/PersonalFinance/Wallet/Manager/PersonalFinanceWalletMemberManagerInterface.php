<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Manager;

use Aurora\Module\PersonalFinance\Wallet\Dto\PersonalFinanceWalletMemberInputInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletMemberInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;

interface PersonalFinanceWalletMemberManagerInterface
{
    public function create(
        PersonalFinanceWalletInterface $wallet,
        CoreUserInterface $user,
        PersonalFinanceWalletMemberInputInterface $input,
    ): PersonalFinanceWalletMemberInterface;

    public function update(
        PersonalFinanceWalletMemberInterface $member,
        PersonalFinanceWalletMemberInputInterface $input,
    ): void;

    public function delete(PersonalFinanceWalletMemberInterface $member): void;

    /**
     * Convenience public wrapper that delegates to delete() after a guard:
     * the Owner can never be removed via this path (transfer ownership
     * first). Kept on the interface because controllers / voters expect
     * it explicitly — but internally it's just a guarded `delete()`.
     */
    public function removeMember(PersonalFinanceWalletMemberInterface $member): void;
}
