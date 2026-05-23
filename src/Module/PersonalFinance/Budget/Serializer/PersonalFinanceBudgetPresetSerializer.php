<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Serializer;

use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetPresetInterface;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetPresetItemInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceBudgetPresetSerializerInterface::class)]
class PersonalFinanceBudgetPresetSerializer implements PersonalFinanceBudgetPresetSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(PersonalFinanceBudgetPresetInterface $preset): array
    {
        return [
            'id' => $preset->getId(),
            'walletId' => $preset->getWallet()->getId(),
            'name' => $preset->getName(),
            'description' => $preset->getDescription(),
            'itemCount' => $preset->getItems()->count(),
            'items' => array_map($this->serializeItem(...), $preset->getItems()->toArray()),
            'createdAt' => $preset->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $preset->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }

    /** @return array<string, mixed> */
    public function serializeItem(PersonalFinanceBudgetPresetItemInterface $item): array
    {
        $category = $item->getCategory();

        return [
            'id' => $item->getId(),
            'section' => $item->getSection()->value,
            'label' => $item->getLabel(),
            'plannedAmount' => $item->getPlannedAmount(),
            'categoryId' => $category?->getId(),
            'categoryName' => $category?->getName(),
            'position' => $item->getPosition(),
            'notes' => $item->getNotes(),
        ];
    }
}
