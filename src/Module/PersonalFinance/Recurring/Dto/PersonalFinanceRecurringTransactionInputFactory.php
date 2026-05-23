<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Recurring\Dto;

use Aurora\Core\Support\Str;
use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceRecurringTransactionInputFactoryInterface::class)]
class PersonalFinanceRecurringTransactionInputFactory implements PersonalFinanceRecurringTransactionInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PersonalFinanceRecurringTransactionInputInterface
    {
        $typeValue = Str::trimFromArray($data, 'type');
        $type = PersonalFinanceTransactionTypeEnum::tryFrom($typeValue) ?? PersonalFinanceTransactionTypeEnum::Expense;

        return new PersonalFinanceRecurringTransactionInput(
            walletId: isset($data['walletId']) && '' !== $data['walletId'] ? (int) $data['walletId'] : null,
            categoryId: isset($data['categoryId']) && '' !== $data['categoryId'] ? (int) $data['categoryId'] : null,
            type: $type,
            amount: Str::trimFromArray($data, 'amount') ?: '0.00',
            description: Str::trimOrNullFromArray($data, 'description'),
            dayOfMonth: isset($data['dayOfMonth']) ? max(1, min(28, (int) $data['dayOfMonth'])) : 1,
            active: !isset($data['active']) || !empty($data['active']),
        );
    }
}
