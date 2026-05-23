<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Goal\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceGoalDepositInputFactoryInterface::class)]
class PersonalFinanceGoalDepositInputFactory implements PersonalFinanceGoalDepositInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PersonalFinanceGoalDepositInputInterface
    {
        return new PersonalFinanceGoalDepositInput(
            amount: Str::trimFromArray($data, 'amount') ?: '0.00',
        );
    }
}
