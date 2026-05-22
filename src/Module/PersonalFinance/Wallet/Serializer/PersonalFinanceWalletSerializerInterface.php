<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Serializer;

use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;

interface PersonalFinanceWalletSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(PersonalFinanceWalletInterface $wallet): array;
}
