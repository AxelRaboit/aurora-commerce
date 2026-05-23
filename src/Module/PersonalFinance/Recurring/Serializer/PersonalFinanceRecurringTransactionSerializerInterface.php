<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Recurring\Serializer;

use Aurora\Module\PersonalFinance\Recurring\Entity\PersonalFinanceRecurringTransactionInterface;

interface PersonalFinanceRecurringTransactionSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(PersonalFinanceRecurringTransactionInterface $rec): array;
}
