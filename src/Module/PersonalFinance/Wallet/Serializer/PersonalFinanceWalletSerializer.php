<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Serializer;

use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceWalletSerializerInterface::class)]
class PersonalFinanceWalletSerializer implements PersonalFinanceWalletSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(PersonalFinanceWalletInterface $wallet): array
    {
        return [
            'id' => $wallet->getId(),
            'name' => $wallet->getName(),
            'startBalance' => $wallet->getStartBalance(),
            'mode' => $wallet->getMode()->value,
            'showOnDashboard' => $wallet->isShowOnDashboard(),
            'position' => $wallet->getPosition(),
            'ownerId' => $wallet->getOwner()->getId(),
            'createdAt' => $wallet->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $wallet->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
