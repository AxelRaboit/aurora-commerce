<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Recurring\Dto;

use Aurora\Core\Support\Str;
use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use DateTimeImmutable;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceScheduledTransactionInputFactoryInterface::class)]
class PersonalFinanceScheduledTransactionInputFactory implements PersonalFinanceScheduledTransactionInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PersonalFinanceScheduledTransactionInputInterface
    {
        $typeValue = Str::trimFromArray($data, 'type');
        $type = PersonalFinanceTransactionTypeEnum::tryFrom($typeValue) ?? PersonalFinanceTransactionTypeEnum::Expense;

        $dateValue = Str::trimOrNullFromArray($data, 'scheduledDate');
        $scheduledDate = null;
        if (null !== $dateValue) {
            try {
                $scheduledDate = new DateTimeImmutable($dateValue);
            } catch (Exception) {
                $scheduledDate = null;
            }
        }

        return new PersonalFinanceScheduledTransactionInput(
            walletId: isset($data['walletId']) && '' !== $data['walletId'] ? (int) $data['walletId'] : null,
            categoryId: isset($data['categoryId']) && '' !== $data['categoryId'] ? (int) $data['categoryId'] : null,
            type: $type,
            amount: Str::trimFromArray($data, 'amount') ?: '0.00',
            description: Str::trimOrNullFromArray($data, 'description'),
            scheduledDate: $scheduledDate,
        );
    }
}
