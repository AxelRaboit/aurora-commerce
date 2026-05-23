<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Categorization\Dto;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceCategorizationRuleInputFactoryInterface::class)]
class PersonalFinanceCategorizationRuleInputFactory implements PersonalFinanceCategorizationRuleInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PersonalFinanceCategorizationRuleInputInterface
    {
        return new PersonalFinanceCategorizationRuleInput(
            categoryId: isset($data['categoryId']) && '' !== $data['categoryId'] ? (int) $data['categoryId'] : null,
        );
    }
}
