<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Goal\Dto;

use Aurora\Core\Support\Str;
use DateTimeImmutable;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceGoalInputFactoryInterface::class)]
class PersonalFinanceGoalInputFactory implements PersonalFinanceGoalInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PersonalFinanceGoalInputInterface
    {
        $deadlineValue = Str::trimOrNullFromArray($data, 'deadline');
        $deadline = null;
        if (null !== $deadlineValue) {
            try {
                $deadline = new DateTimeImmutable($deadlineValue);
            } catch (Exception) {
                $deadline = null;
            }
        }

        return new PersonalFinanceGoalInput(
            name: Str::trimFromArray($data, 'name'),
            targetAmount: Str::trimFromArray($data, 'targetAmount') ?: '0.00',
            walletId: isset($data['walletId']) && '' !== $data['walletId'] ? (int) $data['walletId'] : null,
            categoryId: isset($data['categoryId']) && '' !== $data['categoryId'] ? (int) $data['categoryId'] : null,
            deadline: $deadline,
            color: Str::trimOrNullFromArray($data, 'color'),
        );
    }
}
