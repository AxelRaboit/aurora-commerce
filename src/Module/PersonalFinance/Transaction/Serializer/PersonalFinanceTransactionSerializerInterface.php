<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Serializer;

use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransactionInterface;

interface PersonalFinanceTransactionSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(PersonalFinanceTransactionInterface $transaction): array;
}
