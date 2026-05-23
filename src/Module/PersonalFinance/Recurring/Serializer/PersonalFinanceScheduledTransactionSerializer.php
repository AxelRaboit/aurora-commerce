<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Recurring\Serializer;

use Aurora\Module\PersonalFinance\Recurring\Entity\PersonalFinanceScheduledTransactionInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceScheduledTransactionSerializerInterface::class)]
class PersonalFinanceScheduledTransactionSerializer implements PersonalFinanceScheduledTransactionSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(PersonalFinanceScheduledTransactionInterface $sched): array
    {
        return [
            'id' => $sched->getId(),
            'walletId' => $sched->getWallet()->getId(),
            'walletName' => $sched->getWallet()->getName(),
            'categoryId' => $sched->getCategory()?->getId(),
            'categoryName' => $sched->getCategory()?->getName(),
            'type' => $sched->getType()->value,
            'amount' => $sched->getAmount(),
            'description' => $sched->getDescription(),
            'scheduledDate' => $sched->getScheduledDate()->format('Y-m-d'),
            'generated' => $sched->isGenerated(),
            'createdAt' => $sched->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $sched->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
