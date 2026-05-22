<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Dto;

interface PersonalFinanceTransactionInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PersonalFinanceTransactionInputInterface;
}
