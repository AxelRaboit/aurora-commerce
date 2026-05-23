<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Transfer\Dto;

use Aurora\Core\Support\Str;
use DateTimeImmutable;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceTransferInputFactoryInterface::class)]
class PersonalFinanceTransferInputFactory implements PersonalFinanceTransferInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PersonalFinanceTransferInputInterface
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

        return new PersonalFinanceTransferInput(
            fromWalletId: isset($data['fromWalletId']) ? (int) $data['fromWalletId'] : null,
            toWalletId: isset($data['toWalletId']) ? (int) $data['toWalletId'] : null,
            amount: Str::trimFromArray($data, 'amount') ?: '0.00',
            date: $date,
            description: Str::trimOrNullFromArray($data, 'description'),
        );
    }
}
