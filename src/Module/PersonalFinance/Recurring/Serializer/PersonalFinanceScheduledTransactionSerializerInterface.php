<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Recurring\Serializer;

use Aurora\Module\PersonalFinance\Recurring\Entity\PersonalFinanceScheduledTransactionInterface;

interface PersonalFinanceScheduledTransactionSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(PersonalFinanceScheduledTransactionInterface $sched): array;
}
