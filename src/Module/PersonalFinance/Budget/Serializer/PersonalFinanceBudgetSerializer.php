<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Serializer;

use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceBudgetSerializerInterface::class)]
class PersonalFinanceBudgetSerializer implements PersonalFinanceBudgetSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(PersonalFinanceBudgetInterface $budget): array
    {
        return [
            'id' => $budget->getId(),
            'walletId' => $budget->getWallet()->getId(),
            'month' => $budget->getMonth()->format('Y-m'),
            'notes' => $budget->getNotes(),
            'createdAt' => $budget->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $budget->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
