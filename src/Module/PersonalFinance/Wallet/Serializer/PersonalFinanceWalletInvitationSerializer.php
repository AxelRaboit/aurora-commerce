<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Serializer;

use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInvitationInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceWalletInvitationSerializerInterface::class)]
class PersonalFinanceWalletInvitationSerializer implements PersonalFinanceWalletInvitationSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(PersonalFinanceWalletInvitationInterface $invitation): array
    {
        return [
            'id' => $invitation->getId(),
            'walletId' => $invitation->getWallet()->getId(),
            'invitedById' => $invitation->getInvitedBy()->getId(),
            'email' => $invitation->getEmail(),
            'role' => $invitation->getRole()->value,
            'expiresAt' => $invitation->getExpiresAt()->format(DateTimeInterface::ATOM),
            'acceptedAt' => $invitation->getAcceptedAt()?->format(DateTimeInterface::ATOM),
            'declinedAt' => $invitation->getDeclinedAt()?->format(DateTimeInterface::ATOM),
            'createdAt' => $invitation->getCreatedAt()->format(DateTimeInterface::ATOM),
            'isPending' => $invitation->isPending(),
            'isExpired' => $invitation->isExpired(),
        ];
    }
}
