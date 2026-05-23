<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Serializer;

use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetItemInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceBudgetItemSerializerInterface::class)]
class PersonalFinanceBudgetItemSerializer implements PersonalFinanceBudgetItemSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(PersonalFinanceBudgetItemInterface $item, ?string $actual = null): array
    {
        $expected = bcadd($item->getPlannedAmount(), $item->getCarriedOver(), 2);

        $payload = [
            'id' => $item->getId(),
            'budgetId' => $item->getBudget()->getId(),
            'section' => $item->getSection()->value,
            'label' => $item->getLabel(),
            'plannedAmount' => $item->getPlannedAmount(),
            'carriedOver' => $item->getCarriedOver(),
            'expected' => $expected,
            'categoryId' => $item->getCategory()?->getId(),
            'categoryName' => $item->getCategory()?->getName(),
            'position' => $item->getPosition(),
            'notes' => $item->getNotes(),
            'repeatNextMonth' => $item->repeatsNextMonth(),
            'createdAt' => $item->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $item->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];

        if (null !== $actual) {
            $payload['actual'] = $actual;
            $payload['diff'] = bcsub($expected, $actual, 2);
        }

        return $payload;
    }
}
