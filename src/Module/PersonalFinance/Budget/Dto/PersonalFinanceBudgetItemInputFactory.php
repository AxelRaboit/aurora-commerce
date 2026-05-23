<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Dto;

use Aurora\Core\Support\Str;
use Aurora\Module\PersonalFinance\Budget\Enum\PersonalFinanceBudgetSectionEnum;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceBudgetItemInputFactoryInterface::class)]
class PersonalFinanceBudgetItemInputFactory implements PersonalFinanceBudgetItemInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PersonalFinanceBudgetItemInputInterface
    {
        $sectionValue = Str::trimFromArray($data, 'section');
        $section = PersonalFinanceBudgetSectionEnum::tryFrom($sectionValue) ?? PersonalFinanceBudgetSectionEnum::Expenses;

        return new PersonalFinanceBudgetItemInput(
            section: $section,
            label: Str::trimFromArray($data, 'label'),
            plannedAmount: Str::trimFromArray($data, 'plannedAmount') ?: '0.00',
            carriedOver: Str::trimFromArray($data, 'carriedOver') ?: '0.00',
            categoryId: isset($data['categoryId']) && '' !== $data['categoryId'] ? (int) $data['categoryId'] : null,
            position: isset($data['position']) ? (int) $data['position'] : 0,
            notes: Str::trimOrNullFromArray($data, 'notes'),
            repeatNextMonth: !empty($data['repeatNextMonth']),
        );
    }
}
