<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Serializer;

use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetInterface;

interface PersonalFinanceBudgetSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(PersonalFinanceBudgetInterface $budget): array;
}
