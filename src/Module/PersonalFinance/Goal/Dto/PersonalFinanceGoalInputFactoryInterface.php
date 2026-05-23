<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Goal\Dto;

interface PersonalFinanceGoalInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PersonalFinanceGoalInputInterface;
}
