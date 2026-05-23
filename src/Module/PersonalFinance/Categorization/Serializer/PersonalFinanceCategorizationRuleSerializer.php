<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Categorization\Serializer;

use Aurora\Module\PersonalFinance\Categorization\Entity\PersonalFinanceCategorizationRuleInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceCategorizationRuleSerializerInterface::class)]
class PersonalFinanceCategorizationRuleSerializer implements PersonalFinanceCategorizationRuleSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(PersonalFinanceCategorizationRuleInterface $rule): array
    {
        return [
            'id' => $rule->getId(),
            'pattern' => $rule->getPattern(),
            'categoryId' => $rule->getCategory()->getId(),
            'categoryName' => $rule->getCategory()->getName(),
            'walletId' => $rule->getCategory()->getWallet()->getId(),
            'walletName' => $rule->getCategory()->getWallet()->getName(),
            'hits' => $rule->getHits(),
            'createdAt' => $rule->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $rule->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
