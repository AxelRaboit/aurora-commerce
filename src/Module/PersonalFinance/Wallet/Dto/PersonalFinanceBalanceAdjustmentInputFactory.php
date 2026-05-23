<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Wallet\Dto;

use Aurora\Core\Support\Str;
use DateTimeImmutable;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceBalanceAdjustmentInputFactoryInterface::class)]
class PersonalFinanceBalanceAdjustmentInputFactory implements PersonalFinanceBalanceAdjustmentInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PersonalFinanceBalanceAdjustmentInputInterface
    {
        $dateValue = Str::trimOrNullFromArray($data, 'date');
        $date = null;
        if (null !== $dateValue) {
            try {
                $date = new DateTimeImmutable($dateValue);
            } catch (Exception) {
                $date = null;
            }
        }

        return new PersonalFinanceBalanceAdjustmentInput(
            newBalance: Str::trimFromArray($data, 'newBalance') ?: '0.00',
            date: $date,
            description: Str::trimOrNullFromArray($data, 'description'),
        );
    }
}
