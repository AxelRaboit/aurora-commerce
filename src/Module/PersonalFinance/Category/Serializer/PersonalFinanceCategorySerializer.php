<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Category\Serializer;

use Aurora\Module\PersonalFinance\Category\Entity\PersonalFinanceCategoryInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceCategorySerializerInterface::class)]
class PersonalFinanceCategorySerializer implements PersonalFinanceCategorySerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(PersonalFinanceCategoryInterface $category): array
    {
        return [
            'id' => $category->getId(),
            'walletId' => $category->getWallet()->getId(),
            'userId' => $category->getUser()->getId(),
            'name' => $category->getName(),
            'isSystem' => $category->isSystem(),
            'systemKey' => $category->getSystemKey(),
            'createdAt' => $category->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $category->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
