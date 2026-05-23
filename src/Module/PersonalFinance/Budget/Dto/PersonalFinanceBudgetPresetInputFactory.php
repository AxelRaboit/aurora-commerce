<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Dto;

use Aurora\Core\Support\Str;
use Aurora\Module\PersonalFinance\Budget\Enum\PersonalFinanceBudgetSectionEnum;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceBudgetPresetInputFactoryInterface::class)]
class PersonalFinanceBudgetPresetInputFactory implements PersonalFinanceBudgetPresetInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PersonalFinanceBudgetPresetInputInterface
    {
        return new PersonalFinanceBudgetPresetInput(
            name: Str::trimFromArray($data, 'name'),
            description: Str::trimOrNullFromArray($data, 'description'),
            items: $this->buildItems($data['items'] ?? []),
        );
    }

    /**
     * @param mixed $raw
     *
     * @return list<PersonalFinanceBudgetPresetItemInputInterface>
     */
    protected function buildItems(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $items = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $items[] = $this->buildItem($row);
        }

        return $items;
    }

    /** @param array<string, mixed> $row */
    protected function buildItem(array $row): PersonalFinanceBudgetPresetItemInputInterface
    {
        $sectionValue = Str::trimFromArray($row, 'section');
        $section = PersonalFinanceBudgetSectionEnum::tryFrom($sectionValue) ?? PersonalFinanceBudgetSectionEnum::Expenses;

        return new PersonalFinanceBudgetPresetItemInput(
            section: $section,
            label: Str::trimFromArray($row, 'label'),
            plannedAmount: Str::trimFromArray($row, 'plannedAmount') ?: '0.00',
            categoryId: isset($row['categoryId']) && '' !== $row['categoryId'] ? (int) $row['categoryId'] : null,
            position: isset($row['position']) ? (int) $row['position'] : 0,
            notes: Str::trimOrNullFromArray($row, 'notes'),
        );
    }
}
