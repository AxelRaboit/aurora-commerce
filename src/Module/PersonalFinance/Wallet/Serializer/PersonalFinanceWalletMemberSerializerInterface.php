<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Serializer;

use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletMemberInterface;

interface PersonalFinanceWalletMemberSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(PersonalFinanceWalletMemberInterface $member): array;
}
