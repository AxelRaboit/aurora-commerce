<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Dto;

interface PersonalFinanceBudgetItemInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PersonalFinanceBudgetItemInputInterface;
}
