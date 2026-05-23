<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Goal\Dto;

interface PersonalFinanceGoalDepositInputInterface
{
    public function getAmount(): string;
}
