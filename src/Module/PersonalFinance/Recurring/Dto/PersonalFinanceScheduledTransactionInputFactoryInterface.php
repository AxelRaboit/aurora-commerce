<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Recurring\Dto;

interface PersonalFinanceScheduledTransactionInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PersonalFinanceScheduledTransactionInputInterface;
}
