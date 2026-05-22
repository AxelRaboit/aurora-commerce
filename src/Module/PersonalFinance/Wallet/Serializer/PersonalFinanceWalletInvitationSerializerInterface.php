<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Serializer;

use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInvitationInterface;

interface PersonalFinanceWalletInvitationSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(PersonalFinanceWalletInvitationInterface $invitation): array;
}
