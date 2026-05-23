<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Serializer;

use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletMemberInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceWalletMemberSerializerInterface::class)]
class PersonalFinanceWalletMemberSerializer implements PersonalFinanceWalletMemberSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(PersonalFinanceWalletMemberInterface $member): array
    {
        $user = $member->getUser();

        return [
            'id' => $member->getId(),
            'walletId' => $member->getWallet()->getId(),
            'userId' => $user->getId(),
            'userName' => $user->getName(),
            'userEmail' => $user->getEmail(),
            'role' => $member->getRole()->value,
            'createdAt' => $member->getCreatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
