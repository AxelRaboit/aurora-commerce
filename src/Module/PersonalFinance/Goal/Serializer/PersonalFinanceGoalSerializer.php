<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Goal\Serializer;

use Aurora\Module\PersonalFinance\Goal\Entity\PersonalFinanceGoalInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceGoalSerializerInterface::class)]
class PersonalFinanceGoalSerializer implements PersonalFinanceGoalSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(PersonalFinanceGoalInterface $goal): array
    {
        return [
            'id' => $goal->getId(),
            'name' => $goal->getName(),
            'targetAmount' => $goal->getTargetAmount(),
            'savedAmount' => $goal->getSavedAmount(),
            'remainingAmount' => bcsub($goal->getTargetAmount(), $goal->getSavedAmount(), 2),
            'progress' => round($goal->getProgress(), 2),
            'isCompleted' => $goal->isCompleted(),
            'isAutoTracked' => $goal->isAutoTracked(),
            'walletId' => $goal->getWallet()?->getId(),
            'walletName' => $goal->getWallet()?->getName(),
            'categoryId' => $goal->getCategory()?->getId(),
            'categoryName' => $goal->getCategory()?->getName(),
            'deadline' => $goal->getDeadline()?->format('Y-m-d'),
            'color' => $goal->getColor(),
            'createdAt' => $goal->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $goal->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
