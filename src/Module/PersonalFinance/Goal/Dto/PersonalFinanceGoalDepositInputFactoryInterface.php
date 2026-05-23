<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Goal\Dto;

interface PersonalFinanceGoalDepositInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PersonalFinanceGoalDepositInputInterface;
}
